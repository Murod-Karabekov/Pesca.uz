<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260406170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align membership referral percentages with new START/PREMIUM/VIP tariff copy';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE membership_plan SET plan_referral_percent = '5.00', product_referral_percent = '1.00' WHERE slug = 'start'");
        $this->addSql("UPDATE membership_plan SET plan_referral_percent = '10.00', product_referral_percent = '1.00' WHERE slug = 'premium'");
        $this->addSql("UPDATE membership_plan SET plan_referral_percent = '20.00', product_referral_percent = '1.00' WHERE slug = 'vip'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE membership_plan SET plan_referral_percent = '5.00', product_referral_percent = '5.00' WHERE slug = 'start'");
        $this->addSql("UPDATE membership_plan SET plan_referral_percent = '15.00', product_referral_percent = '5.00' WHERE slug = 'premium'");
        $this->addSql("UPDATE membership_plan SET plan_referral_percent = '20.00', product_referral_percent = '5.00' WHERE slug = 'vip'");
    }
}
