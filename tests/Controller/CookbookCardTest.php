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

    /**
     * Audit finding S3: the client used to interpolate API fields into an HTML
     * string, `alt` included. Rendering server-side settles it by construction.
     */
    public function testRecipeFieldsAreEscaped(): void
    {
        $trapped = [[
            'id' => 9,
            'title' => 'L\'entrecôte "façon <chef>" <img src=x onerror=alert(1)>',
            // With a thumbnail on purpose: `alt` was the sharpest edge of S3.
            'thumbnail' => 'https://cookbook.test/x.jpg',
            'category' => ['id' => 1, 'name' => '<b>Plat</b>'],
            'duration' => 30,
        ]];

        $this->mockApi($trapped);

        $this->client->request('GET', '/app/cookbook');
        $firstScreen = (string) $this->client->getResponse()->getContent();
        $scrolled = $this->payloadOf('/app/cookbook/recipes')['html'];

        foreach (['premier écran' => $firstScreen, 'défilement' => $scrolled] as $path => $html) {
            self::assertStringNotContainsString('<img src=x', $html, sprintf('Balisage injecté par le %s.', $path));
            self::assertStringNotContainsString('<b>Plat</b>', $html, sprintf('Balisage injecté par le %s.', $path));
            self::assertStringContainsString('&lt;img src=x', $html, sprintf('Le titre piégé doit être échappé dans le %s.', $path));
            self::assertStringContainsString('alt=""', $html, sprintf('Le titre ne doit plus entrer dans alt= dans le %s.', $path));
        }
    }

    /**
     * The scroll path used to rebuild the recipe path by hand: a route change
     * would have broken half the cards, silently.
     */
    public function testTheCardLinkComesFromTheRouter(): void
    {
        $this->mockApi(self::RECIPES);

        $html = $this->payloadOf('/app/cookbook/recipes')['html'];

        $expected = static::getContainer()->get('router')->generate('cookbook_recipe', ['id' => 1]);
        self::assertStringContainsString(sprintf('href="%s"', $expected), $html);
    }

    public function testAnEmptyResultSetServesTheEmptyState(): void
    {
        $this->mockApi([]);

        $payload = $this->payloadOf('/app/cookbook/recipes');

        self::assertTrue($payload['empty']);
        self::assertStringContainsString('data-slot="empty"', $payload['html'], 'L\'état vide doit venir du serveur.');
        self::assertFalse($payload['hasNextPage']);
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
