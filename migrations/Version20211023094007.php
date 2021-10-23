<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211023094007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds unique index on "slug" field of "conference" table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX "uniq_idx_conference_slug" ON "conference" ("slug")');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX IF EXISTS "uniq_idx_conference_slug"');
    }
}
