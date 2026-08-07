<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260806172049 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE structure (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(100) DEFAULT NULL, street VARCHAR(255) DEFAULT NULL, complement VARCHAR(255) DEFAULT NULL, postal_code VARCHAR(10) DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, country VARCHAR(100) DEFAULT NULL, phone VARCHAR(30) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, siret VARCHAR(50) DEFAULT NULL, claim_code VARCHAR(8) NOT NULL, created_at DATETIME NOT NULL, created_by_id INT NOT NULL, UNIQUE INDEX UNIQ_6F0137EA391AA7BC (claim_code), INDEX IDX_6F0137EAB03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE structure_membership (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(20) NOT NULL, joined_at DATETIME NOT NULL, user_id INT NOT NULL, structure_id INT NOT NULL, INDEX IDX_B4B1E6CBA76ED395 (user_id), INDEX IDX_B4B1E6CB2534008B (structure_id), UNIQUE INDEX UNIQ_B4B1E6CBA76ED3952534008B (user_id, structure_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE structure ADD CONSTRAINT FK_6F0137EAB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE structure_membership ADD CONSTRAINT FK_B4B1E6CBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE structure_membership ADD CONSTRAINT FK_B4B1E6CB2534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('ALTER TABLE animal ADD structure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE animal ADD CONSTRAINT FK_6AAB231F2534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('CREATE INDEX IDX_6AAB231F2534008B ON animal (structure_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE structure DROP FOREIGN KEY FK_6F0137EAB03A8386');
        $this->addSql('ALTER TABLE structure_membership DROP FOREIGN KEY FK_B4B1E6CBA76ED395');
        $this->addSql('ALTER TABLE structure_membership DROP FOREIGN KEY FK_B4B1E6CB2534008B');
        $this->addSql('DROP TABLE structure');
        $this->addSql('DROP TABLE structure_membership');
        $this->addSql('ALTER TABLE animal DROP FOREIGN KEY FK_6AAB231F2534008B');
        $this->addSql('DROP INDEX IDX_6AAB231F2534008B ON animal');
        $this->addSql('ALTER TABLE animal DROP structure_id');
    }
}
