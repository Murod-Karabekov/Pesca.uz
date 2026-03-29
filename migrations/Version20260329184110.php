<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260329184110 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove size column from cart table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX user_product_size_unique ON cart');
        $this->addSql('ALTER TABLE cart DROP size');
        $this->addSql('CREATE UNIQUE INDEX user_product_unique ON cart (user_id, product_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX user_product_unique ON cart');
        $this->addSql('ALTER TABLE cart ADD size VARCHAR(10) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX user_product_size_unique ON cart (user_id, product_id, size)');
    }
}
