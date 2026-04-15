<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260415190959 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE vendor (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, address VARCHAR(500) DEFAULT NULL, commission_rate NUMERIC(5, 2) NOT NULL, total_earnings NUMERIC(14, 2) NOT NULL, is_active TINYINT NOT NULL, created_at DATETIME NOT NULL, owner_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_F52233F67E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE vendor_transaction (id INT AUTO_INCREMENT NOT NULL, sale_amount NUMERIC(14, 2) NOT NULL, commission_amount NUMERIC(14, 2) NOT NULL, commission_rate NUMERIC(5, 2) NOT NULL, status VARCHAR(20) NOT NULL, settled_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, vendor_id INT NOT NULL, order_id INT NOT NULL, INDEX idx_vtx_vendor (vendor_id), INDEX idx_vtx_order (order_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE vendor ADD CONSTRAINT FK_F52233F67E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE vendor_transaction ADD CONSTRAINT FK_A17B84FEF603EE73 FOREIGN KEY (vendor_id) REFERENCES vendor (id)');
        $this->addSql('ALTER TABLE vendor_transaction ADD CONSTRAINT FK_A17B84FE8D9F6D38 FOREIGN KEY (order_id) REFERENCES customer_order (id)');
        $this->addSql('ALTER TABLE order_item ADD vendor_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F09F603EE73 FOREIGN KEY (vendor_id) REFERENCES vendor (id)');
        $this->addSql('CREATE INDEX IDX_52EA1F09F603EE73 ON order_item (vendor_id)');
        $this->addSql('ALTER TABLE product ADD publish_status VARCHAR(20) NOT NULL, ADD vendor_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADF603EE73 FOREIGN KEY (vendor_id) REFERENCES vendor (id)');
        $this->addSql('CREATE INDEX IDX_D34A04ADF603EE73 ON product (vendor_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vendor DROP FOREIGN KEY FK_F52233F67E3C61F9');
        $this->addSql('ALTER TABLE vendor_transaction DROP FOREIGN KEY FK_A17B84FEF603EE73');
        $this->addSql('ALTER TABLE vendor_transaction DROP FOREIGN KEY FK_A17B84FE8D9F6D38');
        $this->addSql('DROP TABLE vendor');
        $this->addSql('DROP TABLE vendor_transaction');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F09F603EE73');
        $this->addSql('DROP INDEX IDX_52EA1F09F603EE73 ON order_item');
        $this->addSql('ALTER TABLE order_item DROP vendor_id');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADF603EE73');
        $this->addSql('DROP INDEX IDX_D34A04ADF603EE73 ON product');
        $this->addSql('ALTER TABLE product DROP publish_status, DROP vendor_id');
    }
}
