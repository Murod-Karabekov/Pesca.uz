<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260414230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add body_type column to user_profile';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user_profile ADD body_type VARCHAR(30) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user_profile DROP COLUMN body_type");
    }
}
