<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260801120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add anatomical_locations JSON column to health_book_entry';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE health_book_entry ADD anatomical_locations JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE health_book_entry DROP COLUMN anatomical_locations');
    }
}
