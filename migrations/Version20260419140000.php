<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260419140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'SmartStyle mobil API: skan tarixi (smart_style_scan_history)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE smart_style_scan_history (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                profile_snapshot JSON NOT NULL,
                recommendations_snapshot JSON NOT NULL,
                photo_filename VARCHAR(255) DEFAULT NULL,
                INDEX idx_smartstyle_hist_user_created (user_id, created_at),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql('ALTER TABLE smart_style_scan_history ADD CONSTRAINT FK_SMARTSTYLE_HIST_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE smart_style_scan_history DROP FOREIGN KEY FK_SMARTSTYLE_HIST_USER');
        $this->addSql('DROP TABLE smart_style_scan_history');
    }
}
