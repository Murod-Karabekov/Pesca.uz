<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add selected size support to cart and order items';
    }

    public function up(Schema $schema): void
    {
        $cartTable = $schema->getTable('cart');

        if (!$cartTable->hasColumn('size')) {
            $this->addSql("ALTER TABLE cart ADD size VARCHAR(30) NOT NULL DEFAULT 'UNIVERSAL'");
        }

        if ($cartTable->hasIndex('user_product_unique')) {
            $this->addSql('DROP INDEX user_product_unique ON cart');
        }

        if ($cartTable->hasIndex('user_product_variant_unique')) {
            $this->addSql('DROP INDEX user_product_variant_unique ON cart');
        }

        // Merge duplicate cart rows by user/product/size before adding strict unique index.
        $this->addSql('UPDATE cart c JOIN (SELECT MIN(id) AS keep_id, user_id, product_id, size, SUM(quantity) AS total_quantity FROM cart GROUP BY user_id, product_id, size HAVING COUNT(*) > 1) d ON c.id = d.keep_id SET c.quantity = d.total_quantity');
        $this->addSql('DELETE c1 FROM cart c1 JOIN cart c2 ON c1.user_id = c2.user_id AND c1.product_id = c2.product_id AND c1.size = c2.size AND c1.id > c2.id');

        if (!$cartTable->hasIndex('user_product_size_unique')) {
            $this->addSql('CREATE UNIQUE INDEX user_product_size_unique ON cart (user_id, product_id, size)');
        }

        $orderItemTable = $schema->getTable('order_item');
        if (!$orderItemTable->hasColumn('selected_size')) {
            $this->addSql('ALTER TABLE order_item ADD selected_size VARCHAR(30) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $orderItemTable = $schema->getTable('order_item');
        if ($orderItemTable->hasColumn('selected_size')) {
            $this->addSql('ALTER TABLE order_item DROP selected_size');
        }

        $cartTable = $schema->getTable('cart');

        if ($cartTable->hasIndex('user_product_size_unique')) {
            $this->addSql('DROP INDEX user_product_size_unique ON cart');
        }

        if (!$cartTable->hasIndex('user_product_unique')) {
            $this->addSql('CREATE UNIQUE INDEX user_product_unique ON cart (user_id, product_id)');
        }

        if ($cartTable->hasColumn('size')) {
            $this->addSql('ALTER TABLE cart DROP size');
        }
    }
}
