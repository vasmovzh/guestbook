<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211030112158 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds "state" field into "comment" table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "comment" ADD "state" VARCHAR(20)');
        $this->addSql('UPDATE "comment" SET "state" = \'published\'');
        $this->addSql('ALTER TABLE "comment" ALTER COLUMN "state" SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "comment" DROP IF EXISTS "state"');
    }
}
