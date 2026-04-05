<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260405093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create order and order_item tables for internal checkout flow';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE customer_order (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, approved_by_admin_id INT DEFAULT NULL, customer_full_name VARCHAR(255) NOT NULL, customer_phone VARCHAR(20) NOT NULL, location_code VARCHAR(50) NOT NULL, location_label VARCHAR(100) NOT NULL, notes LONGTEXT DEFAULT NULL, subtotal_amount NUMERIC(12, 2) NOT NULL, currency VARCHAR(3) NOT NULL, order_status VARCHAR(30) NOT NULL, payment_status VARCHAR(30) NOT NULL, payment_method VARCHAR(30) DEFAULT NULL, payment_reference VARCHAR(100) DEFAULT NULL, admin_note LONGTEXT DEFAULT NULL, approved_at DATETIME DEFAULT NULL COMMENT "(DC2Type:datetime_immutable)", created_at DATETIME NOT NULL COMMENT "(DC2Type:datetime_immutable)", updated_at DATETIME NOT NULL COMMENT "(DC2Type:datetime_immutable)", INDEX IDX_6AA5C982A76ED395 (user_id), INDEX IDX_6AA5C9829BD0F3D8 (approved_by_admin_id), INDEX idx_order_status (order_status), INDEX idx_payment_status (payment_status), INDEX idx_location_code (location_code), INDEX idx_order_created_at (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE order_item (id INT AUTO_INCREMENT NOT NULL, order_id INT NOT NULL, product_id INT NOT NULL, product_name_snapshot VARCHAR(255) NOT NULL, product_image_snapshot VARCHAR(255) DEFAULT NULL, unit_price NUMERIC(12, 2) NOT NULL, quantity INT NOT NULL, line_total NUMERIC(12, 2) NOT NULL, created_at DATETIME NOT NULL COMMENT "(DC2Type:datetime_immutable)", INDEX IDX_52EA1F098D9F6D38 (order_id), INDEX IDX_52EA1F094584665A (product_id), INDEX idx_order_item_order (order_id), INDEX idx_order_item_product (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE customer_order ADD CONSTRAINT FK_6AA5C982A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE customer_order ADD CONSTRAINT FK_6AA5C9829BD0F3D8 FOREIGN KEY (approved_by_admin_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F098D9F6D38 FOREIGN KEY (order_id) REFERENCES customer_order (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F094584665A FOREIGN KEY (product_id) REFERENCES product (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer_order DROP FOREIGN KEY FK_6AA5C982A76ED395');
        $this->addSql('ALTER TABLE customer_order DROP FOREIGN KEY FK_6AA5C9829BD0F3D8');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F098D9F6D38');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F094584665A');
        $this->addSql('DROP TABLE customer_order');
        $this->addSql('DROP TABLE order_item');
    }
}
