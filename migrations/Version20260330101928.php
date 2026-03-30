<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260330101928 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Manage app details in a single table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app (id SERIAL NOT NULL, slug VARCHAR(50) NOT NULL, label VARCHAR(100) NOT NULL, route VARCHAR(100) NOT NULL, position INT DEFAULT NULL, tech_stack JSON DEFAULT NULL, challenges JSON DEFAULT NULL, improvements JSON DEFAULT NULL, resources JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C96E70CF989D9B62 ON app (slug)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app');
    }
}
