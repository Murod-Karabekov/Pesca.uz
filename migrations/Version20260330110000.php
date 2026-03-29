<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260330110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop has_photosession column from membership_plan table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE membership_plan DROP COLUMN has_photosession');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE membership_plan ADD has_photosession TINYINT(1) NOT NULL DEFAULT 0');
    }
}
