<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260720145744 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE animal ADD height DOUBLE PRECISION DEFAULT NULL, ADD coat VARCHAR(50) DEFAULT NULL, ADD average_weight DOUBLE PRECISION DEFAULT NULL, ADD microchip_number VARCHAR(100) DEFAULT NULL, ADD living_place_name VARCHAR(255) DEFAULT NULL, ADD living_place_manager_last_name VARCHAR(100) DEFAULT NULL, ADD living_place_manager_first_name VARCHAR(100) DEFAULT NULL, ADD living_place_manager_phone VARCHAR(30) DEFAULT NULL, ADD living_place_manager_email VARCHAR(180) DEFAULT NULL, ADD living_place_street VARCHAR(255) DEFAULT NULL, ADD living_place_complement VARCHAR(255) DEFAULT NULL, ADD living_place_postal_code VARCHAR(10) DEFAULT NULL, ADD living_place_city VARCHAR(100) DEFAULT NULL, ADD living_place_country VARCHAR(100) DEFAULT NULL, CHANGE species species VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE animal DROP height, DROP coat, DROP average_weight, DROP microchip_number, DROP living_place_name, DROP living_place_manager_last_name, DROP living_place_manager_first_name, DROP living_place_manager_phone, DROP living_place_manager_email, DROP living_place_street, DROP living_place_complement, DROP living_place_postal_code, DROP living_place_city, DROP living_place_country, CHANGE species species VARCHAR(100) NOT NULL');
    }
}
