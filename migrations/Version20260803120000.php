<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260803120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add status field to health_book_entry for draft support';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE health_book_entry ADD status VARCHAR(20) NOT NULL DEFAULT 'published'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE health_book_entry DROP status');
    }
}
