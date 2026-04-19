<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Start: mahsulot referral 0% (qayta tasdiqlash — ba'zi muhitlarda avvalgi migratsiya ishlamagan bo'lishi mumkin).
 */
final class Version20260421130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Start tarifi mahsulot foizi 0% — membership_plan va referral_link (idempotent)';
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
