<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260702180306 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE allergy (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, severity VARCHAR(50) NOT NULL, symptoms LONGTEXT DEFAULT NULL, diagnosed_at DATE DEFAULT NULL, notes LONGTEXT DEFAULT NULL, animal_id INT NOT NULL, INDEX IDX_CBB142B58E962C16 (animal_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE appointment (id INT AUTO_INCREMENT NOT NULL, reason VARCHAR(255) NOT NULL, scheduled_at DATETIME NOT NULL, status VARCHAR(30) NOT NULL, notes LONGTEXT DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, animal_id INT NOT NULL, created_by_id INT NOT NULL, INDEX IDX_FE38F8448E962C16 (animal_id), INDEX IDX_FE38F844B03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, is_read TINYINT NOT NULL, created_at DATETIME NOT NULL, sender_id INT NOT NULL, animal_id INT NOT NULL, INDEX IDX_B6BD307FF624B39D (sender_id), INDEX IDX_B6BD307F8E962C16 (animal_id), INDEX idx_message_animal_date (animal_id, created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE treatment (id INT AUTO_INCREMENT NOT NULL, medication_name VARCHAR(255) NOT NULL, dosage VARCHAR(255) DEFAULT NULL, frequency VARCHAR(100) DEFAULT NULL, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, instructions LONGTEXT DEFAULT NULL, prescribed_by VARCHAR(255) DEFAULT NULL, animal_id INT NOT NULL, INDEX IDX_98013C318E962C16 (animal_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE vaccination (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, administered_at DATE NOT NULL, next_due_at DATE DEFAULT NULL, recurrence_months INT DEFAULT NULL, batch_number VARCHAR(100) DEFAULT NULL, veterinarian_name VARCHAR(255) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, animal_id INT NOT NULL, INDEX IDX_1B0999998E962C16 (animal_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE weight_record (id INT AUTO_INCREMENT NOT NULL, weight DOUBLE PRECISION NOT NULL, recorded_at DATE NOT NULL, note LONGTEXT DEFAULT NULL, animal_id INT NOT NULL, INDEX IDX_506A8B488E962C16 (animal_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE allergy ADD CONSTRAINT FK_CBB142B58E962C16 FOREIGN KEY (animal_id) REFERENCES animal (id)');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F8448E962C16 FOREIGN KEY (animal_id) REFERENCES animal (id)');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF624B39D FOREIGN KEY (sender_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F8E962C16 FOREIGN KEY (animal_id) REFERENCES animal (id)');
        $this->addSql('ALTER TABLE treatment ADD CONSTRAINT FK_98013C318E962C16 FOREIGN KEY (animal_id) REFERENCES animal (id)');
        $this->addSql('ALTER TABLE vaccination ADD CONSTRAINT FK_1B0999998E962C16 FOREIGN KEY (animal_id) REFERENCES animal (id)');
        $this->addSql('ALTER TABLE weight_record ADD CONSTRAINT FK_506A8B488E962C16 FOREIGN KEY (animal_id) REFERENCES animal (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE allergy DROP FOREIGN KEY FK_CBB142B58E962C16');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F8448E962C16');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F844B03A8386');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FF624B39D');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F8E962C16');
        $this->addSql('ALTER TABLE treatment DROP FOREIGN KEY FK_98013C318E962C16');
        $this->addSql('ALTER TABLE vaccination DROP FOREIGN KEY FK_1B0999998E962C16');
        $this->addSql('ALTER TABLE weight_record DROP FOREIGN KEY FK_506A8B488E962C16');
        $this->addSql('DROP TABLE allergy');
        $this->addSql('DROP TABLE appointment');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE treatment');
        $this->addSql('DROP TABLE vaccination');
        $this->addSql('DROP TABLE weight_record');
    }
}
