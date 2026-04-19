<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Corporate partnership: drop STIR; proposals (additional_notes) NOT NULL';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE corporate_partnership_request DROP COLUMN stir');
        $this->addSql("UPDATE corporate_partnership_request SET additional_notes = '(avvalgi yozuvsiz)' WHERE additional_notes IS NULL OR additional_notes = ''");
        $this->addSql('ALTER TABLE corporate_partnership_request CHANGE additional_notes additional_notes LONGTEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE corporate_partnership_request ADD stir VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE corporate_partnership_request CHANGE additional_notes additional_notes LONGTEXT DEFAULT NULL');
    }
}
