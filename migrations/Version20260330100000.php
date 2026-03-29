<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260330100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop membership_level column from product table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product DROP COLUMN membership_level');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE product ADD membership_level VARCHAR(20) NOT NULL DEFAULT 'free'");
    }
}
