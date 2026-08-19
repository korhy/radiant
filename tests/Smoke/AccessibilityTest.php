<?php

declare(strict_types=1);

namespace App\Tests\Smoke;

use App\Entity\App;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * L'accessibilité est un critère d'acceptation du projet, pas une finition.
 * Ces tests figent les correctifs de l'étape 2 : sans eux, chacun se déferait
 * à la première retouche de gabarit sans que rien ne le signale.
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
     * A1 — les 15 tuiles étaient des <div> pilotées par un clic délégué : le jeu
     * était strictement inutilisable sans souris.
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
     * A11 — les déplacements et la victoire doivent être annoncés.
     */
    public function testTaquinExposesALiveRegion(): void
    {
        $crawler = $this->client->request('GET', '/app/taquin');

        self::assertSame(1, $crawler->filter('[data-taquin-target="status"][role="status"]')->count());
    }

    /**
     * A3 — « Bravo ! » et « Perdu ! » n'étaient jamais annoncés.
     */
    public function testMotusMessageIsALiveRegion(): void
    {
        $crawler = $this->client->request('GET', '/app/motus');

        self::assertSame(1, $crawler->filter('[data-motus-target="message"][role="status"]')->count());
    }

    /**
     * A5 + A6 — le tiroir n'avait aucun nom accessible, aucune sémantique
     * d'onglets, et restait dans l'ordre de tabulation une fois fermé.
     */
    public function testBehindTheScenesDrawerIsAccessible(): void
    {
        $this->seedApp('taquin');

        $crawler = $this->client->request('GET', '/app/taquin');

        self::assertSame(
            1,
            $crawler->filter('[data-app-detail-drawer-target="trigger"][aria-label][aria-expanded="false"]')->count(),
            'Le déclencheur doit être nommé et annoncer son état.'
        );
        self::assertSame(
            1,
            $crawler->filter('[data-app-detail-drawer-target="drawer"][role="dialog"][inert]')->count(),
            'Fermé, le tiroir doit être inerte.'
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
     * A8 — quatre <a> sans href servaient uniquement à colorer du texte.
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
     * A10 — les champs recâblés à la main n'affichaient aucune erreur, et le
     * <textarea value="..."> invalide perdait le message à chaque échec.
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

        // Symfony renvoie 422 sur un formulaire invalide depuis la 6.2 : le
        // formulaire se réaffiche avec ses erreurs, il ne redirige pas.
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
}
