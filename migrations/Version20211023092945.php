<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds "slug" field into "conference" table
 */
final class Version20211023092945 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds "slug" field into "conference" table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "conference" ADD "slug" VARCHAR(255)');
        $this->addSql('UPDATE "conference" SET "slug" = concat(lower("city"), \'-\', "year")');
        $this->addSql('ALTER TABLE "conference" ALTER COLUMN "slug" SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "conference" DROP "slug"');
    }
}
