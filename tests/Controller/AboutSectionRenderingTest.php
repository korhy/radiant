<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\SiteContent;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The About section is now driven by a database row, so the homepage has to survive every state
 * of it: text present, text hostile, text empty, row missing entirely.
 */
final class AboutSectionRenderingTest extends WebTestCase
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

    private function seedAboutText(?string $text): void
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $em->persist((new SiteContent())->setAboutText($text));
        $em->flush();
    }

    public function testTheStoredTextIsRendered(): void
    {
        $this->seedAboutText('Un texte de présentation bien à moi.');

        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(
            'Un texte de présentation bien à moi.',
            $crawler->filter('#about')->text(),
        );
    }

    public function testHighlightedExpressionsCarryTheBrandClass(): void
    {
        $this->seedAboutText('Développeur **Symfony** convaincu.');

        $crawler = $this->client->request('GET', '/');

        $highlighted = $crawler->filter('#about span.text-brand-fg');

        self::assertCount(1, $highlighted);
        self::assertSame('Symfony', $highlighted->text());
        self::assertStringNotContainsString('**', $crawler->filter('#about')->text());
    }

    /**
     * FR-006: nothing typed into the admin may become structure. The parser hands Twig plain
     * text, so the markup comes back escaped and the script never becomes an element.
     */
    public function testMarkupTypedIntoTheAdminIsEscaped(): void
    {
        $this->seedAboutText('<script>alert(1)</script> et <b>gras</b>');

        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('#about script'));
        self::assertCount(0, $crawler->filter('#about b'));
        self::assertStringContainsString(
            '&lt;script&gt;alert(1)&lt;/script&gt;',
            (string) $this->client->getResponse()->getContent(),
        );
    }

    /**
     * FR-009: the section and its jump link go together. Leaving the nav entry behind would point
     * it at an anchor that no longer exists.
     *
     * @dataProvider emptyAboutProvider
     */
    public function testAnEmptyAboutLeavesNoSectionAndNoJumpLink(?string $text, bool $seedRow): void
    {
        if ($seedRow) {
            $this->seedAboutText($text);
        }

        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('#about'));
        self::assertCount(0, $crawler->filter('a[href="#about"]'));
    }

    /**
     * @return iterable<string, array{?string, bool}>
     */
    public static function emptyAboutProvider(): iterable
    {
        yield 'texte null' => [null, true];
        yield 'texte vide' => ['', true];
        yield 'texte en blancs' => ['   ', true];
        yield 'aucune ligne en base' => [null, false];
    }
}
