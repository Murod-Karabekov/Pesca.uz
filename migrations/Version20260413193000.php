<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cleanup legacy variant table and align cart size column with mapping';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('product_variant')) {
            $this->addSql('ALTER TABLE product_variant DROP FOREIGN KEY fk_variant_product');
            $this->addSql('DROP TABLE product_variant');
        }

        if ($schema->hasTable('cart') && $schema->getTable('cart')->hasColumn('size')) {
            $this->addSql('ALTER TABLE cart CHANGE size size VARCHAR(30) NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE product_variant (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, variant VARCHAR(255) NOT NULL, INDEX idx_variant_product (product_id), UNIQUE INDEX uq_variant_product_variant (product_id, variant), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE product_variant ADD CONSTRAINT fk_variant_product FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');

        if ($schema->hasTable('cart') && $schema->getTable('cart')->hasColumn('size')) {
            $this->addSql("ALTER TABLE cart CHANGE size size VARCHAR(30) NOT NULL DEFAULT 'UNIVERSAL'");
        }
    }
}
