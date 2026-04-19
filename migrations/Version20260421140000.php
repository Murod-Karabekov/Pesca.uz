<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Premium: tarmoq mahsulotidan foiz 0.5%.
 */
final class Version20260421140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Premium tarifi: product_referral_percent va referral_link product_percent 0.5%';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE membership_plan SET product_referral_percent = '0.50' WHERE slug = 'premium'");
        $this->addSql("UPDATE referral_link SET product_percent = '0.50' WHERE referrer_plan_at_time = 'premium'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE membership_plan SET product_referral_percent = '1.00' WHERE slug = 'premium'");
        $this->addSql("UPDATE referral_link SET product_percent = '1.00' WHERE referrer_plan_at_time = 'premium'");
    }
}
