<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260702181030 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE treatment DROP FOREIGN KEY `FK_98013C318E962C16`');
        $this->addSql('ALTER TABLE vaccination DROP FOREIGN KEY `FK_1B0999998E962C16`');
        $this->addSql('DROP TABLE treatment');
        $this->addSql('DROP TABLE vaccination');
        $this->addSql('ALTER TABLE health_book_entry ADD dosage VARCHAR(255) DEFAULT NULL, ADD frequency VARCHAR(255) DEFAULT NULL, ADD end_date DATE DEFAULT NULL, ADD next_reminder_at DATE DEFAULT NULL, ADD recurrence_months INT DEFAULT NULL, ADD batch_number VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE treatment (id INT AUTO_INCREMENT NOT NULL, medication_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, dosage VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, frequency VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, instructions LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, prescribed_by VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, animal_id INT NOT NULL, INDEX IDX_98013C318E962C16 (animal_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE vaccination (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, administered_at DATE NOT NULL, next_due_at DATE DEFAULT NULL, recurrence_months INT DEFAULT NULL, batch_number VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, veterinarian_name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, notes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, animal_id INT NOT NULL, INDEX IDX_1B0999998E962C16 (animal_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE treatment ADD CONSTRAINT `FK_98013C318E962C16` FOREIGN KEY (animal_id) REFERENCES animal (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE vaccination ADD CONSTRAINT `FK_1B0999998E962C16` FOREIGN KEY (animal_id) REFERENCES animal (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE health_book_entry DROP dosage, DROP frequency, DROP end_date, DROP next_reminder_at, DROP recurrence_months, DROP batch_number');
    }
}
