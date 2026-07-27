<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260727100201 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reminder (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, scheduled_at DATETIME NOT NULL, recurrence VARCHAR(30) DEFAULT NULL, next_occurrence DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, active TINYINT NOT NULL, animal_id INT NOT NULL, owner_id INT NOT NULL, INDEX IDX_40374F408E962C16 (animal_id), INDEX IDX_40374F407E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE reminder ADD CONSTRAINT FK_40374F408E962C16 FOREIGN KEY (animal_id) REFERENCES animal (id)');
        $this->addSql('ALTER TABLE reminder ADD CONSTRAINT FK_40374F407E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reminder DROP FOREIGN KEY FK_40374F408E962C16');
        $this->addSql('ALTER TABLE reminder DROP FOREIGN KEY FK_40374F407E3C61F9');
        $this->addSql('DROP TABLE reminder');
    }
}
