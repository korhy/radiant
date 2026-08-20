<?php

declare(strict_types=1);

namespace App\Tests\Smoke;

use App\Entity\App;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Accessibility is an acceptance criterion here, not polish. These tests pin the
 * fixes down: without them, a template edit would silently undo any of them.
 */
final class AccessibilityTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $tool = new SchemaTool($em);
        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);
    }

    /** Le tiroir n'est rendu que si la ligne `App` du slug existe. */
    private function seedApp(string $slug): void
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist((new App())->setSlug($slug)->setLabel(ucfirst($slug))->setRoute($slug)->setPosition(1));
        $em->flush();
    }

    /**
     * A1 — the 15 tiles must stay operable without a mouse.
     */
    public function testTaquinTilesAreRealButtons(): void
    {
        $crawler = $this->client->request('GET', '/app/taquin');

        $grid = '[aria-label="Grille du taquin"] ';

        self::assertSame(16, $crawler->filter($grid.'button')->count(), '16 cases, toutes actionnables au clavier.');
        self::assertSame(15, $crawler->filter($grid.'button.tile[aria-label^="Tuile"]')->count());
        self::assertSame(1, $crawler->filter($grid.'button.empty[disabled][aria-label="Case vide"]')->count());
    }

    /**
     * A11 — moves and the win must be announced.
     */
    public function testTaquinExposesALiveRegion(): void
    {
        $crawler = $this->client->request('GET', '/app/taquin');

        self::assertSame(1, $crawler->filter('[data-taquin-target="status"][role="status"]')->count());
    }

    /**
     * A3 — the end-of-game messages must reach assistive technology.
     */
    public function testMotusMessageIsALiveRegion(): void
    {
        $crawler = $this->client->request('GET', '/app/motus');

        self::assertSame(1, $crawler->filter('[data-motus-target="message"][role="status"]')->count());
    }

    /**
     * A12 + DU3 — the ⓘ help is a native `<details>`, reachable by mouse,
     * keyboard and touch alike. This pins the markup, not the styling: going
     * back to a hover-only tooltip breaks it.
     *
     * @dataProvider provideMiniAppsWithRules
     */
    public function testRulesHelpIsANativeDisclosure(string $path, int $expected): void
    {
        $crawler = $this->client->request('GET', $path);

        self::assertSame(
            $expected,
            $crawler->filter('details[data-slot="info-disclosure"]')->count(),
            'L\'aide doit passer par le composant InfoDisclosure.'
        );
        self::assertSame(
            $expected,
            $crawler->filter('details > summary[data-slot="info-disclosure-trigger"][aria-label]')->count(),
            'Chaque déclencheur doit être un <summary> nommé.'
        );

        $orphans = $crawler->filter('[data-slot="info-disclosure-panel"]')->reduce(
            static fn (Crawler $node): bool => 'details' !== $node->ancestors()->first()->nodeName()
        );

        self::assertSame(0, $orphans->count(), 'Un panneau d\'aide hors <details> n\'est plus activable au clavier.');
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function provideMiniAppsWithRules(): iterable
    {
        yield 'taquin' => ['/app/taquin', 1];
        // Motus en rend deux : le ⓘ en ligne du bureau, le bouton de barre du mobile.
        yield 'motus' => ['/app/motus', 2];
    }

    /**
     * A5 + A6 — the drawer is a native `<dialog>`: without the `open` attribute
     * it is neither rendered, nor focusable, nor exposed, which is what `inert`
     * used to achieve by hand. Its role and `aria-modal` come from the element.
     */
    public function testBehindTheScenesDrawerIsAccessible(): void
    {
        $this->seedApp('taquin');

        $crawler = $this->client->request('GET', '/app/taquin');

        self::assertSame(
            1,
            $crawler->filter('[data-dialog-target="trigger"][aria-label][aria-expanded="false"]')->count(),
            'Le déclencheur doit être nommé et annoncer son état.'
        );
        self::assertSame(
            1,
            $crawler->filter('dialog:not([open])')->count(),
            'Le panneau doit être un <dialog> natif, fermé au rendu.'
        );

        $labelledBy = $crawler->filter('[aria-labelledby]')->reduce(
            static fn (Crawler $node): bool => $node->filter('dialog')->count() > 0
        );

        self::assertSame(1, $labelledBy->count(), 'Le panneau doit porter un nom accessible.');
        self::assertSame(
            1,
            $crawler->filter('#'.$labelledBy->attr('aria-labelledby'))->count(),
            'Le nom accessible doit pointer sur un élément qui existe.'
        );

        self::assertSame(4, $crawler->filter('[role="tablist"] [role="tab"][aria-controls]')->count());
        self::assertSame(4, $crawler->filter('[role="tabpanel"][aria-labelledby]')->count());
    }

    /**
     * A7 — le portrait et les vignettes de projets n'avaient pas d'attribut alt.
     */
    public function testEveryImageCarriesAnAltAttribute(): void
    {
        $crawler = $this->client->request('GET', '/');

        self::assertGreaterThan(0, $crawler->filter('img')->count());

        $withoutAlt = array_filter($crawler->filter('img')->each(
            static fn (Crawler $img): ?string => null === $img->attr('alt') ? $img->attr('src') : null
        ));

        self::assertSame([], array_values($withoutAlt), 'Ces images n\'ont pas d\'attribut alt.');
    }

    /**
     * A8 — coloured text must not be marked up as anchors without href.
     */
    public function testHomepageHasNoAnchorWithoutHref(): void
    {
        $crawler = $this->client->request('GET', '/');

        self::assertSame(0, $crawler->filter('a:not([href])')->count());
    }

    /**
     * A9 — le label du mot de passe pointait sur un id inexistant.
     */
    public function testLoginLabelsPointToRealFields(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $targets = $crawler->filter('label[for]')->each(static fn (Crawler $l): ?string => $l->attr('for'));
        self::assertNotEmpty($targets);

        foreach ($targets as $for) {
            self::assertSame(
                1,
                $crawler->filter(sprintf('#%s', $for))->count(),
                sprintf('Le label pointe sur un id inexistant : %s', $for)
            );
        }
    }

    /**
     * A10 — a rejected submission must show its errors and keep what was typed.
     */
    public function testContactFormShowsErrorsAndKeepsWhatWasTyped(): void
    {
        $crawler = $this->client->request('GET', '/contact');

        $form = $crawler->selectButton('Envoyer')->form([
            'contact[name]' => 'Clément',
            'contact[email]' => 'pas-une-adresse',
            'contact[message]' => 'trop court',
        ]);

        $crawler = $this->client->submit($form);

        // Symfony answers 422 on an invalid form since 6.2: the form is
        // re-rendered with its errors instead of redirecting.
        self::assertResponseStatusCodeSame(422, 'Le formulaire doit se réafficher, pas rediriger.');
        self::assertGreaterThan(
            0,
            $crawler->filter('form ul li')->count(),
            'Une erreur de validation doit être visible.'
        );
        self::assertStringContainsString(
            'trop court',
            $crawler->filter('#contact_message')->text(),
            'Le message saisi doit survivre au réaffichage.'
        );
    }

    /**
     * The Cookbook pages talk to an external API. Dispatching on the URL rather
     * than on call order keeps the stub correct whether or not the JWT is still
     * in the cache from an earlier request.
     */
    private function mockCookbookApi(): void
    {
        $recipe = [
            'id' => 42,
            'title' => 'Tarte aux pommes',
            'thumbnail' => null,
            'duration' => 45,
            'description' => 'Une tarte.',
        ];

        static::getContainer()->set('http_client', new MockHttpClient(
            static function (string $method, string $url) use ($recipe): MockResponse {
                if (str_contains($url, '/api/login_check')) {
                    return new MockResponse(json_encode(['token' => 'jwt-test'], JSON_THROW_ON_ERROR));
                }

                if (preg_match('#/recipes/\d+$#', $url)) {
                    return new MockResponse(json_encode($recipe, JSON_THROW_ON_ERROR));
                }

                if (str_contains($url, '/categories')) {
                    return new MockResponse(json_encode(['member' => [['id' => 1, 'name' => 'Dessert']]], JSON_THROW_ON_ERROR));
                }

                return new MockResponse(json_encode(['member' => [$recipe]], JSON_THROW_ON_ERROR));
            },
            'https://cookbook.test'
        ));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function providePublicPages(): iterable
    {
        yield 'accueil' => ['/'];
        yield 'taquin' => ['/app/taquin'];
        yield 'motus' => ['/app/motus'];
        yield 'cookbook' => ['/app/cookbook'];
        yield 'recette' => ['/app/cookbook/recipe/42'];
        yield 'contact' => ['/contact'];
        yield 'mentions légales' => ['/mentions-legales'];
        yield 'connexion' => ['/login'];
    }

    /**
     * Every page needs exactly one <main>, or the content sits outside any
     * landmark and skip-to-content is impossible. base.html.twig provides it;
     * a template overriding `body` must not add a second one.
     *
     * @dataProvider providePublicPages
     */
    public function testEveryPublicPageHasExactlyOneMainLandmark(string $path): void
    {
        $this->mockCookbookApi();

        $crawler = $this->client->request('GET', $path);

        self::assertResponseIsSuccessful();
        self::assertSame(1, $crawler->filter('main')->count(), 'Une page porte un <main>, et un seul.');
    }

    /**
     * @dataProvider providePublicPages
     */
    public function testEveryPublicPageHasExactlyOneLevelOneHeading(string $path): void
    {
        $this->mockCookbookApi();

        $crawler = $this->client->request('GET', $path);

        self::assertResponseIsSuccessful();
        self::assertSame(1, $crawler->filter('h1')->count(), 'Une page porte un <h1>, et un seul.');
    }

    /**
     * The Cookbook navbar is shared by the list and a recipe page. Turning its
     * title into a heading unconditionally would give a recipe an <h1>
     * competing with the dish's own name — these two pin which one wins where.
     * One request per test: the client reboots the kernel between requests,
     * which would throw away the stubbed HTTP client.
     */
    public function testTheCookbookListTitleIsItsLevelOneHeading(): void
    {
        $this->mockCookbookApi();

        $crawler = $this->client->request('GET', '/app/cookbook');

        self::assertResponseIsSuccessful();
        self::assertSame('Mon livre de recettes', trim($crawler->filter('h1')->text()));
    }

    public function testARecipePageUsesTheDishNameAsItsLevelOneHeading(): void
    {
        $this->mockCookbookApi();

        $crawler = $this->client->request('GET', '/app/cookbook/recipe/42');

        self::assertResponseIsSuccessful();
        self::assertSame('Tarte aux pommes', trim($crawler->filter('h1')->text()));
    }
}
