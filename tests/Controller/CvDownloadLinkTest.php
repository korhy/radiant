<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\SiteContent;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The footer's CV entry is the only one driven by the database. GitHub and LinkedIn stay written
 * in the template, so every case here also asserts they are left alone.
 */
final class CvDownloadLinkTest extends WebTestCase
{
    private const STORED_NAME = 'cv-abc123-1.pdf';
    private const ORIGINAL_NAME = 'Mon_CV_2026.pdf';

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

    private function seedCv(?string $storedName, ?string $originalName): void
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $em->persist(
            (new SiteContent())
                ->setAboutText('Peu importe le texte ici.')
                ->setCvFileName($storedName)
                ->setCvOriginalName($originalName)
        );
        $em->flush();
    }

    public function testThePublishedCvIsLinkedWithItsOriginalName(): void
    {
        $this->seedCv(self::STORED_NAME, self::ORIGINAL_NAME);

        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();

        $link = $crawler->filter('a[download]');

        self::assertCount(1, $link);
        self::assertStringContainsString(self::STORED_NAME, (string) $link->attr('href'));
        self::assertSame(self::ORIGINAL_NAME, $link->attr('download'));
        self::assertStringContainsString('Télécharger mon CV', $link->text());
    }

    /**
     * FR-013: no CV means no link at all — not a disabled one, and nothing reachable by keyboard.
     *
     * @dataProvider missingCvProvider
     */
    public function testWithoutACvNoDownloadLinkIsRendered(?string $storedName, bool $seedRow): void
    {
        if ($seedRow) {
            $this->seedCv($storedName, null);
        }

        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('a[download]'));
        self::assertCount(0, $crawler->filter('a[href*=".pdf"]'));
    }

    /**
     * @return iterable<string, array{?string, bool}>
     */
    public static function missingCvProvider(): iterable
    {
        yield 'aucun fichier enregistré' => [null, true];
        yield 'aucune ligne en base' => [null, false];
    }

    /**
     * FR-020: the two hardcoded links are a non-regression, whatever the CV's state.
     *
     * @dataProvider cvStateProvider
     */
    public function testGithubAndLinkedinAreUntouched(bool $withCv): void
    {
        $this->seedCv($withCv ? self::STORED_NAME : null, $withCv ? self::ORIGINAL_NAME : null);

        $crawler = $this->client->request('GET', '/');

        self::assertCount(1, $crawler->filter('a[href="https://github.com/korhy"]'));
        self::assertCount(1, $crawler->filter('a[href^="https://www.linkedin.com/in/"]'));
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function cvStateProvider(): iterable
    {
        yield 'avec CV' => [true];
        yield 'sans CV' => [false];
    }
}
