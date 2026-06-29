<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260628111344 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE animal_share (id INT AUTO_INCREMENT NOT NULL, shared_with_email VARCHAR(255) NOT NULL, permission_level VARCHAR(20) NOT NULL, created_at DATE NOT NULL, animal_id INT NOT NULL, INDEX IDX_CEF544AE8E962C16 (animal_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE animal_share ADD CONSTRAINT FK_CEF544AE8E962C16 FOREIGN KEY (animal_id) REFERENCES animal (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE animal_share DROP FOREIGN KEY FK_CEF544AE8E962C16');
        $this->addSql('DROP TABLE animal_share');
    }
}
