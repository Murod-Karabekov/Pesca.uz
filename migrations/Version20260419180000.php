<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Start: tarmoq mahsulotidan foiz 0% (faqat tarif tavsiyasi bo'yicha daromad).
 */
final class Version20260419180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Start tarifi: mahsulot referral foizi 0% (membership_plan + referral_link)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE membership_plan SET product_referral_percent = '0.00' WHERE slug = 'start'");
        $this->addSql("UPDATE referral_link SET product_percent = '0.00' WHERE referrer_plan_at_time = 'start'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE membership_plan SET product_referral_percent = '1.00' WHERE slug = 'start'");
        $this->addSql("UPDATE referral_link SET product_percent = '1.00' WHERE referrer_plan_at_time = 'start'");
    }
}
