<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261007150124 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE health_book_entry_audit_log (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(30) NOT NULL, performed_at DATETIME NOT NULL, details JSON DEFAULT NULL, entry_id INT NOT NULL, performed_by_id INT NOT NULL, INDEX IDX_86D44F592E65C292 (performed_by_id), INDEX idx_audit_entry (entry_id), INDEX idx_audit_action (action), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE health_book_entry_audit_log ADD CONSTRAINT FK_86D44F59BA364942 FOREIGN KEY (entry_id) REFERENCES health_book_entry (id)');
        $this->addSql('ALTER TABLE health_book_entry_audit_log ADD CONSTRAINT FK_86D44F592E65C292 FOREIGN KEY (performed_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE health_book_entry ADD published_at DATETIME DEFAULT NULL, ADD archived_at DATETIME DEFAULT NULL, ADD archive_reason LONGTEXT DEFAULT NULL, ADD archived_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE health_book_entry ADD CONSTRAINT FK_3BD3FCCA77BE2925 FOREIGN KEY (archived_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_3BD3FCCA77BE2925 ON health_book_entry (archived_by_id)');

        $this->addSql("UPDATE health_book_entry SET status = 'published' WHERE status = 'shared'");
        $this->addSql("UPDATE health_book_entry SET published_at = updated_at WHERE status = 'published' AND published_at IS NULL");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE health_book_entry_audit_log DROP FOREIGN KEY FK_86D44F59BA364942');
        $this->addSql('ALTER TABLE health_book_entry_audit_log DROP FOREIGN KEY FK_86D44F592E65C292');
        $this->addSql('DROP TABLE health_book_entry_audit_log');
        $this->addSql('ALTER TABLE health_book_entry DROP FOREIGN KEY FK_3BD3FCCA77BE2925');
        $this->addSql('DROP INDEX IDX_3BD3FCCA77BE2925 ON health_book_entry');
        $this->addSql('ALTER TABLE health_book_entry DROP published_at, DROP archived_at, DROP archive_reason, DROP archived_by_id');
    }
}
