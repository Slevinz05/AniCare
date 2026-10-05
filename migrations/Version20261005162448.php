<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261005162448 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE health_book_entry ADD shared_with_email VARCHAR(180) DEFAULT NULL, ADD share_mode VARCHAR(20) DEFAULT NULL, ADD shared_with_user_id INT DEFAULT NULL, ADD shared_with_structure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE health_book_entry ADD CONSTRAINT FK_3BD3FCCA42EBB09C FOREIGN KEY (shared_with_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE health_book_entry ADD CONSTRAINT FK_3BD3FCCAC540E4D5 FOREIGN KEY (shared_with_structure_id) REFERENCES structure (id)');
        $this->addSql('CREATE INDEX IDX_3BD3FCCA42EBB09C ON health_book_entry (shared_with_user_id)');
        $this->addSql('CREATE INDEX IDX_3BD3FCCAC540E4D5 ON health_book_entry (shared_with_structure_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE health_book_entry DROP FOREIGN KEY FK_3BD3FCCA42EBB09C');
        $this->addSql('ALTER TABLE health_book_entry DROP FOREIGN KEY FK_3BD3FCCAC540E4D5');
        $this->addSql('DROP INDEX IDX_3BD3FCCA42EBB09C ON health_book_entry');
        $this->addSql('DROP INDEX IDX_3BD3FCCAC540E4D5 ON health_book_entry');
        $this->addSql('ALTER TABLE health_book_entry DROP shared_with_email, DROP share_mode, DROP shared_with_user_id, DROP shared_with_structure_id');
    }
}
