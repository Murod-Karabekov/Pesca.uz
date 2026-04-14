<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260414231000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add SmartStyle context columns (occasions, style_intents, seasons, body_types) to product';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE product ADD occasions JSON DEFAULT NULL, ADD style_intents JSON DEFAULT NULL, ADD seasons JSON DEFAULT NULL, ADD body_types JSON DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE product DROP COLUMN occasions, DROP COLUMN style_intents, DROP COLUMN seasons, DROP COLUMN body_types");
    }
}
