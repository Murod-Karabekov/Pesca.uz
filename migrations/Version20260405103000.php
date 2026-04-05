<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260405103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create wallet_topup_request table for user top-up requests and admin approval flow';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE wallet_topup_request (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, amount NUMERIC(12, 2) NOT NULL, payment_method VARCHAR(50) NOT NULL, payer_name VARCHAR(255) NOT NULL, payer_phone VARCHAR(20) NOT NULL, payment_reference VARCHAR(120) DEFAULT NULL, comment LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, admin_note LONGTEXT DEFAULT NULL, processed_at DATETIME DEFAULT NULL COMMENT "(DC2Type:datetime_immutable)", created_at DATETIME NOT NULL COMMENT "(DC2Type:datetime_immutable)", INDEX IDX_F43803DDA76ED395 (user_id), INDEX idx_wallet_topup_status (status), INDEX idx_wallet_topup_created_at (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wallet_topup_request ADD CONSTRAINT FK_F43803DDA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wallet_topup_request DROP FOREIGN KEY FK_F43803DDA76ED395');
        $this->addSql('DROP TABLE wallet_topup_request');
    }
}
