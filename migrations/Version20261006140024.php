<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261006140024 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD company_name VARCHAR(50) DEFAULT NULL, ADD siren VARCHAR(14) DEFAULT NULL, ADD show_company_name TINYINT DEFAULT NULL, ADD consultation_locations JSON DEFAULT NULL, ADD agenda_settings JSON DEFAULT NULL, ADD report_settings JSON DEFAULT NULL, ADD recommended_colleagues JSON DEFAULT NULL, ADD supplements JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP company_name, DROP siren, DROP show_company_name, DROP consultation_locations, DROP agenda_settings, DROP report_settings, DROP recommended_colleagues, DROP supplements');
    }
}
