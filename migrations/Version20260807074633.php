<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260807074633 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cover_photo and description to structure';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS animal_deletion_request (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, reason LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, responded_at DATETIME DEFAULT NULL, animal_id INT NOT NULL, requested_by_id INT NOT NULL, INDEX IDX_575A8DF88E962C16 (animal_id), INDEX IDX_575A8DF84DA1E751 (requested_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE structure ADD cover_photo VARCHAR(255) DEFAULT NULL, ADD description LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE structure DROP cover_photo, DROP description');
    }
}
