<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260728100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add slug column to animal table and generate slugs for existing animals';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE animal ADD slug VARCHAR(255) DEFAULT NULL');

        // Generate slugs for existing animals
        $animals = $this->connection->fetchAllAssociative('SELECT id, name FROM animal');
        foreach ($animals as $animal) {
            $slug = mb_strtolower(trim($animal['name']));
            $slug = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9] Remove', $slug);
            if (!$slug) {
                $slug = 'cheval';
            }
            $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $slug));
            $slug = trim($slug, '-') . '-' . $animal['id'];

            $this->addSql('UPDATE animal SET slug = ? WHERE id = ?', [$slug, $animal['id']]);
        }

        $this->addSql('ALTER TABLE animal CHANGE slug slug VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6AAB231F989D9B62 ON animal (slug)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_6AAB231F989D9B62 ON animal');
        $this->addSql('ALTER TABLE animal DROP slug');
    }
}
