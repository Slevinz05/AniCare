<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260629211206 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE animal (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, species VARCHAR(100) NOT NULL, breed VARCHAR(100) DEFAULT NULL, birth_date DATE DEFAULT NULL, gender VARCHAR(10) NOT NULL, weight DOUBLE PRECISION DEFAULT NULL, blood_type VARCHAR(20) DEFAULT NULL, identification_number VARCHAR(100) DEFAULT NULL, documents JSON DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE animal_share (id INT AUTO_INCREMENT NOT NULL, shared_with_email VARCHAR(255) NOT NULL, permission_level VARCHAR(20) NOT NULL, created_at DATE NOT NULL, animal_id INT NOT NULL, INDEX IDX_CEF544AE8E962C16 (animal_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE health_book_entry (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, date DATE NOT NULL, description LONGTEXT DEFAULT NULL, veterinarian_name VARCHAR(255) DEFAULT NULL, documents JSON DEFAULT NULL, animal_id INT NOT NULL, INDEX IDX_3BD3FCCA8E962C16 (animal_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE animal_share ADD CONSTRAINT FK_CEF544AE8E962C16 FOREIGN KEY (animal_id) REFERENCES animal (id)');
        $this->addSql('ALTER TABLE health_book_entry ADD CONSTRAINT FK_3BD3FCCA8E962C16 FOREIGN KEY (animal_id) REFERENCES animal (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE animal_share DROP FOREIGN KEY FK_CEF544AE8E962C16');
        $this->addSql('ALTER TABLE health_book_entry DROP FOREIGN KEY FK_3BD3FCCA8E962C16');
        $this->addSql('DROP TABLE animal');
        $this->addSql('DROP TABLE animal_share');
        $this->addSql('DROP TABLE health_book_entry');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
