<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261005172039 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE health_book_entry_share (id INT AUTO_INCREMENT NOT NULL, shared_with_email VARCHAR(180) DEFAULT NULL, mode VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, entry_id INT NOT NULL, shared_with_user_id INT DEFAULT NULL, shared_with_structure_id INT DEFAULT NULL, INDEX IDX_4456ED4EBA364942 (entry_id), INDEX IDX_4456ED4E42EBB09C (shared_with_user_id), INDEX IDX_4456ED4EC540E4D5 (shared_with_structure_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE health_book_entry_share ADD CONSTRAINT FK_4456ED4EBA364942 FOREIGN KEY (entry_id) REFERENCES health_book_entry (id)');
        $this->addSql('ALTER TABLE health_book_entry_share ADD CONSTRAINT FK_4456ED4E42EBB09C FOREIGN KEY (shared_with_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE health_book_entry_share ADD CONSTRAINT FK_4456ED4EC540E4D5 FOREIGN KEY (shared_with_structure_id) REFERENCES structure (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE health_book_entry_share DROP FOREIGN KEY FK_4456ED4EBA364942');
        $this->addSql('ALTER TABLE health_book_entry_share DROP FOREIGN KEY FK_4456ED4E42EBB09C');
        $this->addSql('ALTER TABLE health_book_entry_share DROP FOREIGN KEY FK_4456ED4EC540E4D5');
        $this->addSql('DROP TABLE health_book_entry_share');
    }
}
