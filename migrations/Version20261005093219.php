<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261005093219 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create animal_referent table, add active_space to user, migrate existing owners to principal referents';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE animal_referent (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(20) NOT NULL, role VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, designated_at DATETIME NOT NULL, revoked_at DATETIME DEFAULT NULL, animal_id INT NOT NULL, user_id INT NOT NULL, designated_by_id INT DEFAULT NULL, INDEX IDX_3819AAB18E962C16 (animal_id), INDEX IDX_3819AAB1A76ED395 (user_id), INDEX IDX_3819AAB1D4F75929 (designated_by_id), UNIQUE INDEX uniq_animal_user_active (animal_id, user_id, type), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE animal_referent ADD CONSTRAINT FK_3819AAB18E962C16 FOREIGN KEY (animal_id) REFERENCES animal (id)');
        $this->addSql('ALTER TABLE animal_referent ADD CONSTRAINT FK_3819AAB1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE animal_referent ADD CONSTRAINT FK_3819AAB1D4F75929 FOREIGN KEY (designated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user ADD active_space VARCHAR(20) DEFAULT NULL');

        // Data migration: convert existing Animal.owner relationships to AnimalReferent (principal, proprietaire, active)
        $this->addSql("
            INSERT INTO animal_referent (animal_id, user_id, type, role, status, designated_at)
            SELECT a.id, a.owner_id, 'principal', 'proprietaire', 'active', NOW()
            FROM animal a
            WHERE a.owner_id IS NOT NULL
        ");

        // Set activeSpace based on existing accountType
        $this->addSql("UPDATE user SET active_space = 'particulier' WHERE account_type = 'OWNER'");
        $this->addSql("UPDATE user SET active_space = 'professionnel' WHERE account_type = 'PRO'");
        $this->addSql("UPDATE user SET active_space = 'professionnel' WHERE account_type = 'STRUCTURE'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE animal_referent DROP FOREIGN KEY FK_3819AAB18E962C16');
        $this->addSql('ALTER TABLE animal_referent DROP FOREIGN KEY FK_3819AAB1A76ED395');
        $this->addSql('ALTER TABLE animal_referent DROP FOREIGN KEY FK_3819AAB1D4F75929');
        $this->addSql('DROP TABLE animal_referent');
        $this->addSql('ALTER TABLE user DROP active_space');
    }
}
