<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261007142646 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE animal_referent ADD can_share TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE health_book_entry ADD corrected_at DATETIME DEFAULT NULL, ADD correction_reason LONGTEXT DEFAULT NULL, ADD version INT DEFAULT 1 NOT NULL, ADD last_corrected_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE health_book_entry ADD CONSTRAINT FK_3BD3FCCA66483DF4 FOREIGN KEY (last_corrected_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_3BD3FCCA66483DF4 ON health_book_entry (last_corrected_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE animal_referent DROP can_share');
        $this->addSql('ALTER TABLE health_book_entry DROP FOREIGN KEY FK_3BD3FCCA66483DF4');
        $this->addSql('DROP INDEX IDX_3BD3FCCA66483DF4 ON health_book_entry');
        $this->addSql('ALTER TABLE health_book_entry DROP corrected_at, DROP correction_reason, DROP version, DROP last_corrected_by_id');
    }
}
