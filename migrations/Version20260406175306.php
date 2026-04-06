<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260406175306 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE announcement ADD is_banner TINYINT NOT NULL, ADD delay_seconds INT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE bonus_transaction CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE bonus_transaction RENAME INDEX idx_bonus_transaction_user TO IDX_487D3D7DA76ED395');
        $this->addSql('ALTER TABLE bonus_transaction RENAME INDEX idx_bonus_transaction_source_user TO IDX_487D3D7DEEB16BFD');
        $this->addSql('ALTER TABLE bonus_wallet CHANGE balance balance NUMERIC(12, 2) NOT NULL, CHANGE total_earned total_earned NUMERIC(12, 2) NOT NULL, CHANGE total_spent total_spent NUMERIC(12, 2) NOT NULL, CHANGE total_withdrawn total_withdrawn NUMERIC(12, 2) NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE bonus_wallet RENAME INDEX uniq_bonus_wallet_user TO UNIQ_3362D8CEA76ED395');
        $this->addSql('ALTER TABLE customer_order CHANGE approved_at approved_at DATETIME DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE customer_order RENAME INDEX idx_6aa5c982a76ed395 TO IDX_3B1CE6A3A76ED395');
        $this->addSql('ALTER TABLE customer_order RENAME INDEX idx_6aa5c9829bd0f3d8 TO IDX_3B1CE6A3F148197B');
        $this->addSql('ALTER TABLE membership_plan CHANGE can_withdraw can_withdraw TINYINT NOT NULL, CHANGE has_finance_section has_finance_section TINYINT NOT NULL, CHANGE interface_type interface_type VARCHAR(30) NOT NULL, CHANGE clothing_count clothing_count INT NOT NULL, CHANGE has_design has_design TINYINT NOT NULL, CHANGE is_active is_active TINYINT NOT NULL, CHANGE sort_order sort_order INT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE membership_plan RENAME INDEX uniq_membership_plan_slug TO UNIQ_A6656EB6989D9B62');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY `FK_52EA1F098D9F6D38`');
        $this->addSql('DROP INDEX IDX_52EA1F098D9F6D38 ON order_item');
        $this->addSql('DROP INDEX IDX_52EA1F094584665A ON order_item');
        $this->addSql('ALTER TABLE order_item CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F098D9F6D38 FOREIGN KEY (order_id) REFERENCES customer_order (id)');
        $this->addSql('ALTER TABLE referral_link CHANGE is_active is_active TINYINT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE referral_link RENAME INDEX idx_referral_link_referrer TO IDX_25FEEC23798C22DB');
        $this->addSql('ALTER TABLE referral_link RENAME INDEX idx_referral_link_referred TO IDX_25FEEC23CFE2A98');
        $this->addSql('ALTER TABLE user CHANGE current_plan current_plan VARCHAR(20) NOT NULL, CHANGE general_balance general_balance NUMERIC(12, 2) NOT NULL');
        $this->addSql('ALTER TABLE user RENAME INDEX uniq_user_referral_code TO UNIQ_8D93D6496447454A');
        $this->addSql('ALTER TABLE user RENAME INDEX idx_user_referred_by TO IDX_8D93D649758C8114');
        $this->addSql('ALTER TABLE user_membership CHANGE status status VARCHAR(20) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE user_membership RENAME INDEX idx_user_membership_user TO IDX_21981469A76ED395');
        $this->addSql('ALTER TABLE user_membership RENAME INDEX idx_user_membership_plan TO IDX_21981469E899029B');
        $this->addSql('DROP INDEX idx_wallet_topup_status ON wallet_topup_request');
        $this->addSql('DROP INDEX idx_wallet_topup_created_at ON wallet_topup_request');
        $this->addSql('ALTER TABLE wallet_topup_request CHANGE processed_at processed_at DATETIME DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE wallet_topup_request RENAME INDEX idx_f43803dda76ed395 TO IDX_2AC2E968A76ED395');
        $this->addSql('ALTER TABLE withdrawal CHANGE status status VARCHAR(20) NOT NULL, CHANGE processed_at processed_at DATETIME DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE withdrawal RENAME INDEX idx_withdrawal_user TO IDX_6D2D3B45A76ED395');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE announcement DROP is_banner, DROP delay_seconds, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE bonus_transaction CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE bonus_transaction RENAME INDEX idx_487d3d7deeb16bfd TO IDX_bonus_transaction_source_user');
        $this->addSql('ALTER TABLE bonus_transaction RENAME INDEX idx_487d3d7da76ed395 TO IDX_bonus_transaction_user');
        $this->addSql('ALTER TABLE bonus_wallet CHANGE balance balance NUMERIC(12, 2) DEFAULT \'0.00\' NOT NULL, CHANGE total_earned total_earned NUMERIC(12, 2) DEFAULT \'0.00\' NOT NULL, CHANGE total_spent total_spent NUMERIC(12, 2) DEFAULT \'0.00\' NOT NULL, CHANGE total_withdrawn total_withdrawn NUMERIC(12, 2) DEFAULT \'0.00\' NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE bonus_wallet RENAME INDEX uniq_3362d8cea76ed395 TO UNIQ_bonus_wallet_user');
        $this->addSql('ALTER TABLE customer_order CHANGE approved_at approved_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE customer_order RENAME INDEX idx_3b1ce6a3a76ed395 TO IDX_6AA5C982A76ED395');
        $this->addSql('ALTER TABLE customer_order RENAME INDEX idx_3b1ce6a3f148197b TO IDX_6AA5C9829BD0F3D8');
        $this->addSql('ALTER TABLE membership_plan CHANGE can_withdraw can_withdraw TINYINT DEFAULT 0 NOT NULL, CHANGE has_finance_section has_finance_section TINYINT DEFAULT 0 NOT NULL, CHANGE interface_type interface_type VARCHAR(30) DEFAULT \'basic\' NOT NULL, CHANGE clothing_count clothing_count INT DEFAULT 0 NOT NULL, CHANGE has_design has_design TINYINT DEFAULT 0 NOT NULL, CHANGE is_active is_active TINYINT DEFAULT 1 NOT NULL, CHANGE sort_order sort_order INT DEFAULT 0 NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE membership_plan RENAME INDEX uniq_a6656eb6989d9b62 TO UNIQ_membership_plan_slug');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F098D9F6D38');
        $this->addSql('ALTER TABLE order_item CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT `FK_52EA1F098D9F6D38` FOREIGN KEY (order_id) REFERENCES customer_order (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_52EA1F098D9F6D38 ON order_item (order_id)');
        $this->addSql('CREATE INDEX IDX_52EA1F094584665A ON order_item (product_id)');
        $this->addSql('ALTER TABLE referral_link CHANGE is_active is_active TINYINT DEFAULT 1 NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE referral_link RENAME INDEX idx_25feec23798c22db TO IDX_referral_link_referrer');
        $this->addSql('ALTER TABLE referral_link RENAME INDEX idx_25feec23cfe2a98 TO IDX_referral_link_referred');
        $this->addSql('ALTER TABLE `user` CHANGE current_plan current_plan VARCHAR(20) DEFAULT \'free\' NOT NULL, CHANGE general_balance general_balance NUMERIC(12, 2) DEFAULT \'0.00\' NOT NULL');
        $this->addSql('ALTER TABLE `user` RENAME INDEX uniq_8d93d6496447454a TO UNIQ_user_referral_code');
        $this->addSql('ALTER TABLE `user` RENAME INDEX idx_8d93d649758c8114 TO IDX_user_referred_by');
        $this->addSql('ALTER TABLE user_membership CHANGE status status VARCHAR(20) DEFAULT \'active\' NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user_membership RENAME INDEX idx_21981469a76ed395 TO IDX_user_membership_user');
        $this->addSql('ALTER TABLE user_membership RENAME INDEX idx_21981469e899029b TO IDX_user_membership_plan');
        $this->addSql('ALTER TABLE wallet_topup_request CHANGE processed_at processed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX idx_wallet_topup_status ON wallet_topup_request (status)');
        $this->addSql('CREATE INDEX idx_wallet_topup_created_at ON wallet_topup_request (created_at)');
        $this->addSql('ALTER TABLE wallet_topup_request RENAME INDEX idx_2ac2e968a76ed395 TO IDX_F43803DDA76ED395');
        $this->addSql('ALTER TABLE withdrawal CHANGE status status VARCHAR(20) DEFAULT \'pending\' NOT NULL, CHANGE processed_at processed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE withdrawal RENAME INDEX idx_6d2d3b45a76ed395 TO IDX_withdrawal_user');
    }
}
