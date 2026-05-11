<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260417005254 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de la colonne updated_at dans la table hebergement pour VichUploaderBundle';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE hebergement ADD updated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE hebergement DROP updated_at');
    }
}