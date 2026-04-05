<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260405113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create announcement table for home page managed announcements';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE announcement (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, body LONGTEXT NOT NULL, media_type VARCHAR(20) NOT NULL, media_url VARCHAR(1000) DEFAULT NULL, cta_label VARCHAR(80) DEFAULT NULL, cta_url VARCHAR(1000) DEFAULT NULL, is_active TINYINT(1) NOT NULL, sort_order INT NOT NULL, created_at DATETIME NOT NULL COMMENT "(DC2Type:datetime_immutable)", updated_at DATETIME NOT NULL COMMENT "(DC2Type:datetime_immutable)", INDEX idx_announcement_active_sort (is_active, sort_order), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE announcement');
    }
}
