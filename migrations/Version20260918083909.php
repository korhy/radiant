<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates the single-row table holding the editable "About" paragraph and the published CV,
 * then seeds it with what the templates carried until now.
 *
 * Runs unattended on every green deploy: DDL and one INSERT, nothing destructive, no filesystem
 * access. The seeded `cv_file_name` points at the legacy PDF still tracked in the repository —
 * see specs/008-editable-about-cv/research.md, decision R4.
 */
final class Version20260918083909 extends AbstractMigration
{
    private const ABOUT_TEXT = 'Développeur Web expérimenté, j’ai consolidé mon expertise en concevant des architectures **back-end** et **e-commerce** robustes avec PHP et Elasticsearch. En 2025, j’ai complété mon profil par une **certification Data Science & IA**, que j’applique aujourd’hui en tant que **Teacher Assistant en Data Science et Développement Web** au Wagon, et sur mes projets personnels. Mon objectif : concevoir des produits web **intelligents**, performants et centrés sur la donnée.';

    private const LEGACY_CV = 'CV_Clement_BOUDINEL_Fullstack_PHP-Symfony.pdf';

    public function getDescription(): string
    {
        return 'Add site_content, the single row backing the About section and the CV download';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE site_content (id SERIAL NOT NULL, about_text TEXT DEFAULT NULL, cv_file_name VARCHAR(255) DEFAULT NULL, cv_original_name VARCHAR(255) DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN site_content.updated_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql(
            'INSERT INTO site_content (about_text, cv_file_name, cv_original_name, updated_at) VALUES (?, ?, ?, ?)',
            [
                self::ABOUT_TEXT,
                self::LEGACY_CV,
                self::LEGACY_CV,
                (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ],
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE site_content');
    }
}
