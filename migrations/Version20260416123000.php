<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove virtual try-on garment PNG URL column from product table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product DROP COLUMN garment_png_url');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product ADD garment_png_url VARCHAR(2048) DEFAULT NULL');
    }
}
