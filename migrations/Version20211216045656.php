<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211216045656 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds admin user into DB';
    }

    public function up(Schema $schema): void
    {
        $insert = <<<'SQL'
            INSERT INTO "admin" ("id", "username", "roles", "password")
            VALUES (NEXTVAL('admin_id_seq'), 'admin', '["ROLE_ADMIN"]', '$2y$13$xRosH2S.R5VWyGc6Ry4D.OzKy3L6emk3zhgcnAQJWnBNP6Eq4Qpo.')
            SQL;

        $this->addSql($insert);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM "admin" WHERE "username" = \'admin\'');
    }
}
