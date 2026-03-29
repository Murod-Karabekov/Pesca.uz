<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Membership, Referral, Bonus tizimini qo'shish
 */
final class Version20260327000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add membership plans, referral links, bonus wallet, bonus transactions, withdrawals tables and update user/product';
    }

    public function up(Schema $schema): void
    {
        // 1. MembershipPlan jadvali
        $this->addSql('CREATE TABLE membership_plan (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(50) NOT NULL,
            slug VARCHAR(50) NOT NULL,
            price NUMERIC(12, 2) NOT NULL,
            product_referral_percent NUMERIC(5, 2) NOT NULL,
            plan_referral_percent NUMERIC(5, 2) NOT NULL,
            can_withdraw TINYINT(1) NOT NULL DEFAULT 0,
            has_finance_section TINYINT(1) NOT NULL DEFAULT 0,
            interface_type VARCHAR(30) NOT NULL DEFAULT \'basic\',
            clothing_count INT NOT NULL DEFAULT 0,
            has_design TINYINT(1) NOT NULL DEFAULT 0,
            has_photosession TINYINT(1) NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_membership_plan_slug (slug),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 2. UserMembership jadvali
        $this->addSql('CREATE TABLE user_membership (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            plan_id INT NOT NULL,
            paid_amount NUMERIC(12, 2) NOT NULL,
            payment_method VARCHAR(50) DEFAULT NULL,
            payment_transaction_id VARCHAR(255) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'active\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_user_membership_user (user_id),
            INDEX IDX_user_membership_plan (plan_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_user_membership_user FOREIGN KEY (user_id) REFERENCES `user` (id),
            CONSTRAINT FK_user_membership_plan FOREIGN KEY (plan_id) REFERENCES membership_plan (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 3. ReferralLink jadvali
        $this->addSql('CREATE TABLE referral_link (
            id INT AUTO_INCREMENT NOT NULL,
            referrer_id INT NOT NULL,
            referred_id INT NOT NULL,
            referrer_plan_at_time VARCHAR(20) NOT NULL,
            product_percent NUMERIC(5, 2) NOT NULL,
            plan_percent NUMERIC(5, 2) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_referral_link_referrer (referrer_id),
            INDEX IDX_referral_link_referred (referred_id),
            UNIQUE INDEX referrer_referred_unique (referrer_id, referred_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_referral_link_referrer FOREIGN KEY (referrer_id) REFERENCES `user` (id),
            CONSTRAINT FK_referral_link_referred FOREIGN KEY (referred_id) REFERENCES `user` (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 4. BonusWallet jadvali
        $this->addSql('CREATE TABLE bonus_wallet (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            balance NUMERIC(12, 2) NOT NULL DEFAULT 0,
            total_earned NUMERIC(12, 2) NOT NULL DEFAULT 0,
            total_spent NUMERIC(12, 2) NOT NULL DEFAULT 0,
            total_withdrawn NUMERIC(12, 2) NOT NULL DEFAULT 0,
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_bonus_wallet_user (user_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_bonus_wallet_user FOREIGN KEY (user_id) REFERENCES `user` (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 5. BonusTransaction jadvali
        $this->addSql('CREATE TABLE bonus_transaction (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            source_user_id INT DEFAULT NULL,
            type VARCHAR(30) NOT NULL,
            amount NUMERIC(12, 2) NOT NULL,
            description VARCHAR(500) DEFAULT NULL,
            source_order_id INT DEFAULT NULL,
            source_membership_id INT DEFAULT NULL,
            applied_percent NUMERIC(5, 2) DEFAULT NULL,
            referrer_plan_at_time VARCHAR(20) DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_bonus_transaction_user (user_id),
            INDEX IDX_bonus_transaction_source_user (source_user_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_bonus_transaction_user FOREIGN KEY (user_id) REFERENCES `user` (id),
            CONSTRAINT FK_bonus_transaction_source_user FOREIGN KEY (source_user_id) REFERENCES `user` (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 6. Withdrawal jadvali
        $this->addSql('CREATE TABLE withdrawal (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            amount NUMERIC(12, 2) NOT NULL,
            card_number VARCHAR(20) DEFAULT NULL,
            card_holder_name VARCHAR(255) DEFAULT NULL,
            method VARCHAR(50) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'pending\',
            admin_note LONGTEXT DEFAULT NULL,
            processed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_withdrawal_user (user_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_withdrawal_user FOREIGN KEY (user_id) REFERENCES `user` (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 7. User jadvaliga yangi ustunlar
        $this->addSql('ALTER TABLE `user` ADD referral_code VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD referred_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD current_plan VARCHAR(20) NOT NULL DEFAULT \'free\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_user_referral_code ON `user` (referral_code)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_user_referred_by FOREIGN KEY (referred_by_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_user_referred_by ON `user` (referred_by_id)');

        // 8. Product jadvaliga membershipLevel ustuni
        $this->addSql('ALTER TABLE product ADD membership_level VARCHAR(20) NOT NULL DEFAULT \'free\'');

        // 9. Default tariflarni qo'shish
        $this->addSql("INSERT INTO membership_plan (name, slug, price, product_referral_percent, plan_referral_percent, can_withdraw, has_finance_section, interface_type, clothing_count, has_design, has_photosession, is_active, sort_order, created_at) VALUES
            ('Free', 'free', 0, 1, 0, 0, 0, 'basic', 0, 0, 0, 1, 0, NOW()),
            ('Start', 'start', 450000, 5, 5, 0, 1, 'start', 1, 0, 0, 1, 1, NOW()),
            ('Premium', 'premium', 1000000, 5, 15, 0, 1, 'premium', 2, 1, 0, 1, 2, NOW()),
            ('VIP', 'vip', 1700000, 5, 20, 1, 1, 'premium_vip', 2, 1, 1, 1, 3, NOW())
        ");

        // 10. Mavjud userlarga referral kod berish
        $this->addSql("UPDATE `user` SET referral_code = UPPER(SUBSTRING(MD5(RAND()), 1, 8)) WHERE referral_code IS NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product DROP membership_level');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_user_referred_by');
        $this->addSql('DROP INDEX IDX_user_referred_by ON `user`');
        $this->addSql('DROP INDEX UNIQ_user_referral_code ON `user`');
        $this->addSql('ALTER TABLE `user` DROP referral_code, DROP referred_by_id, DROP current_plan');
        $this->addSql('DROP TABLE withdrawal');
        $this->addSql('DROP TABLE bonus_transaction');
        $this->addSql('DROP TABLE bonus_wallet');
        $this->addSql('DROP TABLE referral_link');
        $this->addSql('DROP TABLE user_membership');
        $this->addSql('DROP TABLE membership_plan');
    }
}
