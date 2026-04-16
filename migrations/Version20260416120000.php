<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add garmentPngUrl column to product table for virtual try-on feature';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product ADD garment_png_url VARCHAR(2048) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product DROP COLUMN garment_png_url');
    }
}
