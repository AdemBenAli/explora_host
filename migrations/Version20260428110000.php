<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260428110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing columns for transport, billet and hebergement tables';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist(['transport'])) {
            $transportColumns = $schemaManager->listTableColumns('transport');
            if (!isset($transportColumns['created_at'])) {
                $this->addSql('ALTER TABLE transport ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
            }
        }

        if ($schemaManager->tablesExist(['billet'])) {
            $billetColumns = $schemaManager->listTableColumns('billet');

            if (!isset($billetColumns['created_at'])) {
                $this->addSql('ALTER TABLE billet ADD created_at DATETIME DEFAULT NULL');
            }

            if (!isset($billetColumns['qr_code'])) {
                $this->addSql('ALTER TABLE billet ADD qr_code LONGTEXT DEFAULT NULL');
            }
        }

        if ($schemaManager->tablesExist(['hebergement'])) {
            $hebergementColumns = $schemaManager->listTableColumns('hebergement');

            if (!isset($hebergementColumns['capacite'])) {
                $this->addSql('ALTER TABLE hebergement ADD capacite INT DEFAULT NULL');
            }

            if (!isset($hebergementColumns['date_creation'])) {
                $this->addSql('ALTER TABLE hebergement ADD date_creation DATETIME DEFAULT NULL');
            }

            if (!isset($hebergementColumns['updated_at'])) {
                $this->addSql('ALTER TABLE hebergement ADD updated_at DATETIME DEFAULT NULL');
            }
        }

        if ($schemaManager->tablesExist(['reservation'])) {
            $reservationColumns = $schemaManager->listTableColumns('reservation');

            if (!isset($reservationColumns['statut'])) {
                $this->addSql("ALTER TABLE reservation ADD statut VARCHAR(50) NOT NULL DEFAULT 'CONFIRMED'");
            }

            if (!isset($reservationColumns['date_reservation'])) {
                $this->addSql('ALTER TABLE reservation ADD date_reservation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
            }

            if (!isset($reservationColumns['guests_count'])) {
                $this->addSql('ALTER TABLE reservation ADD guests_count INT NOT NULL DEFAULT 1');
            }

            if (!isset($reservationColumns['rooms_count'])) {
                $this->addSql('ALTER TABLE reservation ADD rooms_count INT NOT NULL DEFAULT 1');
            }

            if (isset($reservationColumns['status'])) {
                $this->addSql("UPDATE reservation SET statut = COALESCE(NULLIF(status, ''), statut)");
            }

            if (isset($reservationColumns['nb_guests'])) {
                $this->addSql('UPDATE reservation SET guests_count = COALESCE(nb_guests, guests_count)');
            }

            if (isset($reservationColumns['nb_rooms'])) {
                $this->addSql('UPDATE reservation SET rooms_count = COALESCE(nb_rooms, rooms_count)');
            }

            if (isset($reservationColumns['occupancy'])) {
                $this->addSql("UPDATE reservation SET occupancy = COALESCE(NULLIF(occupancy, ''), 'DOUBLE')");
            }
        }

        if ($schemaManager->tablesExist(['reservation_guest'])) {
            $reservationGuestColumns = $schemaManager->listTableColumns('reservation_guest');

            if (!isset($reservationGuestColumns['id_guest']) && isset($reservationGuestColumns['id'])) {
                $this->addSql('ALTER TABLE reservation_guest CHANGE id id_guest INT NOT NULL AUTO_INCREMENT');
            }
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist(['transport'])) {
            $transportColumns = $schemaManager->listTableColumns('transport');
            if (isset($transportColumns['created_at'])) {
                $this->addSql('ALTER TABLE transport DROP COLUMN created_at');
            }
        }

        if ($schemaManager->tablesExist(['billet'])) {
            $billetColumns = $schemaManager->listTableColumns('billet');

            if (isset($billetColumns['created_at'])) {
                $this->addSql('ALTER TABLE billet DROP COLUMN created_at');
            }

            if (isset($billetColumns['qr_code'])) {
                $this->addSql('ALTER TABLE billet DROP COLUMN qr_code');
            }
        }

        if ($schemaManager->tablesExist(['hebergement'])) {
            $hebergementColumns = $schemaManager->listTableColumns('hebergement');

            if (isset($hebergementColumns['updated_at'])) {
                $this->addSql('ALTER TABLE hebergement DROP COLUMN updated_at');
            }

            if (isset($hebergementColumns['date_creation'])) {
                $this->addSql('ALTER TABLE hebergement DROP COLUMN date_creation');
            }

            if (isset($hebergementColumns['capacite'])) {
                $this->addSql('ALTER TABLE hebergement DROP COLUMN capacite');
            }
        }

        if ($schemaManager->tablesExist(['reservation'])) {
            $reservationColumns = $schemaManager->listTableColumns('reservation');

            if (isset($reservationColumns['rooms_count'])) {
                $this->addSql('ALTER TABLE reservation DROP COLUMN rooms_count');
            }

            if (isset($reservationColumns['guests_count'])) {
                $this->addSql('ALTER TABLE reservation DROP COLUMN guests_count');
            }

            if (isset($reservationColumns['date_reservation'])) {
                $this->addSql('ALTER TABLE reservation DROP COLUMN date_reservation');
            }

            if (isset($reservationColumns['statut'])) {
                $this->addSql('ALTER TABLE reservation DROP COLUMN statut');
            }
        }

        if ($schemaManager->tablesExist(['reservation_guest'])) {
            $reservationGuestColumns = $schemaManager->listTableColumns('reservation_guest');

            if (isset($reservationGuestColumns['id_guest']) && !isset($reservationGuestColumns['id'])) {
                $this->addSql('ALTER TABLE reservation_guest CHANGE id_guest id INT NOT NULL AUTO_INCREMENT');
            }
        }
    }
}