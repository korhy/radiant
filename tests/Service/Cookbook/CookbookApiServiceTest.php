<?php

declare(strict_types=1);

namespace App\Tests\Service\Cookbook;

use App\Service\Cookbook\CookbookApiService;
use App\Service\Cookbook\Exception\CookbookUnavailableException;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * The Cookbook client talks to an external API: everything goes through
 * MockHttpClient, the suite must never depend on the network.
 */
final class CookbookApiServiceTest extends TestCase
{
    private const USERNAME = 'test-user';
    private const PASSWORD = 'test-password';

    /**
     * @param list<MockResponse|callable> $responses
     *
     * @return array{0: CookbookApiService, 1: MockHttpClient}
     */
    private function service(array $responses, ?LoggerInterface $logger = null): array
    {
        $client = new MockHttpClient($responses, 'https://cookbook.test');

        $service = new CookbookApiService(
            $client,
            new ArrayAdapter(),
            'https://cookbook.test',
            self::USERNAME,
            self::PASSWORD,
            'v1',
            $logger ?? new class extends AbstractLogger {
                public function log($level, $message, array $context = []): void
                {
                }
            },
        );

        return [$service, $client];
    }

    private static function json(array $payload, int $status = 200): MockResponse
    {
        return new MockResponse(json_encode($payload, JSON_THROW_ON_ERROR), ['http_code' => $status]);
    }

    public function testItAuthenticatesThenReturnsTheHydraPayload(): void
    {
        [$service, $client] = $this->service([
            self::json(['token' => 'jwt-1']),
            self::json(['member' => [['id' => 1, 'title' => 'Tarte']]]),
        ]);

        $data = $service->getRecipes();

        self::assertSame([['id' => 1, 'title' => 'Tarte']], $data['member']);
        self::assertSame(2, $client->getRequestsCount(), 'Un login puis un appel métier.');
    }

    /**
     * The core of the contract: a 401 must drop the cached JWT, re-authenticate
     * and replay the request — exactly once.
     */
    public function testItRetriesOnceAfterA401AndDropsTheCachedToken(): void
    {
        [$service, $client] = $this->service([
            self::json(['token' => 'jwt-expire']),
            self::json(['error' => 'Expired JWT Token'], 401),
            self::json(['token' => 'jwt-frais']),
            self::json(['member' => [['id' => 7]]]),
        ]);

        $data = $service->getRecipes();

        self::assertSame([['id' => 7]], $data['member']);
        self::assertSame(
            4,
            $client->getRequestsCount(),
            'login → 401 → nouveau login → rejeu : exactement 4 requêtes.'
        );
    }

    public function testItDoesNotRetryTwice(): void
    {
        [$service] = $this->service([
            self::json(['token' => 'jwt-1']),
            self::json(['error' => 'Expired JWT Token'], 401),
            self::json(['token' => 'jwt-2']),
            self::json(['error' => 'Expired JWT Token'], 401),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Cookbook API error 401/');

        $service->getRecipes();
    }

    /**
     * A server error upstream is an outage: the caller has to tell it apart to
     * degrade the page (audit finding S8).
     */
    public function testAServerErrorIsReportedAsAnUnavailability(): void
    {
        [$service] = $this->service([
            self::json(['token' => 'jwt-1']),
            self::json(['error' => 'boom'], 500),
        ]);

        $this->expectException(CookbookUnavailableException::class);
        $this->expectExceptionMessageMatches('/Cookbook API error 500/');

        $service->getCategories();
    }

    public function testAnUnreachableApiIsReportedAsAnUnavailability(): void
    {
        [$service] = $this->service([
            static fn () => throw new TransportException('Connection refused'),
        ]);

        $this->expectException(CookbookUnavailableException::class);

        $service->getRecipes();
    }

    /**
     * Unavailability stops at 5xx: a client error stays a bug in our own code
     * and must not pass itself off as an outage.
     */
    public function testAClientErrorIsNotAnUnavailability(): void
    {
        [$service] = $this->service([
            self::json(['token' => 'jwt-1']),
            self::json(['error' => 'not found'], 404),
        ]);

        try {
            $service->getRecipe(404);
            self::fail('A 404 must raise an exception.');
        } catch (\RuntimeException $e) {
            self::assertNotInstanceOf(CookbookUnavailableException::class, $e);
            self::assertMatchesRegularExpression('/Cookbook API error 404/', $e->getMessage());
        }
    }

    public function testTheTokenIsReusedAcrossCalls(): void
    {
        [$service, $client] = $this->service([
            self::json(['token' => 'jwt-1']),
            self::json(['member' => []]),
            self::json(['member' => []]),
        ]);

        $service->getCategories();
        $service->getCategories();

        self::assertSame(3, $client->getRequestsCount(), 'Un seul login pour deux appels.');
    }

    /**
     * Guard on global rule 6: neither the credentials nor the JWT may ever end
     * up in the logs.
     */
    public function testItNeverLogsCredentialsOrToken(): void
    {
        $spy = new class extends AbstractLogger {
            /** @var list<string> */
            private array $records = [];

            public function log($level, $message, array $context = []): void
            {
                $this->records[] = $message.' '.json_encode($context);
            }

            public function dump(): string
            {
                return implode("\n", $this->records);
            }
        };

        [$service] = $this->service([
            self::json(['token' => 'jwt-secret-value']),
            self::json(['member' => []]),
        ], $spy);

        $service->getRecipes(1, 10, ['title' => 'tarte']);

        $everythingLogged = $spy->dump();
        self::assertNotSame('', $everythingLogged, 'Le service doit bien journaliser quelque chose.');
        self::assertStringNotContainsString(self::PASSWORD, $everythingLogged);
        self::assertStringNotContainsString(self::USERNAME, $everythingLogged);
        self::assertStringNotContainsString('jwt-secret-value', $everythingLogged);
    }
}
