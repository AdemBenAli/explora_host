<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to add Accommodation and Reservation tables.
 */
final class Version20260418143711 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds hebergement, avis and reservation tables';
    }

    public function up(Schema $schema): void
    {
        // Add new tables only
        $this->addSql('CREATE TABLE avis (id_avis INT AUTO_INCREMENT NOT NULL, id_hebergement INT NOT NULL, nom_auteur VARCHAR(255) NOT NULL, note INT NOT NULL, commentaire LONGTEXT DEFAULT NULL, date_avis DATETIME NOT NULL, INDEX IDX_8F91ABF05040106B (id_hebergement), PRIMARY KEY(id_avis)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE hebergement (id_hebergement INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, type VARCHAR(100) NOT NULL, localisation VARCHAR(255) NOT NULL, pays VARCHAR(100) DEFAULT NULL, description LONGTEXT DEFAULT NULL, prix_par_nuit DOUBLE PRECISION DEFAULT NULL, capacite INT DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, note_moyenne DOUBLE PRECISION DEFAULT NULL, date_creation DATETIME DEFAULT NULL, image_path VARCHAR(255) DEFAULT NULL, updated_at DATETIME DEFAULT NULL, special_couple TINYINT(1) DEFAULT 0 NOT NULL, under18_allowed TINYINT(1) DEFAULT 0 NOT NULL, sea_view TINYINT(1) DEFAULT 0 NOT NULL, PRIMARY KEY(id_hebergement)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reservation (id_reservation INT AUTO_INCREMENT NOT NULL, id_hebergement INT NOT NULL, nom_client VARCHAR(255) NOT NULL, email_client VARCHAR(255) DEFAULT NULL, date_checkin DATE NOT NULL, date_checkout DATE NOT NULL, statut VARCHAR(50) NOT NULL, prix_total DOUBLE PRECISION NOT NULL, date_reservation DATETIME NOT NULL, guests_count INT NOT NULL, rooms_count INT NOT NULL, occupancy VARCHAR(50) NOT NULL, room_type VARCHAR(100) DEFAULT NULL, INDEX IDX_42C849555040106B (id_hebergement), PRIMARY KEY(id_reservation)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reservation_guest (id_guest INT AUTO_INCREMENT NOT NULL, id_reservation INT NOT NULL, full_name VARCHAR(255) NOT NULL, birth_date DATE DEFAULT NULL, INDEX IDX_EFC84A925ADA84A2 (id_reservation), PRIMARY KEY(id_guest)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF05040106B FOREIGN KEY (id_hebergement) REFERENCES hebergement (id_hebergement) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C849555040106B FOREIGN KEY (id_hebergement) REFERENCES hebergement (id_hebergement)');
        $this->addSql('ALTER TABLE reservation_guest ADD CONSTRAINT FK_EFC84A925ADA84A2 FOREIGN KEY (id_reservation) REFERENCES reservation (id_reservation) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF05040106B');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C849555040106B');
        $this->addSql('ALTER TABLE reservation_guest DROP FOREIGN KEY FK_EFC84A925ADA84A2');
        $this->addSql('DROP TABLE avis');
        $this->addSql('DROP TABLE hebergement');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE reservation_guest');
    }
}
