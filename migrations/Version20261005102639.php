<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261005102639 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE tournee (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, name VARCHAR(255) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, created_by_id INT NOT NULL, INDEX IDX_EBF67D7EB03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tournee_stop (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, position INT NOT NULL, completed TINYINT NOT NULL, tournee_id INT NOT NULL, appointment_id INT DEFAULT NULL, INDEX IDX_37076507F661D013 (tournee_id), INDEX IDX_37076507E5B533F9 (appointment_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE tournee ADD CONSTRAINT FK_EBF67D7EB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE tournee_stop ADD CONSTRAINT FK_37076507F661D013 FOREIGN KEY (tournee_id) REFERENCES tournee (id)');
        $this->addSql('ALTER TABLE tournee_stop ADD CONSTRAINT FK_37076507E5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointment (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tournee DROP FOREIGN KEY FK_EBF67D7EB03A8386');
        $this->addSql('ALTER TABLE tournee_stop DROP FOREIGN KEY FK_37076507F661D013');
        $this->addSql('ALTER TABLE tournee_stop DROP FOREIGN KEY FK_37076507E5B533F9');
        $this->addSql('DROP TABLE tournee');
        $this->addSql('DROP TABLE tournee_stop');
    }
}
