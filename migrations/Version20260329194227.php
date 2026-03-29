<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260329194227 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add general_balance column to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD general_balance NUMERIC(12, 2) NOT NULL DEFAULT 0.00');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP general_balance');
    }
}
