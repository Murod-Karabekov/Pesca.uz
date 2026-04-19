<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Corporate partnership inquiry requests (B2B)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE corporate_partnership_request (
            id INT AUTO_INCREMENT NOT NULL,
            submitted_by_user_id INT DEFAULT NULL,
            organization_name VARCHAR(255) NOT NULL,
            contact_full_name VARCHAR(255) NOT NULL,
            email VARCHAR(180) NOT NULL,
            phone VARCHAR(30) NOT NULL,
            address LONGTEXT NOT NULL,
            stir VARCHAR(64) DEFAULT NULL,
            additional_notes LONGTEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_corporate_created (created_at),
            INDEX IDX_corporate_submitted_user (submitted_by_user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE corporate_partnership_request ADD CONSTRAINT FK_corporate_submitted_user FOREIGN KEY (submitted_by_user_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE corporate_partnership_request DROP FOREIGN KEY FK_corporate_submitted_user');
        $this->addSql('DROP TABLE corporate_partnership_request');
    }
}
