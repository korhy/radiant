<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * The recipe card has a single definition, used by the first screen and by the
 * infinite scroll alike (audit findings DU1 and S3).
 */
final class CookbookCardTest extends WebTestCase
{
    /** @var list<array<string, mixed>> */
    private const RECIPES = [
        ['id' => 1, 'title' => 'Tarte aux pommes', 'thumbnail' => 'https://cookbook.test/tarte.jpg', 'category' => ['id' => 2, 'name' => 'Dessert'], 'duration' => 45],
        ['id' => 2, 'title' => 'Salade tiède', 'thumbnail' => null, 'category' => null, 'duration' => null],
    ];

    /**
     * Keyed on the URL, not on call order: the JWT is cached across tests, so
     * the login request may or may not happen.
     *
     * @param list<array<string, mixed>> $recipes
     */
    private function mockApi(array $recipes): void
    {
        static::getContainer()->set('http_client', new MockHttpClient(
            static function (string $method, string $url) use ($recipes): MockResponse {
                $payload = match (true) {
                    str_contains($url, 'login_check') => ['token' => 'jwt'],
                    str_contains($url, '/categories') => ['member' => []],
                    default => ['member' => $recipes],
                };

                return new MockResponse(json_encode($payload, JSON_THROW_ON_ERROR));
            },
            'https://cookbook.test'
        ));
    }

    /**
     * @return list<string>
     */
    private static function cardsOf(string $html): array
    {
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="UTF-8">'.$html, \LIBXML_NOERROR | \LIBXML_NOWARNING);

        $cards = [];
        foreach ((new \DOMXPath($document))->query('//*[@data-slot="recipe-card"]') as $node) {
            $cards[] = (string) preg_replace('/\s+/', ' ', trim((string) $document->saveHTML($node)));
        }

        return $cards;
    }

    private function payloadOf(string $path): array
    {
        $this->client->request('GET', $path);
        self::assertResponseIsSuccessful();

        return json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        // Two requests per test hit the same mocked API: without this, the kernel
        // reboots between them and the mocked http_client is lost.
        $this->client->disableReboot();
    }

    /**
     * The guard of the whole feature: it fails the moment a card is rendered by
     * the browser again. The other tests would stay green in that case.
     */
    public function testBothRenderPathsProduceTheSameCard(): void
    {
        $this->mockApi(self::RECIPES);

        $this->client->request('GET', '/app/cookbook');
        self::assertResponseIsSuccessful();
        $fromFirstScreen = self::cardsOf((string) $this->client->getResponse()->getContent());

        $fromScroll = self::cardsOf($this->payloadOf('/app/cookbook/recipes')['html'] ?? '');

        self::assertCount(2, $fromFirstScreen, 'Le premier écran doit rendre les deux cartes.');
        self::assertSame(
            $fromFirstScreen,
            $fromScroll,
            'Les deux chemins de rendu ont divergé : la carte a plus d\'une définition.'
        );
    }

    public function testTheEndpointServesRenderedMarkup(): void
    {
        $this->mockApi(self::RECIPES);

        $payload = $this->payloadOf('/app/cookbook/recipes');

        self::assertArrayNotHasKey('recipes', $payload, 'Le point d\'accès ne doit plus servir de données brutes.');
        self::assertStringContainsString('/app/cookbook/recipe/1', $payload['html']);
        self::assertFalse($payload['empty']);
    }
}
