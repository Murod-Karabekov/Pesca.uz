<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260406173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Re-align frozen referral_link percentages for existing users to current START/PREMIUM/VIP rates';
    }

    public function up(Schema $schema): void
    {
        // Existing frozen links are updated so future payouts follow the new tariff policy.
        $this->addSql("UPDATE referral_link SET plan_percent = '5.00', product_percent = '1.00' WHERE referrer_plan_at_time = 'start'");
        $this->addSql("UPDATE referral_link SET plan_percent = '10.00', product_percent = '1.00' WHERE referrer_plan_at_time = 'premium'");
        $this->addSql("UPDATE referral_link SET plan_percent = '20.00', product_percent = '1.00' WHERE referrer_plan_at_time = 'vip'");
        $this->addSql("UPDATE referral_link SET plan_percent = '0.00', product_percent = '0.00' WHERE referrer_plan_at_time = 'free'");
    }

    public function down(Schema $schema): void
    {
        // Roll back to the previous frozen percentages used before this migration.
        $this->addSql("UPDATE referral_link SET plan_percent = '5.00', product_percent = '5.00' WHERE referrer_plan_at_time = 'start'");
        $this->addSql("UPDATE referral_link SET plan_percent = '15.00', product_percent = '5.00' WHERE referrer_plan_at_time = 'premium'");
        $this->addSql("UPDATE referral_link SET plan_percent = '20.00', product_percent = '5.00' WHERE referrer_plan_at_time = 'vip'");
        $this->addSql("UPDATE referral_link SET plan_percent = '0.00', product_percent = '0.00' WHERE referrer_plan_at_time = 'free'");
    }
}
