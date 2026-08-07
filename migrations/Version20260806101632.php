<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260806101632 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE health_book_entry ADD static_examination LONGTEXT DEFAULT NULL, DROP general_condition, DROP antalgic_position, DROP conformation_plumb, DROP deformation_sensitivity');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE health_book_entry ADD antalgic_position LONGTEXT DEFAULT NULL, ADD conformation_plumb LONGTEXT DEFAULT NULL, ADD deformation_sensitivity LONGTEXT DEFAULT NULL, CHANGE static_examination general_condition LONGTEXT DEFAULT NULL');
    }
}
