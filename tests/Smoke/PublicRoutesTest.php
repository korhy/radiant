<?php

declare(strict_types=1);

namespace App\Tests\Smoke;

use App\Entity\App;
use App\Service\Motus\MotusService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Filet de sécurité minimal : chaque route publique doit rendre sans exception.
 *
 * Ces tests attrapent notamment les régressions du contrat Stream Deck — toute
 * ligne `App` dont le partiel `_icon_<slug>.html.twig` manque fait échouer la
 * page d'accueil, pas la page de la mini-app.
 */
final class PublicRoutesTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->resetSchema();
    }

    private function resetSchema(): void
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $metadata = $em->getMetadataFactory()->getAllMetadata();

        $tool = new SchemaTool($em);
        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);
    }

    /**
     * Une tuile réelle sur le Stream Deck, pour que la page d'accueil exerce
     * vraiment l'include dynamique `components/_icon_<slug>.html.twig`.
     */
    private function seedStreamDeckTile(string $slug, string $route): void
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $app = (new App())
            ->setSlug($slug)
            ->setLabel(ucfirst($slug))
            ->setRoute($route)
            ->setPosition(1);

        $em->persist($app);
        $em->flush();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function publicRouteProvider(): iterable
    {
        yield 'accueil' => ['/'];
        yield 'taquin' => ['/app/taquin'];
        yield 'motus' => ['/app/motus'];
        yield 'contact' => ['/contact'];
        yield 'mentions légales' => ['/mentions-legales'];
        yield 'connexion' => ['/login'];
    }

    /**
     * @dataProvider publicRouteProvider
     */
    public function testPublicRouteRespondsSuccessfully(string $path): void
    {
        $this->client->request('GET', $path);

        self::assertResponseIsSuccessful();
    }

    public function testHomepageRendersTheStreamDeckTileAndItsIconPartial(): void
    {
        $this->seedStreamDeckTile('taquin', 'taquin');

        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSame(
            1,
            $crawler->filter('a[href="/app/taquin"] svg')->count(),
            'La tuile doit rendre son partiel d\'icône.'
        );
    }

    /**
     * Chaque page porte désormais son propre <title> : le bloc `title` de
     * base.html.twig existe et les gabarits enfants le remplissent (constat F3).
     */
    public function testEachPageHasItsOwnTitle(): void
    {
        $titles = [];

        foreach (['/', '/app/taquin', '/app/motus', '/contact', '/mentions-legales'] as $path) {
            $crawler = $this->client->request('GET', $path);
            $titles[$path] = $crawler->filter('title')->text();
        }

        self::assertCount(
            count($titles),
            array_unique($titles),
            'Deux pages partagent le même <title> : le bloc title est probablement contourné.'
        );
    }

    public function testCookbookRendersWithoutTouchingTheNetwork(): void
    {
        static::getContainer()->set('http_client', new MockHttpClient([
            new MockResponse(json_encode(['token' => 'jwt-test'], JSON_THROW_ON_ERROR)),
            new MockResponse(json_encode(['member' => []], JSON_THROW_ON_ERROR)),
            new MockResponse(json_encode(['member' => []], JSON_THROW_ON_ERROR)),
        ], 'https://cookbook.test'));

        $this->client->request('GET', '/app/cookbook');

        self::assertResponseIsSuccessful();
    }

    /**
     * An unavailable external dependency must not break a portfolio page: the
     * list renders degraded, not as a 500 (audit finding S8).
     */
    public function testCookbookDegradesWhenTheApiIsUnreachable(): void
    {
        static::getContainer()->set('http_client', new MockHttpClient(
            static fn () => throw new TransportException('Connection refused'),
            'https://cookbook.test'
        ));

        $crawler = $this->client->request('GET', '/app/cookbook');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(
            'injoignable',
            $crawler->filter('#recipe-grid')->text(),
            'La page doit dire au visiteur que le service externe est en panne.'
        );
    }

    public function testTheCookbookJsonEndpointAnswers503WhenTheApiIsUnreachable(): void
    {
        static::getContainer()->set('http_client', new MockHttpClient(
            static fn () => throw new TransportException('Connection refused'),
            'https://cookbook.test'
        ));

        $this->client->request('GET', '/app/cookbook/recipes');

        self::assertResponseStatusCodeSame(Response::HTTP_SERVICE_UNAVAILABLE);

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('', $payload['html']);
        self::assertTrue($payload['unavailable']);
    }

    /**
     * Les endpoints JSON reçoivent des entrées non typées : c'est le point le
     * plus exposé depuis l'ajout de declare(strict_types=1).
     */
    public function testMotusGuessRejectsAWrongLength(): void
    {
        $this->client->request(
            'POST',
            '/app/motus/guess',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['guess' => 'A'], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testMotusGuessAcceptsAWellFormedGuess(): void
    {
        $length = mb_strlen(static::getContainer()->get(MotusService::class)->getWordOfTheDay());

        $this->client->request(
            'POST',
            '/app/motus/guess',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['guess' => str_repeat('A', $length)], JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount($length, $payload['result']);
        self::assertIsBool($payload['won']);
    }

    /**
     * Une entrée vide ne doit pas produire une 500 : le corps est du JSON libre.
     */
    public function testMotusGuessSurvivesAnEmptyBody(): void
    {
        $this->client->request('POST', '/app/motus/guess', server: ['CONTENT_TYPE' => 'application/json'], content: '');

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Seul `^/admin` est protégé par `access_control` : ce test verrouille le
     * fait que le back-office n'est pas atteignable anonymement.
     */
    public function testAdminIsNotReachableAnonymously(): void
    {
        $this->client->request('GET', '/admin');

        self::assertResponseStatusCodeSame(Response::HTTP_FOUND);
        self::assertResponseRedirects('/login');
    }
}
