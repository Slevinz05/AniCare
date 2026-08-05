<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260804194222 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE appointment_animal (appointment_id INT NOT NULL, animal_id INT NOT NULL, INDEX IDX_2DF59C51E5B533F9 (appointment_id), INDEX IDX_2DF59C518E962C16 (animal_id), PRIMARY KEY (appointment_id, animal_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE appointment_animal ADD CONSTRAINT FK_2DF59C51E5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE appointment_animal ADD CONSTRAINT FK_2DF59C518E962C16 FOREIGN KEY (animal_id) REFERENCES animal (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE appointment ADD event_type VARCHAR(30) NOT NULL DEFAULT \'appointment\', ADD duration SMALLINT NOT NULL DEFAULT 60, ADD travel_time SMALLINT DEFAULT NULL, ADD consultation_type VARCHAR(100) DEFAULT NULL, ADD public_notes LONGTEXT DEFAULT NULL, ADD private_notes LONGTEXT DEFAULT NULL, ADD client_id INT DEFAULT NULL, ADD shared_with_professional_id INT DEFAULT NULL, CHANGE animal_id animal_id INT DEFAULT NULL');
        $this->addSql('UPDATE appointment SET event_type = \'appointment\', duration = 60 WHERE event_type = \'\'');
        $this->addSql('INSERT INTO appointment_animal (appointment_id, animal_id) SELECT id, animal_id FROM appointment WHERE animal_id IS NOT NULL');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F84419EB6921 FOREIGN KEY (client_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F8446EFF094 FOREIGN KEY (shared_with_professional_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_FE38F84419EB6921 ON appointment (client_id)');
        $this->addSql('CREATE INDEX IDX_FE38F8446EFF094 ON appointment (shared_with_professional_id)');
        $this->addSql('ALTER TABLE user ADD default_consultation_duration SMALLINT DEFAULT NULL, ADD default_public_notes LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment_animal DROP FOREIGN KEY FK_2DF59C51E5B533F9');
        $this->addSql('ALTER TABLE appointment_animal DROP FOREIGN KEY FK_2DF59C518E962C16');
        $this->addSql('DROP TABLE appointment_animal');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F84419EB6921');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F8446EFF094');
        $this->addSql('DROP INDEX IDX_FE38F84419EB6921 ON appointment');
        $this->addSql('DROP INDEX IDX_FE38F8446EFF094 ON appointment');
        $this->addSql('ALTER TABLE appointment DROP event_type, DROP duration, DROP travel_time, DROP consultation_type, DROP public_notes, DROP private_notes, DROP client_id, DROP shared_with_professional_id, CHANGE animal_id animal_id INT NOT NULL');
        $this->addSql('ALTER TABLE user DROP default_consultation_duration, DROP default_public_notes');
    }
}
