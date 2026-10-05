<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261005163203 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE animal ADD created_by_pro_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE animal ADD CONSTRAINT FK_6AAB231FD5F134D1 FOREIGN KEY (created_by_pro_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_6AAB231FD5F134D1 ON animal (created_by_pro_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE animal DROP FOREIGN KEY FK_6AAB231FD5F134D1');
        $this->addSql('DROP INDEX IDX_6AAB231FD5F134D1 ON animal');
        $this->addSql('ALTER TABLE animal DROP created_by_pro_id');
    }
}
