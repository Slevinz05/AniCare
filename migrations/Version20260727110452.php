<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260727110452 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE health_book_entry ADD veterinarian_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE health_book_entry ADD CONSTRAINT FK_3BD3FCCA804C8213 FOREIGN KEY (veterinarian_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_3BD3FCCA804C8213 ON health_book_entry (veterinarian_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE health_book_entry DROP FOREIGN KEY FK_3BD3FCCA804C8213');
        $this->addSql('DROP INDEX IDX_3BD3FCCA804C8213 ON health_book_entry');
        $this->addSql('ALTER TABLE health_book_entry DROP veterinarian_id');
    }
}
