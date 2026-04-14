<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260414223000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add SmartStyle V2 optional context and body measurement fields to user_profile';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('user_profile')) {
            return;
        }

        $table = $schema->getTable('user_profile');

        if (!$table->hasColumn('occasion')) {
            $this->addSql('ALTER TABLE user_profile ADD occasion VARCHAR(30) DEFAULT NULL');
        }

        if (!$table->hasColumn('style_intent')) {
            $this->addSql('ALTER TABLE user_profile ADD style_intent VARCHAR(30) DEFAULT NULL');
        }

        if (!$table->hasColumn('season')) {
            $this->addSql('ALTER TABLE user_profile ADD season VARCHAR(10) DEFAULT NULL');
        }

        if (!$table->hasColumn('height_cm')) {
            $this->addSql('ALTER TABLE user_profile ADD height_cm SMALLINT DEFAULT NULL');
        }

        if (!$table->hasColumn('shoulder_cm')) {
            $this->addSql('ALTER TABLE user_profile ADD shoulder_cm SMALLINT DEFAULT NULL');
        }

        if (!$table->hasColumn('chest_cm')) {
            $this->addSql('ALTER TABLE user_profile ADD chest_cm SMALLINT DEFAULT NULL');
        }

        if (!$table->hasColumn('waist_cm')) {
            $this->addSql('ALTER TABLE user_profile ADD waist_cm SMALLINT DEFAULT NULL');
        }

        if (!$table->hasColumn('hip_cm')) {
            $this->addSql('ALTER TABLE user_profile ADD hip_cm SMALLINT DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('user_profile')) {
            return;
        }

        $table = $schema->getTable('user_profile');

        if ($table->hasColumn('hip_cm')) {
            $this->addSql('ALTER TABLE user_profile DROP hip_cm');
        }

        if ($table->hasColumn('waist_cm')) {
            $this->addSql('ALTER TABLE user_profile DROP waist_cm');
        }

        if ($table->hasColumn('chest_cm')) {
            $this->addSql('ALTER TABLE user_profile DROP chest_cm');
        }

        if ($table->hasColumn('shoulder_cm')) {
            $this->addSql('ALTER TABLE user_profile DROP shoulder_cm');
        }

        if ($table->hasColumn('height_cm')) {
            $this->addSql('ALTER TABLE user_profile DROP height_cm');
        }

        if ($table->hasColumn('season')) {
            $this->addSql('ALTER TABLE user_profile DROP season');
        }

        if ($table->hasColumn('style_intent')) {
            $this->addSql('ALTER TABLE user_profile DROP style_intent');
        }

        if ($table->hasColumn('occasion')) {
            $this->addSql('ALTER TABLE user_profile DROP occasion');
        }
    }
}
