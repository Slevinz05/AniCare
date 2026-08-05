<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260805094327 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment CHANGE event_type event_type VARCHAR(30) NOT NULL, CHANGE duration duration SMALLINT NOT NULL');
        $this->addSql('ALTER TABLE health_book_entry ADD updated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment CHANGE event_type event_type VARCHAR(30) DEFAULT \'appointment\' NOT NULL, CHANGE duration duration SMALLINT DEFAULT 60 NOT NULL');
        $this->addSql('ALTER TABLE health_book_entry DROP updated_at');
    }
}
