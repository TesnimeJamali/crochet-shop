<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250527183613 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE cart ADD coupon_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cart ADD CONSTRAINT FK_BA388B766C5951B FOREIGN KEY (coupon_id) REFERENCES coupon (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_BA388B766C5951B ON cart (coupon_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE cart DROP FOREIGN KEY FK_BA388B766C5951B
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_BA388B766C5951B ON cart
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cart DROP coupon_id
        SQL);
    }
}
