<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261005094120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add vet referent and antecedents fields to animal';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE animal ADD vet_referent_name VARCHAR(255) DEFAULT NULL, ADD vet_referent_phone VARCHAR(30) DEFAULT NULL, ADD antecedents_pro LONGTEXT DEFAULT NULL, ADD antecedents_referent LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE animal DROP vet_referent_name, DROP vet_referent_phone, DROP antecedents_pro, DROP antecedents_referent');
    }
}
