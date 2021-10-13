<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates "sessions" table.
 */
final class Version20211013105035 extends AbstractMigration
{
    private string $createSql = <<<'SQL'
        CREATE TABLE "sessions"
        (
            "sess_id"       VARCHAR(128) NOT NULL PRIMARY KEY,
            "sess_data"     BYTEA        NOT NULL,
            "sess_lifetime" INTEGER      NOT NULL,
            "sess_time"     INTEGER      NOT NULL
        )
        SQL;

    public function getDescription(): string
    {
        return 'Creates "sessions" table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql($this->createSql);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS "sessions"');
    }
}
