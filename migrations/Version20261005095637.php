<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261005095637 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add sharedAt and createdBy to health_book_entry for consultation workflow';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE health_book_entry ADD shared_at DATETIME DEFAULT NULL, ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE health_book_entry ADD CONSTRAINT FK_3BD3FCCAB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_3BD3FCCAB03A8386 ON health_book_entry (created_by_id)');
        $this->addSql('UPDATE health_book_entry SET created_by_id = veterinarian_id WHERE veterinarian_id IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE health_book_entry DROP FOREIGN KEY FK_3BD3FCCAB03A8386');
        $this->addSql('DROP INDEX IDX_3BD3FCCAB03A8386 ON health_book_entry');
        $this->addSql('ALTER TABLE health_book_entry DROP shared_at, DROP created_by_id');
    }
}
