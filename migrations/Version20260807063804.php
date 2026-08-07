<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260807063804 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE animal_deletion_request (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, reason LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, responded_at DATETIME DEFAULT NULL, animal_id INT NOT NULL, requested_by_id INT NOT NULL, INDEX IDX_575A8DF88E962C16 (animal_id), INDEX IDX_575A8DF84DA1E751 (requested_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE animal_deletion_request ADD CONSTRAINT FK_575A8DF88E962C16 FOREIGN KEY (animal_id) REFERENCES animal (id)');
        $this->addSql('ALTER TABLE animal_deletion_request ADD CONSTRAINT FK_575A8DF84DA1E751 FOREIGN KEY (requested_by_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE animal_deletion_request DROP FOREIGN KEY FK_575A8DF88E962C16');
        $this->addSql('ALTER TABLE animal_deletion_request DROP FOREIGN KEY FK_575A8DF84DA1E751');
        $this->addSql('DROP TABLE animal_deletion_request');
    }
}
