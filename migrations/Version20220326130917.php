<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220326130917 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alter "state" field of "comment" table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "comment" ALTER COLUMN "state" TYPE VARCHAR(20)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "comment" ALTER COLUMN "state" TYPE VARCHAR(10)');
    }
}
