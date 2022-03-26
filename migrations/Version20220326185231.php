<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220326185231 extends AbstractMigration
{
    private string $insertTomsk = <<<'TOMSK'
        INSERT INTO "conference" ("id", "city", "year", "is_international", "slug")
        VALUES (nextval('conference_id_seq'), 'Tomsk', '2022', FALSE, 'tomsk-2022');
        TOMSK;

    private string $insertMoscow = <<<'MOSCOW'
        INSERT INTO "conference" ("id", "city", "year", "is_international", "slug")
        VALUES (nextval('conference_id_seq'), 'Moscow', '2021', TRUE, 'moscow-2021');
        MOSCOW;


    public function getDescription(): string
    {
        return 'Adds default conferences';
    }

    public function up(Schema $schema): void
    {
        $this->addSql($this->insertMoscow);
        $this->addSql($this->insertTomsk);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM "conference"');
        $this->addSql('ALTER SEQUENCE IF EXISTS "conference_id_seq" RESTART');
    }
}
