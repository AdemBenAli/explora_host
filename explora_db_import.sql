-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 29, 2026 at 08:32 PM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `explora_db_import`
--

-- --------------------------------------------------------

--
-- Table structure for table `activite`
--

CREATE TABLE `activite` (
  `idActivite` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `categorie` varchar(100) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `ville` varchar(255) DEFAULT NULL,
  `lieu` varchar(255) DEFAULT NULL,
  `prix` double DEFAULT 0,
  `duree` int(11) DEFAULT 0,
  `image` varchar(500) DEFAULT NULL,
  `nombrePlaces` int(11) DEFAULT 0,
  `disponible` tinyint(1) DEFAULT 1,
  `date_activite` date DEFAULT NULL,
  `heure_debut` time DEFAULT NULL,
  `heure_fin` time DEFAULT NULL,
  `id_agent` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activite_voyage`
--

CREATE TABLE `activite_voyage` (
  `idActivite` int(11) NOT NULL,
  `idVoyage` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `type` varchar(255) DEFAULT NULL,
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`type`, `id`) VALUES
('SUPER_ADMIN', 6);

-- --------------------------------------------------------

--
-- Table structure for table `agent`
--

CREATE TABLE `agent` (
  `id` int(11) NOT NULL,
  `nomAgence` varchar(255) DEFAULT NULL,
  `nomLegalAgence` varchar(255) DEFAULT NULL,
  `descriptionAgence` text DEFAULT NULL,
  `logoUrl` varchar(500) DEFAULT NULL,
  `paysAgence` varchar(100) DEFAULT NULL,
  `villeAgence` varchar(100) DEFAULT NULL,
  `adresseAgence` text DEFAULT NULL,
  `codePostalAgence` varchar(20) DEFAULT NULL,
  `telephoneAgence` varchar(50) DEFAULT NULL,
  `emailAgence` varchar(255) DEFAULT NULL,
  `siteWebUrl` varchar(500) DEFAULT NULL,
  `numeroRegistreCommerce` varchar(100) DEFAULT NULL,
  `numeroFiscal` varchar(100) DEFAULT NULL,
  `numeroLicenceAgence` varchar(100) DEFAULT NULL,
  `dateEnregistrement` datetime(6) DEFAULT NULL,
  `docRegistreCommerceUrl` varchar(500) DEFAULT NULL,
  `docMatriculeFiscalUrl` varchar(500) DEFAULT NULL,
  `docLicenceAgenceUrl` varchar(500) DEFAULT NULL,
  `docPieceIdentiteRectoUrl` varchar(500) DEFAULT NULL,
  `docPieceIdentiteVersoUrl` varchar(500) DEFAULT NULL,
  `docJustificatifAdresseUrl` varchar(500) DEFAULT NULL,
  `docRibOuReleveBancaireUrl` varchar(500) DEFAULT NULL,
  `docAssuranceUrl` varchar(500) DEFAULT NULL,
  `statutVerification` enum('EN_ATTENTE','EN_COURS','VALIDE','REFUSE','SUSPENDU') DEFAULT 'EN_ATTENTE',
  `dateSoumission` datetime(6) DEFAULT NULL,
  `dateValidation` datetime(6) DEFAULT NULL,
  `valideParAdminId` int(11) DEFAULT NULL,
  `raisonRefus` text DEFAULT NULL,
  `notesAdmin` text DEFAULT NULL,
  `estSuspendu` bit(1) DEFAULT b'0',
  `raisonSuspension` text DEFAULT NULL,
  `dateSuspension` datetime(6) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `agence` varchar(255) DEFAULT NULL,
  `domaine` enum('VOYAGE','TOURISME','HEBERGEMENT','TRANSPORT','ACTIVITES','RESTAURATION','CULTURE','SPORT','AVENTURE','DETENTE') DEFAULT NULL,
  `emailProfessionnel` varchar(255) DEFAULT NULL,
  `matricule` int(11) NOT NULL,
  `pays` varchar(255) DEFAULT NULL,
  `telephoneEntreprise` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `agent`
--

INSERT INTO `agent` (`id`, `nomAgence`, `nomLegalAgence`, `descriptionAgence`, `logoUrl`, `paysAgence`, `villeAgence`, `adresseAgence`, `codePostalAgence`, `telephoneAgence`, `emailAgence`, `siteWebUrl`, `numeroRegistreCommerce`, `numeroFiscal`, `numeroLicenceAgence`, `dateEnregistrement`, `docRegistreCommerceUrl`, `docMatriculeFiscalUrl`, `docLicenceAgenceUrl`, `docPieceIdentiteRectoUrl`, `docPieceIdentiteVersoUrl`, `docJustificatifAdresseUrl`, `docRibOuReleveBancaireUrl`, `docAssuranceUrl`, `statutVerification`, `dateSoumission`, `dateValidation`, `valideParAdminId`, `raisonRefus`, `notesAdmin`, `estSuspendu`, `raisonSuspension`, `dateSuspension`, `adresse`, `agence`, `domaine`, `emailProfessionnel`, `matricule`, `pays`, `telephoneEntreprise`) VALUES
(3, 'Explora Travel', 'Explora Travel SARL', 'Agence de voyage test', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'VALIDE', NULL, NULL, NULL, NULL, NULL, b'0', NULL, NULL, NULL, NULL, NULL, NULL, 123456, NULL, 71234567);

-- --------------------------------------------------------

--
-- Table structure for table `analyse_saisonniere`
--

CREATE TABLE `analyse_saisonniere` (
  `id` int(11) NOT NULL,
  `saison` varchar(50) DEFAULT NULL,
  `type_voyage` varchar(100) DEFAULT NULL,
  `preference_dominante` varchar(100) DEFAULT NULL,
  `budget_moyen` double DEFAULT NULL,
  `duree_moyenne` bigint(20) DEFAULT NULL,
  `nombre_voyages` int(11) DEFAULT NULL,
  `type_voyage_dominant` varchar(100) DEFAULT NULL,
  `date_analyse` date DEFAULT NULL,
  `annee` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `avis`
--

CREATE TABLE `avis` (
  `id_avis` int(11) NOT NULL,
  `id_hebergement` int(11) NOT NULL,
  `nom_auteur` varchar(255) DEFAULT 'Guest',
  `note` int(11) NOT NULL,
  `commentaire` text DEFAULT NULL,
  `date_avis` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `avis`
--

INSERT INTO `avis` (`id_avis`, `id_hebergement`, `nom_auteur`, `note`, `commentaire`, `date_avis`) VALUES
(1, 1, 'Guest', 3, 'wow', '2026-03-01 22:52:45'),
(2, 1, 'Guest', 3, 'exellent', '2026-03-01 22:57:43'),
(3, 1, 'Guest', 1, 'bad', '2026-03-01 22:57:51');

-- --------------------------------------------------------

--
-- Table structure for table `avis_activite`
--

CREATE TABLE `avis_activite` (
  `idAvis` int(11) NOT NULL,
  `idActivite` int(11) NOT NULL,
  `idVoyageur` int(11) NOT NULL,
  `nomVoyageur` varchar(255) DEFAULT NULL,
  `note` int(11) DEFAULT NULL CHECK (`note` between 1 and 5),
  `commentaire` text DEFAULT NULL,
  `dateAvis` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `axe_activite`
--

CREATE TABLE `axe_activite` (
  `avecGroupe` bit(1) NOT NULL,
  `avecGuide` bit(1) NOT NULL,
  `budgetMax` double NOT NULL,
  `budgetMin` double NOT NULL,
  `niveau` varchar(255) DEFAULT NULL,
  `typesActivite` varchar(255) DEFAULT NULL,
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `axe_hebergement`
--

CREATE TABLE `axe_hebergement` (
  `accepteColocation` bit(1) NOT NULL,
  `budgetMax` double NOT NULL,
  `budgetMin` double NOT NULL,
  `categorieHotel` varchar(255) DEFAULT NULL,
  `nombreDeChambre` int(11) NOT NULL,
  `services` varchar(255) DEFAULT NULL,
  `typeHebergement` varchar(255) DEFAULT NULL,
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `axe_transport`
--

CREATE TABLE `axe_transport` (
  `accepteEscale` bit(1) NOT NULL,
  `budgetMax` double NOT NULL,
  `budgetMin` double NOT NULL,
  `classe` varchar(255) DEFAULT NULL,
  `toleranceTemps` int(11) NOT NULL,
  `typeTransport` varchar(255) DEFAULT NULL,
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `axe_voyage`
--

CREATE TABLE `axe_voyage` (
  `budgetMax` double NOT NULL,
  `budgetMin` double NOT NULL,
  `destinations` varchar(255) DEFAULT NULL,
  `duree` int(11) NOT NULL,
  `saisonsPreferees` varchar(255) DEFAULT NULL,
  `typesVoyages` varchar(255) DEFAULT NULL,
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `billet`
--

CREATE TABLE `billet` (
  `id` int(11) NOT NULL,
  `transport_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `voyage_id` int(11) DEFAULT NULL,
  `nombre_places` int(11) DEFAULT NULL,
  `prix_total` double DEFAULT NULL,
  `date_reservation` date DEFAULT NULL,
  `statut` varchar(20) DEFAULT 'EN_ATTENTE',
  `created_at` datetime DEFAULT NULL,
  `qr_code` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billet`
--

INSERT INTO `billet` (`id`, `transport_id`, `user_id`, `voyage_id`, `nombre_places`, `prix_total`, `date_reservation`, `statut`, `created_at`, `qr_code`) VALUES
(1, 1, 1, NULL, 2, 20, '2026-02-04', 'PAYE', NULL, NULL),
(2, 2, 1, 1, 1, 350, '2026-02-03', 'CONFIRME', NULL, NULL),
(3, 3, 2, 2, 2, 300, '2026-02-02', 'PAYE', NULL, NULL),
(4, 4, 1, 3, 3, 600, '2026-02-01', 'CONFIRME', NULL, NULL),
(5, 1, 1, NULL, 1, 10, '2026-02-05', 'EN_ATTENTE', NULL, NULL),
(6, 7, 1, 4, 2, 560, '2026-02-04', 'EN_ATTENTE', NULL, NULL),
(7, 5, 2, NULL, 4, 120, '2026-02-03', 'ANNULE', NULL, NULL),
(8, 4, 1, NULL, 2, 400, '2026-02-07', 'EN_ATTENTE', NULL, NULL),
(9, 4, 1, NULL, 4, 800, '2026-02-07', 'EN_ATTENTE', NULL, NULL),
(17, 2, 1, NULL, 1, 350, '2026-03-02', 'EN_ATTENTE', NULL, NULL),
(18, 7, 1, NULL, 1, 260, '2026-03-02', 'EN_ATTENTE', NULL, NULL),
(20, 2, 1, NULL, 1, 350, '2026-03-02', 'EN_ATTENTE', NULL, NULL),
(21, 2, 1, NULL, 1, 350, '2026-03-02', 'EN_ATTENTE', NULL, NULL),
(22, 4, 1, NULL, 1, 278, '2026-03-02', 'EN_ATTENTE', NULL, NULL),
(23, 2, 1, NULL, 1, 350, '2026-03-02', 'EN_ATTENTE', NULL, NULL),
(24, 1, 1, NULL, 1, 9, '2026-03-02', 'EN_ATTENTE', NULL, NULL),
(25, 7, 5, NULL, 1, 591.36, '2026-04-21', 'EN_ATTENTE', NULL, NULL),
(26, 7, 1, NULL, 1, 591.36, '2026-04-21', 'EN_ATTENTE', NULL, NULL),
(27, 7, 1, NULL, 1, 645.57, '2026-04-21', 'EN_ATTENTE', NULL, NULL),
(28, 7, 1, NULL, 1, 645.57, '2026-04-21', 'EN_ATTENTE', NULL, NULL),
(29, 7, 5, NULL, 1, 700, '2026-04-28', 'EN_ATTENTE', '2026-04-28 23:24:23', NULL),
(30, 7, 5, NULL, 1, 700, '2026-04-29', 'EN_ATTENTE', '2026-04-29 02:12:46', NULL),
(31, 2, 5, NULL, 1, 875, '2026-04-29', 'EN_ATTENTE', '2026-04-29 02:53:08', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `boutique_velo`
--

CREATE TABLE `boutique_velo` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `adresse` varchar(255) NOT NULL,
  `ville` varchar(50) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `reduction` int(11) DEFAULT 50
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `boutique_velo`
--

INSERT INTO `boutique_velo` (`id`, `nom`, `adresse`, `ville`, `telephone`, `latitude`, `longitude`, `reduction`) VALUES
(1, 'EcoBike Tunis Centre', 'Avenue Habib Bourguiba', 'Tunis', '+216 71 123 456', '36.80650000', '10.18150000', 50),
(2, 'Velo City La Marsa', 'Avenue Taieb Mhiri', 'La Marsa', '+216 71 234 567', '36.87810000', '10.32500000', 50),
(3, 'Cycle Express Ariana', 'Centre Ville Ariana', 'Ariana', '+216 71 345 678', '36.86250000', '10.19560000', 50),
(4, 'Green Bike La Soukra', 'Avenue Principale', 'La Soukra', '+216 71 456 789', '36.86640000', '10.11080000', 50),
(5, 'TuniBike Carthage', 'Route de La Marsa', 'Carthage', '+216 71 567 890', '36.85310000', '10.32310000', 50),
(6, 'V??lo Plus Ben Arous', 'Centre Commercial', 'Ben Arous', '+216 71 678 901', '36.75400000', '10.21850000', 50),
(7, 'Bike Shop Sfax', 'Rue de la R??publique', 'Sfax', '+216 74 234 567', '34.74060000', '10.76030000', 50),
(8, 'Cycle Sport Sousse', 'Boulevard Yahia Ibn Omar', 'Sousse', '+216 73 345 678', '35.82560000', '10.63690000', 50),
(9, 'V??lo Express Nabeul', 'Avenue Farhat Hached', 'Nabeul', '+216 72 456 789', '36.45160000', '10.73540000', 50),
(10, 'EcoBike Hammamet', 'Avenue de la Corniche', 'Hammamet', '+216 72 789 012', '36.40000000', '10.61670000', 50),
(11, 'V??lo Shop La Marsa', '12 Avenue Habib Bourguiba, La Marsa', 'La Marsa', '71 123 456', '36.87810000', '10.32500000', 50),
(12, 'Bike Center Carthage', '45 Rue de Carthage, Carthage', 'Carthage', '71 234 567', '36.85310000', '10.32310000', 50),
(13, 'Eco Cycles Ariana', '8 Avenue de la R??publique, Ariana', 'Ariana', '71 345 678', '36.86250000', '10.19560000', 50),
(14, 'Tunis V??lo Station', '33 Avenue de France, Tunis', 'Tunis', '71 456 789', '36.80650000', '10.18150000', 50),
(15, 'La Soukra Bikes', '22 Rue Principale, La Soukra', 'La Soukra', '71 567 890', '36.86640000', '10.11080000', 50),
(16, 'Sidi Bou Said Cycles', '5 Place du Village, Sidi Bou Said', 'Sidi Bou Said', '71 678 901', '36.86860000', '10.34060000', 50),
(17, 'Sousse Bike Rental', '18 Boulevard de la Corniche, Sousse', 'Sousse', '73 123 456', '35.82560000', '10.63690000', 50),
(18, 'Sfax Eco V??lo', '25 Avenue Hedi Chaker, Sfax', 'Sfax', '74 234 567', '34.74060000', '10.76030000', 50),
(19, 'Bizerte Cycles', '12 Quai Tarak Ben Ammar, Bizerte', 'Bizerte', '72 345 678', '37.27440000', '9.87390000', 50),
(20, 'Hammamet Bike Shop', '7 Avenue de la Mer, Hammamet', 'Hammamet', '72 456 789', '36.40000000', '10.61670000', 50),
(21, 'V??lo Shop Tunis', '12 Avenue Habib Bourguiba', 'Tunis', '71 123 456', '36.80650000', '10.18150000', 50),
(22, 'Bike Store La Marsa', '45 Rue de la Plage', 'La Marsa', '71 234 567', '36.87810000', '10.32500000', 50),
(23, 'Carthage Cycles', '8 Avenue de Carthage', 'Carthage', '71 345 678', '36.85310000', '10.32310000', 50),
(24, 'Ariana Bike Center', '23 Rue Principale', 'Ariana', '71 456 789', '36.86250000', '10.19560000', 50),
(25, 'Soukra V??los', '15 Boulevard de la R??publique', 'La Soukra', '71 567 890', '36.86640000', '10.11080000', 50),
(26, 'Sidi Bou V??los', '7 Rue du Port', 'Sidi Bou Said', '71 678 901', '36.86860000', '10.34060000', 50),
(27, 'Sousse Bike Shop', '34 Avenue de la Corniche', 'Sousse', '73 123 456', '35.82560000', '10.63690000', 50),
(28, 'Sfax Cycles', '56 Rue de la Libert??', 'Sfax', '74 234 567', '34.74060000', '10.76030000', 50),
(29, 'Bizerte V??los', '12 Avenue Habib Bourguiba', 'Bizerte', '72 345 678', '37.27440000', '9.87390000', 50),
(30, 'Hammamet Bike Center', '89 Avenue du Tourisme', 'Hammamet', '72 456 789', '36.40000000', '10.61670000', 50);

-- --------------------------------------------------------

--
-- Table structure for table `client`
--

CREATE TABLE `client` (
  `adresse` varchar(255) DEFAULT NULL,
  `badge` enum('BRONZE','ARGENT','OR','PLATINE','DIAMANT','EXPLORATEUR','AVENTURIER','GUIDE_EXPERT','VOYAGEUR_FREQUENT','AMBASSADEUR') DEFAULT NULL,
  `paysResidence` varchar(255) DEFAULT NULL,
  `scoreFidelite` int(11) NOT NULL,
  `ville` varchar(255) DEFAULT NULL,
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client`
--

INSERT INTO `client` (`adresse`, `badge`, `paysResidence`, `scoreFidelite`, `ville`, `id`) VALUES
(NULL, 'BRONZE', NULL, 0, NULL, 1),
(NULL, 'BRONZE', NULL, 0, NULL, 2);

-- --------------------------------------------------------

--
-- Table structure for table `code_promo_velo`
--

CREATE TABLE `code_promo_velo` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `origine` varchar(50) NOT NULL,
  `destination` varchar(50) NOT NULL,
  `code` varchar(20) NOT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_utilisation` datetime DEFAULT NULL,
  `utilise` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupon`
--

CREATE TABLE `coupon` (
  `id` int(11) NOT NULL,
  `actif` bit(1) NOT NULL,
  `clientId` int(11) NOT NULL,
  `code` varchar(255) NOT NULL,
  `dateCreation` datetime(6) NOT NULL,
  `dateExpiration` date NOT NULL,
  `montantMinimum` double NOT NULL,
  `pourcentage` double NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `valeurReduction` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctrine_migration_versions`
--

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctrine_migration_versions`
--

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20260418143711', '2026-04-28 22:49:47', 0),
('DoctrineMigrations\\Version20260428110000', '2026-04-28 23:49:55', 28),
('DoctrineMigrations\\Version20260428170000', '2026-04-28 22:50:55', 0);

-- --------------------------------------------------------

--
-- Table structure for table `eco_scores`
--

CREATE TABLE `eco_scores` (
  `user_id` int(11) NOT NULL,
  `points_actuels` int(11) DEFAULT 0,
  `points_total` int(11) DEFAULT 0,
  `niveau_actuel` int(11) DEFAULT 0,
  `reduction_disponible` tinyint(1) DEFAULT 0,
  `voyages_eco` int(11) DEFAULT 0,
  `co2_economise` double DEFAULT 0,
  `derniere_maj` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `eco_scores`
--

INSERT INTO `eco_scores` (`user_id`, `points_actuels`, `points_total`, `niveau_actuel`, `reduction_disponible`, `voyages_eco`, `co2_economise`, `derniere_maj`) VALUES
(1, 80, 80, 1, 1, 2, 41, '2026-03-02 10:32:39');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` longtext NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `user_id`, `message`, `created_at`) VALUES
(1, 1, 'it was a bad experience i\'ll not use this again !', '2026-04-18 16:06:46'),
(2, 1, 'great experience but it could be better', '2026-04-18 16:37:56'),
(3, 1, 'soo expensive !', '2026-04-18 17:45:52'),
(4, 1, 'too bad', '2026-04-20 10:12:11'),
(5, 1, 'wow oh my god very wow !', '2026-04-21 00:06:55'),
(6, 1, 'wow what an experience !', '2026-04-29 02:48:10');

-- --------------------------------------------------------

--
-- Table structure for table `hebergement`
--

CREATE TABLE `hebergement` (
  `id_hebergement` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `type` varchar(100) DEFAULT NULL,
  `pays` varchar(100) DEFAULT NULL,
  `localisation` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `prix_par_nuit` double NOT NULL DEFAULT 0,
  `latitude` double DEFAULT 0,
  `longitude` double DEFAULT 0,
  `note_moyenne` double DEFAULT 0,
  `image_path` varchar(500) DEFAULT NULL,
  `special_couple` tinyint(1) DEFAULT 0,
  `under18_allowed` tinyint(1) DEFAULT 0,
  `sea_view` tinyint(1) DEFAULT 0,
  `capacite` int(11) DEFAULT NULL,
  `date_creation` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hebergement`
--

INSERT INTO `hebergement` (`id_hebergement`, `nom`, `type`, `pays`, `localisation`, `description`, `prix_par_nuit`, `latitude`, `longitude`, `note_moyenne`, `image_path`, `special_couple`, `under18_allowed`, `sea_view`, `capacite`, `date_creation`, `updated_at`) VALUES
(1, 'radisson', 'Hotel', '????????', '', 'wow', 200, 36.87962060502676, 9.975585937500002, 0, 'uploads/hotel_dfc3a25f71b842319725db2e6fc88688.jpg', 1, 0, 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `paiement`
--

CREATE TABLE `paiement` (
  `id` int(11) NOT NULL,
  `adresse_facturation` text DEFAULT NULL,
  `date_paiement` datetime(6) DEFAULT NULL,
  `devise` varchar(10) DEFAULT NULL,
  `methode_paiement` enum('CARTE','PAYPAL','VIREMENT') NOT NULL,
  `montant_paye` decimal(10,2) NOT NULL,
  `panier_id` int(11) NOT NULL,
  `reference_transaction` varchar(100) DEFAULT NULL,
  `statut` enum('EN_ATTENTE','VALIDE','ECHOUE','REMBOURSE') NOT NULL,
  `token_securise` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `paiement`
--

INSERT INTO `paiement` (`id`, `adresse_facturation`, `date_paiement`, `devise`, `methode_paiement`, `montant_paye`, `panier_id`, `reference_transaction`, `statut`, `token_securise`) VALUES
(28, 'ariana, ariana essoghra, 5180, Tunisia', '2026-02-16 10:05:56.000000', 'USD', 'CARTE', '2145.00', 24, 'pi_3T1NkeDEjimsBBPq0KwoH5cQ', 'VALIDE', '****2057'),
(29, 'mahdia, ariana, 5180, Tunisia', '2026-02-16 11:24:18.000000', 'USD', 'CARTE', '2308.90', 29, 'pi_3T1OyTDEjimsBBPq0FVn2WH4', 'VALIDE', '****2721'),
(30, '123 Main Street, mahdia, 5180, Tunisia', '2026-02-21 03:52:09.000000', 'USD', 'CARTE', '1320.00', 30, 'pi_3T36IeDEjimsBBPq1JbljZDK', 'VALIDE', '****5447'),
(31, 'Mahdia, Tunisia, Agropoli, 5171, Italy', '2026-02-23 09:59:40.000000', 'USD', 'CARTE', '825.00', 31, 'pi_3T3uzPDEjimsBBPq0ei08s1j', 'VALIDE', '****4220'),
(32, 'Ksour Essef, Mahdia, Tunisia, Monastir, 5180, Tunisia', '2026-02-28 07:41:11.000000', 'USD', 'CARTE', '3520.00', 32, 'pi_3T5hD8DEjimsBBPq0zh0Q5TE', 'VALIDE', '****8151'),
(33, '??????????????, Tunisia, Erebuni Fortress, 5180, Armenia', '2026-02-28 09:42:05.000000', 'USD', 'CARTE', '1813.90', 33, 'pi_3T5j68DEjimsBBPq0mMnSftw', 'VALIDE', '****8151'),
(34, 'Tunisia, Adelia Maria, 1212, Argentina', '2026-02-28 09:50:37.000000', 'USD', 'CARTE', '825.00', 34, 'pi_3T5jEPDEjimsBBPq12rJaX6O', 'VALIDE', '****8151'),
(35, 'Niger State, Nigeria, Adavi, 5180, Nigeria', '2026-02-28 10:01:11.000000', 'USD', 'CARTE', '3558.90', 35, 'pi_3T5jOcDEjimsBBPq05wC3iXJ', 'VALIDE', '****8151'),
(36, 'Rosso S??n??gal, SL, Senegal, Louga, 5180, Senegal', '2026-02-28 10:07:18.000000', 'USD', 'CARTE', '1275.05', 36, 'pi_3T5jUXDEjimsBBPq1SEp0ePI', 'VALIDE', '****8151'),
(37, 'Mahdia, Tunisia, Petran, 4232, Albania', '2026-03-02 00:52:49.000000', 'USD', 'CARTE', '1320.00', 37, 'pi_3T6Jn3DEjimsBBPq0ElJZDF5', 'VALIDE', '****8151'),
(38, '?????????? ??????????/?????????? ??????????, 7, Kharkiv Raion, ???????????????????????? ?????????????? ??????????????, 62466, Ukraine, Santa Cruz, 1123, Aruba', '2026-03-02 07:07:56.000000', 'USD', 'CARTE', '10517.76', 38, 'pi_3T6Pe4DEjimsBBPq1wpIyTh3', 'VALIDE', '****8151'),
(39, '5129 ??????????????, Tunisia, Petran, 132, Albania', '2026-03-02 10:37:44.000000', 'USD', 'CARTE', '6988.52', 39, 'pi_3T6Sv6DEjimsBBPq0affVUwZ', 'VALIDE', '****8151'),
(40, NULL, '2026-04-07 01:44:26.000000', NULL, '', '0.00', 0, NULL, '', NULL),
(41, NULL, '2026-04-07 01:56:07.000000', 'USD', 'CARTE', '13227.90', 40, 'PAY-FFBDBEA1-1775519767', 'VALIDE', 'a8179983350b455c2bfdcad04e96da04'),
(42, 'tunisie, 5180, TN', '2026-04-07 02:37:20.000000', 'USD', 'CARTE', '988.90', 41, 'PAY-40AABAA2-1775522240', 'VALIDE', '9d4626be7a4cc874cb5040167dca96f2'),
(43, 'ariana, 5000, FR', '2026-04-07 02:47:49.000000', 'USD', 'CARTE', '2750.00', 42, 'PAY-A73E2CE9-1775522869', 'VALIDE', 'd01946133aa35c177ef092c3a5c43fc3'),
(44, 'errr, 3232, TN', '2026-04-07 02:52:48.000000', 'USD', 'CARTE', '4950.00', 43, 'PAY-E47B1FEB-1775523168', 'VALIDE', '****8066'),
(45, 'tunisie, 5180, IT', '2026-04-07 03:30:53.000000', 'USD', 'CARTE', '2640.00', 44, 'PAY-50EB6FAD-1775525453', 'VALIDE', '****8066'),
(46, 'tunisie, Milan, 5180, IT', '2026-04-07 15:04:49.000000', 'USD', 'CARTE', '2640.00', 45, 'PAY-66B116A6-1775567089', 'VALIDE', '****8066'),
(47, 'tunisie, Seville, 5180, ES', '2026-04-07 15:32:49.000000', 'USD', 'CARTE', '1260.00', 46, 'PAY-7B4E72EA-1775568769', 'VALIDE', '****8066'),
(48, 'tunisie, Nice, 5180, FR', '2026-04-07 16:42:01.000000', 'USD', 'CARTE', '4840.00', 47, 'PAY-5C749114-1775572921', 'VALIDE', '****8066'),
(50, 'tunisie, Naples, 5180, IT', '2026-04-07 17:15:03.000000', 'USD', 'CARTE', '5250.00', 49, 'PAY-39AD9B35-1775574903', 'VALIDE', '****3353'),
(51, 'Ksour Essef, Mahdia, Tunisia, Ariana, 5180, TN', '2026-04-11 17:51:04.000000', 'USD', 'CARTE', '2750.00', 50, 'PAY-55995A5C-1775922664', 'VALIDE', '****6240'),
(52, '1 Afghan Street, Lees, OL1 4EG, United Kingdom, Kandahar, 5180, AF', '2026-04-11 19:26:55.000000', 'USD', 'CARTE', '3520.00', 51, 'PAY-1E8CA69A-1775928415', 'VALIDE', '****6240'),
(53, '16000 Angoul??me, France, The Quarter, 435, AI', '2026-04-11 19:28:00.000000', 'USD', 'CARTE', '2560.00', 52, 'PAY-1C900B34-1775928480', 'VALIDE', '****6240'),
(54, 'Odesa Raion, Odesa, 65082, Ukraine, Sanabis, 5180, BH', '2026-04-11 19:51:32.000000', 'USD', 'CARTE', '3520.00', 53, 'pi_3TL5gsDEjimsBBPq0SBkstDy', 'VALIDE', '****1117'),
(55, 'Berbera, Sahil, Somaliland, Bagatelle, 5180, BB', '2026-04-11 20:10:26.000000', 'USD', 'CARTE', '2750.00', 54, 'pi_3TL5zBDEjimsBBPq1t6YYd5q', 'VALIDE', '****6240'),
(56, 'Jeddah, Makkah Region, Saudi Arabia, Jeddah, 4343, SA', '2026-04-15 00:46:56.000000', 'USD', 'CARTE', '5500.00', 55, 'pi_3TMFjMDEjimsBBPq1Ivc6z0j', 'VALIDE', '****0757'),
(57, 'babafe, Bridgetown, 5180, BB', '2026-04-18 15:23:47.000000', 'USD', 'CARTE', '2750.00', 56, 'pi_3TNYqYDEjimsBBPq1mF6DxbG', 'VALIDE', '****8258'),
(58, 'Algiers, Algeria, El Tarf, 5180, DZ', '2026-04-18 16:06:18.000000', 'USD', 'CARTE', '988.90', 57, 'pi_3TNZVlDEjimsBBPq1i8cW3DP', 'VALIDE', '****8258'),
(59, 'Shire Of Bruce Rock, Australia, Rockley, 5180, BB', '2026-04-18 16:37:37.000000', 'USD', 'CARTE', '2750.00', 58, 'pi_3TNa05DEjimsBBPq0ER7ZZMF', 'VALIDE', '****8258'),
(60, 'Ksour Essef, Mahdia, Tunisia, Rangpur, 5180, BD', '2026-04-18 17:45:32.000000', 'USD', 'CARTE', '825.00', 59, 'pi_3TNb3nDEjimsBBPq1QsCsCFw', 'VALIDE', '****1752'),
(61, '??????????????????, Kursk Oblast, Russia, Andros Town, 5180, BS', '2026-04-18 18:49:30.000000', 'TND', 'CARTE', '2380.37', 60, 'pi_3TNc3iDEjimsBBPq1xTy96a1', 'VALIDE', '****1752'),
(62, 'The Garden, Saint James, Barbados, The Garden, 5180, BB', '2026-04-20 10:11:57.000000', 'USD', 'CARTE', '2750.00', 61, 'pi_3TOCvsDEjimsBBPq1jcBq58F', 'VALIDE', '****1752'),
(63, 'Ksibet El Mediouni, Ksibet El Mediouni, 5031, TN', '2026-04-21 00:06:23.000000', 'USD', 'CARTE', '825.00', 62, 'pi_3TOPxMDEjimsBBPq03aMSi7d', 'VALIDE', '****5636'),
(64, 'Ksibet Echat, Ksibet Ech Chott, 4061, TN', '2026-04-29 02:47:50.000000', 'USD', 'CARTE', '990.00', 63, 'pi_3TRMI5DEjimsBBPq1Zguo1j2', 'VALIDE', '****4821'),
(65, 'Bab El Bhar, Sousse, 4000, TN', '2026-04-29 02:54:16.000000', 'USD', 'CARTE', '962.50', 65, 'pi_3TRMOJDEjimsBBPq0anjNXoR', 'VALIDE', '****4821');

-- --------------------------------------------------------

--
-- Table structure for table `panier`
--

CREATE TABLE `panier` (
  `id` int(11) NOT NULL,
  `code_promo` varchar(50) DEFAULT NULL,
  `date_creation` datetime(6) DEFAULT NULL,
  `date_modification` datetime(6) DEFAULT NULL,
  `montant_reduction` decimal(10,2) DEFAULT NULL,
  `montant_ttc` decimal(10,2) DEFAULT NULL,
  `montant_tva` decimal(10,2) DEFAULT NULL,
  `montant_total_ht` decimal(10,2) DEFAULT NULL,
  `statut` enum('ACTIF','ABANDONNE','VALIDE') NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `panier`
--

INSERT INTO `panier` (`id`, `code_promo`, `date_creation`, `date_modification`, `montant_reduction`, `montant_ttc`, `montant_tva`, `montant_total_ht`, `statut`, `user_id`) VALUES
(1, 'SAVE10', '2026-02-08 23:06:44.000000', '2026-02-09 00:05:03.000000', '629.30', '3955.60', '359.60', '3596.00', 'VALIDE', 1),
(2, NULL, '2026-02-09 00:12:49.000000', '2026-02-09 00:14:38.000000', '0.00', '1320.00', '120.00', '1200.00', 'VALIDE', 1),
(3, NULL, '2026-02-09 00:35:10.000000', '2026-02-09 00:35:34.000000', '0.00', '3520.00', '320.00', '3200.00', 'VALIDE', 1),
(4, NULL, '2026-02-09 03:03:10.000000', '2026-02-10 17:16:03.000000', '0.00', '6761.70', '614.70', '6147.00', 'VALIDE', 1),
(5, NULL, '2026-02-10 17:33:32.000000', '2026-02-11 00:16:42.000000', '0.00', '2308.90', '209.90', '2099.00', 'VALIDE', 1),
(6, NULL, '2026-02-11 00:29:12.000000', '2026-02-11 14:01:44.000000', '0.00', '1320.00', '120.00', '1200.00', 'VALIDE', 1),
(7, NULL, '2026-02-11 14:09:42.000000', '2026-02-11 14:14:00.000000', '0.00', '2750.00', '250.00', '2500.00', 'VALIDE', 1),
(8, NULL, '2026-02-11 14:20:00.000000', '2026-02-11 14:20:54.000000', '0.00', '825.00', '75.00', '750.00', 'VALIDE', 1),
(9, NULL, '2026-02-11 15:32:37.000000', '2026-02-11 15:33:56.000000', '0.00', '825.00', '75.00', '750.00', 'VALIDE', 1),
(10, NULL, '2026-02-11 17:12:42.000000', '2026-02-11 18:31:33.000000', '0.00', '3520.00', '320.00', '3200.00', 'VALIDE', 1),
(11, NULL, '2026-02-11 18:41:06.000000', '2026-02-11 18:41:36.000000', '0.00', '1320.00', '120.00', '1200.00', 'VALIDE', 1),
(12, NULL, '2026-02-11 18:44:48.000000', '2026-02-11 18:45:11.000000', '0.00', '2308.90', '209.90', '2099.00', 'VALIDE', 1),
(13, NULL, '2026-02-11 18:48:52.000000', '2026-02-11 18:49:11.000000', '0.00', '2475.00', '225.00', '2250.00', 'VALIDE', 1),
(14, NULL, '2026-02-11 18:53:18.000000', '2026-02-11 18:53:42.000000', '0.00', '10560.00', '960.00', '9600.00', 'VALIDE', 1),
(15, NULL, '2026-02-11 18:55:25.000000', '2026-02-11 18:55:48.000000', '0.00', '2750.00', '250.00', '2500.00', 'VALIDE', 1),
(16, NULL, '2026-02-11 18:58:30.000000', '2026-02-11 18:59:05.000000', '0.00', '4944.50', '449.50', '4495.00', 'VALIDE', 1),
(17, NULL, '2026-02-11 19:01:58.000000', '2026-02-11 19:02:29.000000', '0.00', '6600.00', '600.00', '6000.00', 'VALIDE', 1),
(18, NULL, '2026-02-11 19:03:44.000000', '2026-02-11 19:43:56.000000', '0.00', '14683.90', '1334.90', '13349.00', 'VALIDE', 1),
(19, NULL, '2026-02-11 20:04:36.000000', '2026-02-11 20:06:33.000000', '0.00', '7920.00', '720.00', '7200.00', 'VALIDE', 1),
(20, NULL, '2026-02-11 20:35:40.000000', '2026-02-11 20:38:40.000000', '0.00', '1320.00', '120.00', '1200.00', 'VALIDE', 1),
(21, NULL, '2026-02-14 20:09:15.000000', '2026-02-14 20:11:05.000000', '0.00', '4840.00', '440.00', '4400.00', 'VALIDE', 1),
(22, NULL, '2026-02-14 20:16:50.000000', '2026-02-14 20:18:19.000000', '0.00', '1320.00', '120.00', '1200.00', 'VALIDE', 2),
(23, NULL, '2026-02-14 23:58:06.000000', '2026-02-15 00:01:55.000000', '0.00', '5500.00', '500.00', '5000.00', 'VALIDE', 1),
(24, NULL, '2026-02-15 00:04:28.000000', '2026-02-16 10:05:56.000000', '0.00', '2145.00', '195.00', '1950.00', 'VALIDE', 1),
(26, NULL, '2026-02-16 01:59:08.000000', '2026-02-16 01:59:08.000000', '0.00', '0.00', '0.00', '0.00', 'ACTIF', 0),
(28, NULL, '2026-02-16 01:59:28.000000', '2026-02-16 01:59:28.000000', '0.00', '0.00', '0.00', '0.00', 'ACTIF', 0),
(29, NULL, '2026-02-16 10:07:15.000000', '2026-02-16 11:24:18.000000', '0.00', '2308.90', '209.90', '2099.00', 'VALIDE', 1),
(30, NULL, '2026-02-16 11:25:41.000000', '2026-02-21 03:52:09.000000', '0.00', '1320.00', '120.00', '1200.00', 'VALIDE', 1),
(31, 'SAVE10', '2026-02-21 03:52:19.000000', '2026-02-23 09:59:40.000000', '75.00', '825.00', '75.00', '750.00', 'VALIDE', 1),
(32, NULL, '2026-02-26 03:34:57.000000', '2026-02-28 07:41:11.000000', '0.00', '3520.00', '320.00', '3200.00', 'VALIDE', 1),
(33, NULL, '2026-02-28 07:42:01.000000', '2026-02-28 09:42:05.000000', '0.00', '1813.90', '164.90', '1649.00', 'VALIDE', 1),
(34, 'EXPL-PRRIC', '2026-02-28 09:42:40.000000', '2026-02-28 09:50:37.000000', '37.50', '825.00', '75.00', '750.00', 'VALIDE', 1),
(35, 'EXPL-39OVB', '2026-02-28 09:51:08.000000', '2026-02-28 10:01:11.000000', '180.00', '3558.90', '339.90', '3399.00', 'VALIDE', 1),
(36, 'EXPL-7RF18', '2026-02-28 10:01:42.000000', '2026-02-28 10:07:18.000000', '44.95', '1275.05', '120.00', '1200.00', 'VALIDE', 1),
(37, NULL, '2026-02-28 10:07:57.000000', '2026-03-02 00:52:49.000000', '0.00', '1320.00', '120.00', '1200.00', 'VALIDE', 1),
(38, NULL, '2026-03-02 00:53:09.000000', '2026-03-02 07:07:56.000000', '0.00', '10517.76', '956.16', '9561.60', 'VALIDE', 1),
(39, NULL, '2026-03-02 07:09:26.000000', '2026-03-02 10:37:44.000000', '0.00', '6988.52', '635.32', '6353.20', 'VALIDE', 1),
(40, 'EXPL-FRGPQ', '2026-03-02 10:38:15.000000', '2026-04-07 01:56:07.000000', '629.90', '13227.90', '1259.80', '12598.00', 'VALIDE', 1),
(41, NULL, '2026-04-07 01:56:07.000000', '2026-04-07 02:37:20.000000', '0.00', '988.90', '89.90', '899.00', 'VALIDE', 1),
(42, NULL, '2026-04-07 02:37:20.000000', '2026-04-07 02:47:49.000000', '0.00', '2750.00', '250.00', '2500.00', 'VALIDE', 1),
(43, NULL, '2026-04-07 02:47:49.000000', '2026-04-07 02:52:48.000000', '0.00', '4950.00', '450.00', '4500.00', 'VALIDE', 1),
(44, NULL, '2026-04-07 02:52:48.000000', '2026-04-07 03:30:53.000000', '0.00', '2640.00', '240.00', '2400.00', 'VALIDE', 1),
(45, NULL, '2026-04-07 03:30:59.000000', '2026-04-07 15:04:49.000000', '0.00', '2640.00', '240.00', '2400.00', 'VALIDE', 1),
(46, 'EXPL-FRGPQ', '2026-04-07 15:04:59.000000', '2026-04-07 15:32:49.000000', '60.00', '1260.00', '120.00', '1200.00', 'VALIDE', 1),
(47, NULL, '2026-04-07 15:32:52.000000', '2026-04-07 16:42:01.000000', '0.00', '4840.00', '440.00', '4400.00', 'VALIDE', 1),
(49, 'EXPL-FRGPQ', '2026-04-07 16:43:06.000000', '2026-04-07 17:15:03.000000', '250.00', '5250.00', '500.00', '5000.00', 'VALIDE', 1),
(50, NULL, '2026-04-07 17:15:07.000000', '2026-04-11 17:51:04.000000', '0.00', '2750.00', '250.00', '2500.00', 'VALIDE', 1),
(51, NULL, '2026-04-11 17:51:12.000000', '2026-04-11 19:26:55.000000', '0.00', '3520.00', '320.00', '3200.00', 'VALIDE', 1),
(52, 'SCRATCH-C9A91D9E15', '2026-04-11 19:26:58.000000', '2026-04-11 19:28:00.000000', '960.00', '2560.00', '320.00', '3200.00', 'VALIDE', 1),
(53, NULL, '2026-04-11 19:28:04.000000', '2026-04-11 19:51:32.000000', '0.00', '3520.00', '320.00', '3200.00', 'VALIDE', 1),
(54, NULL, '2026-04-11 19:51:35.000000', '2026-04-11 20:10:26.000000', '0.00', '2750.00', '250.00', '2500.00', 'VALIDE', 1),
(55, NULL, '2026-04-11 20:10:30.000000', '2026-04-15 00:46:56.000000', '0.00', '5500.00', '500.00', '5000.00', 'VALIDE', 1),
(56, NULL, '2026-04-15 00:47:02.000000', '2026-04-18 15:23:47.000000', '0.00', '2750.00', '250.00', '2500.00', 'VALIDE', 1),
(57, NULL, '2026-04-18 15:23:55.000000', '2026-04-18 16:06:18.000000', '0.00', '988.90', '89.90', '899.00', 'VALIDE', 1),
(58, NULL, '2026-04-18 16:06:21.000000', '2026-04-18 16:37:37.000000', '0.00', '2750.00', '250.00', '2500.00', 'VALIDE', 1),
(59, NULL, '2026-04-18 16:37:40.000000', '2026-04-18 17:45:32.000000', '0.00', '825.00', '75.00', '750.00', 'VALIDE', 1),
(60, NULL, '2026-04-18 17:45:36.000000', '2026-04-18 18:49:30.000000', '0.00', '825.00', '75.00', '750.00', 'VALIDE', 1),
(61, NULL, '2026-04-18 18:49:35.000000', '2026-04-20 10:11:57.000000', '0.00', '2750.00', '250.00', '2500.00', 'VALIDE', 1),
(62, NULL, '2026-04-20 10:12:03.000000', '2026-04-21 00:06:23.000000', '0.00', '825.00', '75.00', '750.00', 'VALIDE', 1),
(63, NULL, '2026-04-21 00:06:31.000000', '2026-04-29 02:47:50.000000', '0.00', '990.00', '90.00', '900.00', 'VALIDE', 1),
(64, NULL, '2026-04-29 00:16:28.000000', '2026-04-29 00:17:25.000000', '0.00', '220.00', '20.00', '200.00', 'ACTIF', 5),
(65, NULL, '2026-04-29 02:47:54.000000', '2026-04-29 02:54:16.000000', '0.00', '962.50', '87.50', '875.00', 'VALIDE', 1),
(66, NULL, '2026-04-29 02:54:19.000000', '2026-04-29 02:54:19.000000', '0.00', '0.00', '0.00', '0.00', 'ACTIF', 1);

-- --------------------------------------------------------

--
-- Table structure for table `planning`
--

CREATE TABLE `planning` (
  `id_planning` int(11) NOT NULL,
  `id_voyageur` int(11) NOT NULL,
  `id_activite` int(11) NOT NULL,
  `date_activite` date DEFAULT NULL,
  `heure_debut` time DEFAULT NULL,
  `heure_fin` time DEFAULT NULL,
  `nombre_places` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `preferences`
--

CREATE TABLE `preferences` (
  `id` int(11) NOT NULL,
  `clientId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `produit_panier`
--

CREATE TABLE `produit_panier` (
  `id` int(11) NOT NULL,
  `date_ajout` datetime(6) DEFAULT NULL,
  `panier_id` int(11) NOT NULL,
  `prix_total_ligne` decimal(10,2) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `type_produit` enum('VOYAGE','TRANSPORT','HEBERGEMENT') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `produit_panier`
--

INSERT INTO `produit_panier` (`id`, `date_ajout`, `panier_id`, `prix_total_ligne`, `prix_unitaire`, `produit_id`, `quantite`, `type_produit`) VALUES
(5, '2026-02-08 23:56:28.000000', 1, '3596.00', '899.00', 1, 4, 'VOYAGE'),
(6, '2026-02-09 00:12:49.000000', 2, '1200.00', '1200.00', 2, 1, 'VOYAGE'),
(7, '2026-02-09 00:35:10.000000', 3, '3200.00', '3200.00', 5, 1, 'VOYAGE'),
(8, '2026-02-09 03:03:10.000000', 4, '1500.00', '750.00', 3, 2, 'VOYAGE'),
(9, '2026-02-09 03:03:29.000000', 4, '1200.00', '1200.00', 2, 1, 'VOYAGE'),
(10, '2026-02-09 03:03:31.000000', 4, '899.00', '899.00', 1, 1, 'VOYAGE'),
(11, '2026-02-09 11:12:33.000000', 4, '750.00', '750.00', 3, 1, 'VOYAGE'),
(12, '2026-02-09 12:22:20.000000', 4, '899.00', '899.00', 1, 1, 'VOYAGE'),
(13, '2026-02-10 17:06:50.000000', 4, '899.00', '899.00', 1, 1, 'VOYAGE'),
(14, '2026-02-10 17:33:32.000000', 5, '899.00', '899.00', 1, 1, 'VOYAGE'),
(15, '2026-02-10 19:29:34.000000', 5, '1200.00', '1200.00', 2, 1, 'VOYAGE'),
(16, '2026-02-11 00:29:12.000000', 6, '1200.00', '1200.00', 2, 1, 'VOYAGE'),
(17, '2026-02-11 14:09:42.000000', 7, '2500.00', '2500.00', 4, 1, 'VOYAGE'),
(18, '2026-02-11 14:20:00.000000', 8, '750.00', '750.00', 3, 1, 'VOYAGE'),
(19, '2026-02-11 15:32:37.000000', 9, '750.00', '750.00', 3, 1, 'VOYAGE'),
(20, '2026-02-11 17:12:42.000000', 10, '3200.00', '3200.00', 5, 1, 'VOYAGE'),
(21, '2026-02-11 18:41:06.000000', 11, '1200.00', '1200.00', 2, 1, 'VOYAGE'),
(22, '2026-02-11 18:44:48.000000', 12, '899.00', '899.00', 1, 1, 'VOYAGE'),
(23, '2026-02-11 18:44:49.000000', 12, '1200.00', '1200.00', 2, 1, 'VOYAGE'),
(24, '2026-02-11 18:48:52.000000', 13, '2250.00', '750.00', 3, 3, 'VOYAGE'),
(25, '2026-02-11 18:53:18.000000', 14, '9600.00', '3200.00', 5, 3, 'VOYAGE'),
(26, '2026-02-11 18:55:25.000000', 15, '2500.00', '2500.00', 4, 1, 'VOYAGE'),
(27, '2026-02-11 18:58:30.000000', 16, '4495.00', '899.00', 1, 5, 'VOYAGE'),
(28, '2026-02-11 19:01:58.000000', 17, '6000.00', '1200.00', 2, 5, 'VOYAGE'),
(29, '2026-02-11 19:03:44.000000', 18, '10500.00', '750.00', 3, 14, 'VOYAGE'),
(30, '2026-02-11 19:20:14.000000', 18, '750.00', '750.00', 3, 1, 'VOYAGE'),
(31, '2026-02-11 19:20:15.000000', 18, '1200.00', '1200.00', 2, 1, 'VOYAGE'),
(32, '2026-02-11 19:20:17.000000', 18, '899.00', '899.00', 1, 1, 'VOYAGE'),
(35, '2026-02-11 20:05:28.000000', 19, '1200.00', '1200.00', 2, 1, 'VOYAGE'),
(36, '2026-02-11 20:05:29.000000', 19, '6000.00', '750.00', 3, 8, 'VOYAGE'),
(37, '2026-02-11 20:35:40.000000', 20, '1200.00', '1200.00', 2, 1, 'VOYAGE'),
(38, '2026-02-14 20:09:16.000000', 21, '1200.00', '1200.00', 2, 1, 'VOYAGE'),
(39, '2026-02-14 20:09:31.000000', 21, '3200.00', '3200.00', 5, 1, 'VOYAGE'),
(40, '2026-02-14 20:16:50.000000', 22, '1200.00', '1200.00', 2, 1, 'VOYAGE'),
(42, '2026-02-14 23:58:09.000000', 23, '5000.00', '2500.00', 4, 2, 'VOYAGE'),
(46, '2026-02-15 00:26:33.000000', 24, '1200.00', '1200.00', 2, 1, 'VOYAGE'),
(47, '2026-02-16 09:57:34.000000', 24, '750.00', '750.00', 3, 1, 'VOYAGE'),
(48, '2026-02-16 10:07:15.000000', 29, '899.00', '899.00', 1, 1, 'VOYAGE'),
(50, '2026-02-16 11:20:57.000000', 29, '1200.00', '1200.00', 2, 1, 'VOYAGE'),
(51, '2026-02-16 11:25:41.000000', 30, '1200.00', '1200.00', 2, 1, 'VOYAGE'),
(52, '2026-02-21 03:52:19.000000', 31, '750.00', '750.00', 3, 1, 'VOYAGE'),
(55, '2026-02-28 07:08:58.000000', 32, '3200.00', '3200.00', 5, 1, 'VOYAGE'),
(56, '2026-02-28 07:42:01.000000', 33, '750.00', '750.00', 3, 1, 'VOYAGE'),
(57, '2026-02-28 08:04:01.000000', 33, '899.00', '899.00', 1, 1, 'VOYAGE'),
(58, '2026-02-28 09:42:40.000000', 34, '750.00', '750.00', 3, 1, 'VOYAGE'),
(60, '2026-02-28 09:51:22.000000', 35, '899.00', '899.00', 1, 1, 'VOYAGE'),
(61, '2026-02-28 09:51:24.000000', 35, '2500.00', '2500.00', 4, 1, 'VOYAGE'),
(65, '2026-02-28 10:04:44.000000', 36, '1200.00', '1200.00', 2, 1, 'VOYAGE'),
(66, '2026-02-28 10:07:57.000000', 37, '1200.00', '1200.00', 2, 1, 'VOYAGE'),
(71, '2026-03-02 06:47:11.000000', 38, '9561.60', '3187.20', 1, 3, 'HEBERGEMENT'),
(72, '2026-03-02 07:09:26.000000', 39, '350.00', '350.00', 2, 1, 'TRANSPORT'),
(73, '2026-03-02 07:10:07.000000', 39, '2803.20', '2803.20', 1, 1, 'HEBERGEMENT'),
(74, '2026-03-02 07:53:37.000000', 39, '3200.00', '3200.00', 5, 1, 'VOYAGE'),
(75, '2026-03-02 10:38:15.000000', 40, '10800.00', '1200.00', 2, 9, 'VOYAGE'),
(76, '2026-03-02 11:54:44.000000', 40, '2656.00', '2656.00', 1, 1, 'HEBERGEMENT'),
(77, '2026-04-07 01:40:21.000000', 40, '1798.00', '899.00', 1, 2, 'VOYAGE'),
(78, '2026-04-07 00:57:28.000000', 41, '899.00', '899.00', 1, 1, 'VOYAGE'),
(80, '2026-04-07 02:44:12.000000', 42, '2500.00', '2500.00', 4, 1, 'VOYAGE'),
(81, '2026-04-07 02:51:32.000000', 43, '4500.00', '750.00', 3, 6, 'VOYAGE'),
(82, '2026-04-07 03:29:58.000000', 44, '2400.00', '1200.00', 2, 2, 'VOYAGE'),
(83, '2026-04-07 14:55:20.000000', 45, '2400.00', '1200.00', 2, 2, 'VOYAGE'),
(84, '2026-04-07 15:31:48.000000', 46, '1200.00', '1200.00', 2, 1, 'VOYAGE'),
(85, '2026-04-07 16:19:51.000000', 47, '3200.00', '3200.00', 5, 1, 'VOYAGE'),
(86, '2026-04-07 16:38:29.000000', 47, '1200.00', '1200.00', 2, 1, 'VOYAGE'),
(87, '2026-04-07 16:42:30.000000', 48, '2500.00', '2500.00', 4, 1, 'VOYAGE'),
(89, '2026-04-07 17:11:00.000000', 49, '5000.00', '2500.00', 4, 2, 'VOYAGE'),
(90, '2026-04-11 17:50:11.000000', 50, '2500.00', '2500.00', 4, 1, 'VOYAGE'),
(91, '2026-04-11 19:26:25.000000', 51, '3200.00', '3200.00', 5, 1, 'VOYAGE'),
(92, '2026-04-11 19:27:17.000000', 52, '3200.00', '3200.00', 5, 1, 'VOYAGE'),
(93, '2026-04-11 19:44:36.000000', 53, '3200.00', '3200.00', 5, 1, 'VOYAGE'),
(94, '2026-04-11 19:53:11.000000', 54, '2500.00', '2500.00', 4, 1, 'VOYAGE'),
(95, '2026-04-11 20:21:30.000000', 55, '5000.00', '2500.00', 4, 2, 'VOYAGE'),
(96, '2026-04-18 15:00:04.000000', 56, '2500.00', '2500.00', 4, 1, 'VOYAGE'),
(97, '2026-04-18 15:32:54.000000', 57, '899.00', '899.00', 1, 1, 'VOYAGE'),
(98, '2026-04-18 16:36:12.000000', 58, '2500.00', '2500.00', 4, 1, 'VOYAGE'),
(99, '2026-04-18 17:42:46.000000', 59, '750.00', '750.00', 3, 1, 'VOYAGE'),
(101, '2026-04-18 17:51:26.000000', 60, '750.00', '750.00', 3, 1, 'VOYAGE'),
(102, '2026-04-20 09:44:13.000000', 61, '2500.00', '2500.00', 4, 1, 'VOYAGE'),
(104, '2026-04-21 00:02:39.000000', 62, '750.00', '750.00', 3, 1, 'VOYAGE'),
(109, '2026-04-29 00:17:25.000000', 64, '200.00', '200.00', 1, 1, 'HEBERGEMENT'),
(110, '2026-04-29 02:12:31.000000', 63, '200.00', '200.00', 1, 1, 'HEBERGEMENT'),
(111, '2026-04-29 02:12:46.000000', 63, '700.00', '700.00', 7, 1, 'TRANSPORT'),
(112, '2026-04-29 02:53:08.000000', 65, '875.00', '875.00', 2, 1, 'TRANSPORT');

-- --------------------------------------------------------

--
-- Table structure for table `promo_code`
--

CREATE TABLE `promo_code` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `created_at` datetime(6) DEFAULT NULL,
  `discount_percent` int(11) NOT NULL,
  `panier_id` int(11) DEFAULT NULL,
  `is_used` bit(1) NOT NULL,
  `used_at` datetime(6) DEFAULT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promo_code`
--

INSERT INTO `promo_code` (`id`, `code`, `created_at`, `discount_percent`, `panier_id`, `is_used`, `used_at`, `user_id`) VALUES
(1, 'EXPL-PRRIC', '2026-02-28 09:42:17.000000', 5, 34, b'1', '2026-02-28 09:42:46.000000', 1),
(2, 'EXPL-39OVB', '2026-02-28 09:50:50.000000', 15, 35, b'1', '2026-02-28 09:51:12.000000', 1),
(3, 'EXPL-7RF18', '2026-02-28 10:01:23.000000', 5, 36, b'1', '2026-02-28 10:01:54.000000', 1),
(5, 'EXPL-1OFZG', '2026-03-02 00:52:53.000000', 10, 40, b'1', '2026-03-02 10:38:24.000000', 1),
(6, 'EXPL-FRGPQ', '2026-03-02 10:37:47.000000', 5, 49, b'1', '2026-04-07 17:11:59.000000', 1),
(7, 'SCRATCH-C9A91D9E15', '2026-04-11 19:26:58.000000', 30, 52, b'1', '2026-04-11 19:27:28.000000', 1),
(8, 'SCRATCH-DC219D9FC4', '2026-04-11 19:28:04.000000', 5, NULL, b'0', NULL, 1),
(9, 'SCRATCH-6DD811966F', '2026-04-11 19:51:35.000000', 15, NULL, b'0', NULL, 1),
(10, 'SCRATCH-68EE610E5B', '2026-04-11 20:10:29.000000', 50, NULL, b'0', NULL, 1),
(11, 'SCRATCH-3C351231E6', '2026-04-15 00:47:02.000000', 20, NULL, b'0', NULL, 1),
(12, 'SCRATCH-79E1788AD1', '2026-04-18 15:23:55.000000', 20, NULL, b'0', NULL, 1),
(13, 'SCRATCH-ABA7AF8AC7', '2026-04-18 16:06:21.000000', 30, NULL, b'0', NULL, 1),
(14, 'SCRATCH-853B3CA5D9', '2026-04-18 17:45:35.000000', 20, NULL, b'0', NULL, 1),
(15, 'SCRATCH-7460F623B7', '2026-04-18 18:49:35.000000', 30, NULL, b'0', NULL, 1),
(16, 'SCRATCH-5ED1C34E1B', '2026-04-21 00:06:31.000000', 15, NULL, b'0', NULL, 1),
(17, 'SCRATCH-CAD32E8BD0', '2026-04-29 02:47:54.000000', 50, NULL, b'0', NULL, 1),
(18, 'SCRATCH-4FE2881636', '2026-04-29 02:54:19.000000', 30, NULL, b'0', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `reclamation`
--

CREATE TABLE `reclamation` (
  `id` int(11) NOT NULL,
  `clientId` int(11) NOT NULL,
  `dateCloture` datetime(6) DEFAULT NULL,
  `dateCreation` datetime(6) NOT NULL,
  `description` varchar(2000) DEFAULT NULL,
  `priorite` enum('BASSE','NORMALE','HAUTE','URGENTE','CRITIQUE') NOT NULL,
  `statut` enum('OUVERTE','EN_COURS','RESOLUE','FERMEE','REJETEE','EN_ATTENTE') NOT NULL,
  `type` enum('TECHNIQUE','COMMERCIAL','SERVICE_CLIENT','FACTURATION','LIVRAISON','QUALITE_PRODUIT','REMBOURSEMENT','AUTRE') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservation`
--

CREATE TABLE `reservation` (
  `id_reservation` int(11) NOT NULL,
  `id_hebergement` int(11) NOT NULL,
  `nom_client` varchar(255) NOT NULL,
  `email_client` varchar(255) DEFAULT NULL,
  `date_checkin` date NOT NULL,
  `date_checkout` date NOT NULL,
  `status` varchar(50) DEFAULT 'CONFIRMED',
  `prix_total` double NOT NULL DEFAULT 0,
  `nb_guests` int(11) DEFAULT 0,
  `nb_rooms` int(11) DEFAULT 0,
  `occupancy` varchar(100) DEFAULT NULL,
  `room_type` varchar(100) DEFAULT NULL,
  `statut` varchar(50) NOT NULL DEFAULT 'CONFIRMED',
  `date_reservation` datetime NOT NULL DEFAULT current_timestamp(),
  `guests_count` int(11) NOT NULL DEFAULT 1,
  `rooms_count` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservation`
--

INSERT INTO `reservation` (`id_reservation`, `id_hebergement`, `nom_client`, `email_client`, `date_checkin`, `date_checkout`, `status`, `prix_total`, `nb_guests`, `nb_rooms`, `occupancy`, `room_type`, `statut`, `date_reservation`, `guests_count`, `rooms_count`) VALUES
(1, 1, 'adem', 'adembenali2004@gmail.com', '2026-03-12', '2026-03-26', 'CONFIRMED', 2803.2, 1, 1, 'DOUBLE', 'Deluxe', 'CONFIRMED', '2026-04-28 22:15:24', 1, 1),
(2, 1, 'aaaaaa', 'aaaaa', '2026-03-05', '2026-03-20', 'CONFIRMED', 2995.2, 1, 1, 'DOUBLE', 'Deluxe', 'CONFIRMED', '2026-04-28 22:15:24', 1, 1),
(3, 1, 'aaaaaa', 'aaaaaaa', '2026-03-04', '2026-03-19', 'CONFIRMED', 2995.2, 1, 1, 'DOUBLE', 'Deluxe', 'CONFIRMED', '2026-04-28 22:15:24', 1, 1),
(4, 1, 'aaaaa', 'aaaa', '2026-03-03', '2026-03-13', 'CONFIRMED', 2224.8, 1, 1, 'DOUBLE', 'Suite', 'CONFIRMED', '2026-04-28 22:15:24', 1, 1),
(5, 1, 'adem', 'adem', '2026-03-04', '2026-03-12', 'CONFIRMED', 1494, 1, 1, 'DOUBLE', 'Standard', 'CONFIRMED', '2026-04-28 22:15:24', 1, 1),
(6, 1, 'aaaaa', 'aaa', '2026-03-18', '2026-03-28', 'CONFIRMED', 2006.4, 1, 1, 'DOUBLE', 'Deluxe', 'CONFIRMED', '2026-04-28 22:15:24', 1, 1),
(7, 1, 'aaaaaa', 'aaaa', '2026-03-10', '2026-04-17', 'CONFIRMED', 8532, 1, 1, 'DOUBLE', 'Suite', 'CONFIRMED', '2026-04-28 22:15:24', 1, 1),
(8, 1, 'eeeeee', 'eeeee', '2026-03-11', '2026-03-27', 'CONFIRMED', 3187.2, 1, 1, 'DOUBLE', 'Deluxe', 'CONFIRMED', '2026-04-28 22:15:24', 1, 1),
(9, 1, 'hamza', 'aaaa', '2026-03-03', '2026-03-17', 'CONFIRMED', 2803.2, 1, 1, 'DOUBLE', 'Deluxe', 'CONFIRMED', '2026-04-28 22:15:24', 1, 1),
(10, 1, 'aaaaaaa', 'aaaaaaa', '2026-03-11', '2026-03-27', 'CONFIRMED', 2656, 1, 1, 'DOUBLE', 'Standard', 'CONFIRMED', '2026-04-28 22:15:24', 1, 1),
(11, 1, 'adem', 'adem.benali@esprit.tn', '2026-04-28', '2026-04-30', 'CONFIRMED', 400, 0, 0, 'DOUBLE', 'Standard', 'CONFIRMED', '2026-04-28 23:24:45', 0, 0),
(12, 1, 'adem', 'adem.benali@esprit.tn', '2026-04-29', '2026-04-30', 'CONFIRMED', 200, 0, 0, 'DOUBLE', 'Standard', 'CONFIRMED', '2026-04-29 02:09:44', 1, 1),
(13, 1, 'adem', 'adem.benali@esprit.tn', '2026-04-29', '2026-04-30', 'CONFIRMED', 200, 0, 0, 'DOUBLE', 'Standard', 'CONFIRMED', '2026-04-29 02:11:10', 1, 1),
(14, 1, 'adem', 'adem.benali@esprit.tn', '2026-04-29', '2026-04-30', 'CONFIRMED', 200, 0, 0, 'DOUBLE', 'Standard', 'CONFIRMED', '2026-04-29 02:11:15', 1, 1),
(15, 1, 'adem', 'adem.benali@esprit.tn', '2026-04-29', '2026-04-30', 'CONFIRMED', 200, 0, 0, 'DOUBLE', 'Standard', 'CONFIRMED', '2026-04-29 02:12:31', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `reservation_guest`
--

CREATE TABLE `reservation_guest` (
  `id_guest` int(11) NOT NULL,
  `id_reservation` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `birth_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservation_guest`
--

INSERT INTO `reservation_guest` (`id_guest`, `id_reservation`, `full_name`, `birth_date`) VALUES
(1, 1, 'aaaaaaaa', '2026-03-12'),
(2, 2, 'aaaaaa', '2026-03-11'),
(3, 3, 'aaaaaa', '2026-03-11'),
(4, 4, 'aaaa', '2026-03-19'),
(5, 5, 'ad', '2026-03-12'),
(6, 6, 'aaaa', '2026-03-20'),
(7, 7, 'daz', '2026-03-05'),
(8, 8, 'aaa', '2026-03-11'),
(9, 9, 'azd', '2026-03-19'),
(10, 10, 'aaaaa', '2026-03-20');

-- --------------------------------------------------------

--
-- Table structure for table `saved_cards`
--

CREATE TABLE `saved_cards` (
  `id` int(11) NOT NULL,
  `billing_address` varchar(255) DEFAULT NULL,
  `billing_city` varchar(100) DEFAULT NULL,
  `billing_country` varchar(100) DEFAULT NULL,
  `billing_postal_code` varchar(20) DEFAULT NULL,
  `card_brand` varchar(20) DEFAULT NULL,
  `cardholder_name` varchar(100) NOT NULL,
  `created_at` datetime(6) NOT NULL,
  `expiry_month` int(11) NOT NULL,
  `expiry_year` int(11) NOT NULL,
  `is_default` bit(1) NOT NULL,
  `last_four_digits` varchar(4) NOT NULL,
  `stripe_payment_method_id` varchar(255) DEFAULT NULL,
  `updated_at` datetime(6) DEFAULT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `saved_cards`
--

INSERT INTO `saved_cards` (`id`, `billing_address`, `billing_city`, `billing_country`, `billing_postal_code`, `card_brand`, `cardholder_name`, `created_at`, `expiry_month`, `expiry_year`, `is_default`, `last_four_digits`, `stripe_payment_method_id`, `updated_at`, `user_id`) VALUES
(1, '123 Main Street', 'mahdia', 'Tunisia', '5180', 'amex', 'adem bena ali', '2026-02-21 03:52:12.000000', 2, 2027, b'1', '5447', NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `schema_version`
--

CREATE TABLE `schema_version` (
  `id` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `executed_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schema_version`
--

INSERT INTO `schema_version` (`id`, `version`, `description`, `executed_at`) VALUES
(1, 0, 'Initial schema', '2026-03-01 22:21:15'),
(2, 1, 'Migration to version 1', '2026-03-01 22:21:15');

-- --------------------------------------------------------

--
-- Table structure for table `transport`
--

CREATE TABLE `transport` (
  `id` int(11) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `categorie` varchar(20) DEFAULT NULL,
  `origine` varchar(100) DEFAULT NULL,
  `destination` varchar(100) DEFAULT NULL,
  `date_depart` date DEFAULT NULL,
  `date_arrivee` date DEFAULT NULL,
  `heure_depart` time DEFAULT NULL,
  `heure_arrivee` time DEFAULT NULL,
  `prix` decimal(15,2) DEFAULT NULL,
  `places_disponibles` int(11) DEFAULT NULL,
  `compagnie` varchar(100) DEFAULT NULL,
  `numero_vol` varchar(50) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `etat_trafic` varchar(50) DEFAULT 'FLUIDE',
  `image_trafic_url` varchar(500) DEFAULT NULL,
  `derniere_analyse` datetime DEFAULT NULL,
  `score_confiance` decimal(5,2) DEFAULT NULL,
  `prix_original` decimal(15,2) DEFAULT NULL,
  `derniere_maj_prix` datetime DEFAULT NULL,
  `distance_km` double DEFAULT 0,
  `emissions_kg_co2` double DEFAULT 0,
  `categorie_ecologique` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transport`
--

INSERT INTO `transport` (`id`, `type`, `categorie`, `origine`, `destination`, `date_depart`, `date_arrivee`, `heure_depart`, `heure_arrivee`, `prix`, `places_disponibles`, `compagnie`, `numero_vol`, `image_url`, `description`, `etat_trafic`, `image_trafic_url`, `derniere_analyse`, `score_confiance`, `prix_original`, `derniere_maj_prix`, `distance_km`, `emissions_kg_co2`, `categorie_ecologique`, `created_at`) VALUES
(1, 'BUS', 'EN_COURS', 'Centre-ville', 'Les berges du lac', '2026-02-10', NULL, '08:00:00', '09:00:00', '9.00', 45, 'TunisTransport', NULL, NULL, NULL, 'FLUIDE', NULL, NULL, NULL, '10.00', '2026-03-03 08:46:39', 500, 34, 'BON', '2026-04-28 22:08:53'),
(2, 'AVION', 'PRE_VOYAGE', 'Tunis', 'Paris', '2026-02-15', '2026-02-15', '14:30:00', '17:45:00', '875.00', 172, 'Air France', 'AF1234', NULL, NULL, 'FLUIDE', NULL, NULL, NULL, '350.00', '2026-04-29 20:20:11', 1482.3240429784696, 277.1945960369738, 'MAUVAIS', '2026-04-28 22:08:53'),
(3, 'BATEAU', 'PRE_VOYAGE', 'Tunis', 'Marseille', '2026-02-20', '2026-02-21', '22:00:00', '08:00:00', '264.00', 200, 'CTN', 'CTN456', NULL, NULL, 'FLUIDE', NULL, NULL, NULL, '150.00', '2026-04-21 01:40:54', 829.4085265862739, 207.35213164656847, 'MAUVAIS', '2026-04-28 22:08:53'),
(4, 'TRAIN', 'PRE_VOYAGE', 'Milan', 'Paris', '2026-02-25', '2026-02-25', '10:00:00', '16:00:00', '278.00', 94, 'Trenitalia', 'MIL205', NULL, NULL, 'FLUIDE', NULL, NULL, NULL, '200.00', '2026-03-03 08:46:40', 500, 7, 'EXCELLENT', '2026-04-28 22:08:53'),
(5, 'TAXI', 'EN_COURS', 'A??roport Tunis', 'Centre-ville', '2026-02-12', NULL, '15:00:00', '16:00:00', '22.00', 4, 'Taxi Tunis', NULL, NULL, NULL, 'FLUIDE', NULL, NULL, NULL, '30.00', '2026-03-03 08:46:39', 500, 96.5, 'MOYEN', '2026-04-28 22:08:53'),
(6, 'VOITURE', 'EN_COURS', 'Sousse', 'Hammamet', '2026-02-18', NULL, '09:00:00', '10:30:00', '25.00', 3, 'Location Auto', NULL, NULL, NULL, 'FLUIDE', NULL, NULL, NULL, NULL, NULL, 70, 12, 'BON', '2026-04-28 22:08:53'),
(7, 'AVION', 'PRE_VOYAGE', 'Tunis', 'Rome', '2026-03-01', '2026-03-01', '11:00:00', '12:30:00', '700.00', 144, 'Tunisair', 'TU789', NULL, NULL, 'FLUIDE', NULL, NULL, NULL, '280.00', '2026-04-29 20:20:11', 600.5523606416775, 154.9425090455528, 'MAUVAIS', '2026-04-28 22:08:53'),
(8, 'BUS', 'EN_COURS', 'Hammamet', 'Sousse', '2026-02-19', NULL, '14:00:00', '15:30:00', '6.00', 50, 'SNTRI', NULL, NULL, NULL, 'FLUIDE', NULL, NULL, NULL, '8.00', '2026-03-03 08:46:39', 63.896135990913436, 4.344937247382114, 'EXCELLENT', '2026-04-28 22:08:53'),
(9, 'VOITURE', 'EN_COURS', 'Sfax', 'Sousse', '2026-02-17', NULL, '11:30:00', NULL, '35.00', 4, 'Rent a car', NULL, NULL, NULL, 'FLUIDE', NULL, NULL, NULL, '50.00', '2026-03-03 08:46:39', 121.16529350043277, 23.384901645583522, 'BON', '2026-04-28 22:08:53'),
(10, 'BUS', 'EN_COURS', 'Tunis', 'Bardo', '2026-03-05', NULL, '07:00:00', '07:45:00', '2.50', 50, 'SNTRI', NULL, NULL, 'Trajet court Tunis-Bardo', 'FLUIDE', NULL, NULL, NULL, '2.50', '2026-03-02 00:09:46', 8.5, 0.68, 'EXCELLENT', '2026-04-28 22:08:53'),
(11, 'BUS', 'EN_COURS', 'Bardo', 'Tunis', '2026-03-05', NULL, '08:30:00', '09:15:00', '2.50', 45, 'SNTRI', NULL, NULL, 'Retour Bardo vers Tunis', 'FLUIDE', NULL, NULL, NULL, '2.50', '2026-03-02 00:09:46', 8.5, 0.68, 'EXCELLENT', '2026-04-28 22:08:53'),
(12, 'BUS', 'EN_COURS', 'Tunis', 'Ariana', '2026-03-05', NULL, '06:30:00', '07:15:00', '3.00', 48, 'SNTRI', NULL, NULL, 'Tunis ?? Ariana - court trajet', 'FLUIDE', NULL, NULL, NULL, '3.00', '2026-03-02 00:09:46', 7.2, 0.58, 'EXCELLENT', '2026-04-28 22:08:53'),
(13, 'BUS', 'EN_COURS', 'Ariana', 'Tunis', '2026-03-05', NULL, '17:00:00', '17:45:00', '3.00', 42, 'SNTRI', NULL, NULL, 'Ariana vers Tunis', 'FLUIDE', NULL, NULL, NULL, '3.00', '2026-03-02 00:09:46', 7.2, 0.58, 'EXCELLENT', '2026-04-28 22:08:53'),
(14, 'BUS', 'EN_COURS', 'Tunis', 'La Marsa', '2026-03-05', NULL, '08:00:00', '09:00:00', '4.00', 55, 'TunisTransport', NULL, NULL, 'Tunis ?? La Marsa', 'FLUIDE', NULL, NULL, NULL, '4.00', '2026-03-02 00:09:46', 10, 0.8, 'BON', '2026-04-28 22:08:53'),
(15, 'BUS', 'EN_COURS', 'La Marsa', 'Carthage', '2026-03-05', NULL, '09:30:00', '10:15:00', '2.00', 50, 'TunisTransport', NULL, NULL, 'La Marsa ?? Carthage', 'FLUIDE', NULL, NULL, NULL, '2.00', '2026-03-02 00:09:46', 3.5, 0.28, 'EXCELLENT', '2026-04-28 22:08:53'),
(16, 'BUS', 'EN_COURS', 'Sidi Bou Said', 'La Marsa', '2026-03-05', NULL, '10:00:00', '10:25:00', '2.00', 50, 'TunisTransport', NULL, NULL, 'Sidi Bou Said ?? La Marsa', 'FLUIDE', NULL, NULL, NULL, '2.00', '2026-03-02 00:09:46', 4, 0.32, 'EXCELLENT', '2026-04-28 22:08:53'),
(17, 'TAXI', 'EN_COURS', 'Tunis', 'Bardo', '2026-03-05', NULL, '10:00:00', '10:30:00', '8.50', 4, 'Taxi Blanc', NULL, NULL, 'Taxi rapide Tunis-Bardo', 'FLUIDE', NULL, NULL, NULL, '10.00', '2026-03-02 00:09:46', 8.5, 1.53, 'BON', '2026-04-28 22:08:53'),
(18, 'TAXI', 'EN_COURS', 'Bardo', 'Ariana', '2026-03-05', NULL, '14:00:00', '14:25:00', '6.50', 4, 'Taxi Blanc', NULL, NULL, 'Bardo ?? Ariana', 'FLUIDE', NULL, NULL, NULL, '8.00', '2026-03-02 00:09:46', 6, 1.08, 'BON', '2026-04-28 22:08:53'),
(19, 'TAXI', 'EN_COURS', 'Tunis', 'Manouba', '2026-03-05', NULL, '11:00:00', '11:35:00', '7.00', 4, 'Taxi Blanc', NULL, NULL, 'Tunis ?? Manouba', 'FLUIDE', NULL, NULL, NULL, '8.50', '2026-03-02 00:09:46', 8, 1.44, 'BON', '2026-04-28 22:08:53'),
(20, 'TRAIN', 'EN_COURS', 'Tunis', 'Sousse', '2026-03-06', NULL, '06:00:00', '08:00:00', '15.00', 120, 'SNCFT', 'TUN001', NULL, 'Train r??gional Tunis-Sousse', 'FLUIDE', NULL, NULL, NULL, '15.00', '2026-03-02 00:09:46', 140, 5.6, 'EXCELLENT', '2026-04-28 22:08:53'),
(21, 'TRAIN', 'EN_COURS', 'Sousse', 'Hammamet', '2026-03-06', NULL, '09:30:00', '11:00:00', '12.00', 100, 'SNCFT', 'SOU002', NULL, 'Sousse ?? Hammamet', 'FLUIDE', NULL, NULL, NULL, '12.00', '2026-03-02 00:09:46', 50, 2, 'EXCELLENT', '2026-04-28 22:08:53'),
(22, 'TRAIN', 'EN_COURS', 'Tunis', 'Sfax', '2026-03-06', NULL, '07:30:00', '10:45:00', '22.00', 150, 'SNCFT', 'TUN003', NULL, 'Train express Tunis-Sfax', 'FLUIDE', NULL, NULL, NULL, '22.00', '2026-03-02 00:09:46', 280, 11.2, 'EXCELLENT', '2026-04-28 22:08:53'),
(23, 'VOITURE', 'EN_COURS', 'Tunis', 'Ezzahra', '2026-03-05', NULL, '13:00:00', '13:35:00', '9.50', 4, 'Location Auto Tunis', NULL, NULL, 'Tunis ?? Ezzahra', 'FLUIDE', NULL, NULL, NULL, '10.00', '2026-03-02 00:09:46', 6.5, 1.17, 'BON', '2026-04-28 22:08:53'),
(24, 'VOITURE', 'EN_COURS', 'Hammamet', 'Sousse', '2026-03-05', NULL, '09:00:00', '10:15:00', '18.00', 4, 'Location Auto', NULL, NULL, 'Hammamet ?? Sousse', 'FLUIDE', NULL, NULL, NULL, '20.00', '2026-03-02 00:09:46', 65, 11.7, 'BON', '2026-04-28 22:08:53');

-- --------------------------------------------------------

--
-- Table structure for table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `id` int(11) NOT NULL,
  `bio` varchar(1000) DEFAULT NULL,
  `dateCreation` datetime(6) NOT NULL,
  `email` varchar(255) NOT NULL,
  `estVerifie` bit(1) NOT NULL,
  `motDePasse` varchar(255) NOT NULL,
  `nationalite` varchar(255) DEFAULT NULL,
  `nom` varchar(255) NOT NULL,
  `photoDeProfil` varchar(255) DEFAULT NULL,
  `prenom` varchar(255) NOT NULL,
  `role` enum('ADMIN','AGENT','VOYAGEUR') NOT NULL,
  `statut` enum('ACTIF','INACTIF','SUSPENDU','EN_ATTENTE','BANNED') NOT NULL,
  `telephone` int(11) NOT NULL,
  `dateNaissance` datetime(6) DEFAULT NULL,
  `pays` varchar(255) DEFAULT NULL,
  `ville` varchar(255) DEFAULT NULL,
  `codePostale` varchar(255) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `utilisateur`
--

INSERT INTO `utilisateur` (`id`, `bio`, `dateCreation`, `email`, `estVerifie`, `motDePasse`, `nationalite`, `nom`, `photoDeProfil`, `prenom`, `role`, `statut`, `telephone`, `dateNaissance`, `pays`, `ville`, `codePostale`, `adresse`) VALUES
(1, NULL, '2026-02-08 22:28:27.000000', 'adembenali2004@gmail.com', b'0', '$2a$10$nuyA/b/pLCIb.Zmb7eaDuu9W0kijQxoG7HvqeSjPb8k9dnZKKZ15K', NULL, 'adem', NULL, 'ben ali', 'VOYAGEUR', 'EN_ATTENTE', 27405006, NULL, NULL, NULL, NULL, NULL),
(2, NULL, '2026-02-14 20:16:34.000000', 'brikihamza10@gmail.com', b'0', '$2a$10$neFbnu.12olfE8pFJ2jliOzHh4QSKjtAdYTmm8ZtDJzSNTcAb4bJK', NULL, 'hamza', NULL, 'briki', 'VOYAGEUR', 'EN_ATTENTE', 94405998, NULL, NULL, NULL, NULL, NULL),
(3, NULL, '2026-03-02 08:16:32.869053', 'agent@explora.com', b'1', '$2a$10$QixKrZPbqN.RHUCXy1UrCuK.bkBsTzLX/n8AOXjBiNmR1BAwHCSIK', 'Tunisienne', 'Agent', NULL, 'Test', 'AGENT', 'ACTIF', 12345678, NULL, 'Tunisie', 'Tunis', '1000', 'Rue Test'),
(6, 'System Administrator', '2026-03-02 09:17:58.000000', 'saadaouilouay16@gmail.com', b'1', '$2a$10$pMjllZAFI9cmTS6BlgFM4.NlSPdZlsTC.Si/yPE4A273ECng2IS0O', 'Tunisian', 'Admin', NULL, 'System', 'ADMIN', 'ACTIF', 12345678, NULL, 'Tunisia', 'Tunis', '1000', 'Admin Address');

-- --------------------------------------------------------

--
-- Table structure for table `verification_codes`
--

CREATE TABLE `verification_codes` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `code` varchar(10) NOT NULL,
  `type` enum('EMAIL','SMS','PASSWORD_RESET') NOT NULL,
  `expiration_time` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_used` bit(1) DEFAULT b'0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `voyage`
--

CREATE TABLE `voyage` (
  `id` int(11) NOT NULL,
  `date_depart` date DEFAULT NULL,
  `date_retour` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `destination` varchar(150) NOT NULL,
  `disponibilite` int(11) NOT NULL,
  `duree_jours` int(11) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `nom` varchar(200) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `voyage`
--

INSERT INTO `voyage` (`id`, `date_depart`, `date_retour`, `description`, `destination`, `disponibilite`, `duree_jours`, `image_url`, `nom`, `prix_unitaire`) VALUES
(1, '2026-03-15', '2026-03-20', 'D??couvrez la ville lumi??re avec ses monuments embl??matiques', 'Paris, France', 1, 5, 'paris.jpg', 'Voyage ?? Paris', '899.00'),
(2, '2026-04-01', '2026-04-08', 'Explorez la capitale italienne et son histoire mill??naire', 'Rome, Italie', 1, 7, 'rome.jpg', 'Tour de Rome', '1200.00'),
(3, '2026-05-10', '2026-05-14', 'Profitez du soleil et de la culture catalane', 'Barcelone, Espagne', 1, 4, 'barcelona.jpg', 'S??jour ?? Barcelone', '750.00'),
(4, '2026-06-01', '2026-06-11', 'Immersion dans la culture japonaise moderne et traditionnelle', 'Tokyo, Japon', 1, 10, 'tokyo.jpg', 'Aventure ?? Tokyo', '2500.00'),
(5, '2026-07-15', '2026-07-23', 'D??couvrez la faune africaine dans son habitat naturel', 'Nairobi, Kenya', 1, 8, 'kenya.jpg', 'Safari au Kenya', '3200.00'),
(7, '2026-04-25', '2026-04-30', NULL, 'Maldive', 1, 5, NULL, 'Voyage de noce', '1000.00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activite`
--
ALTER TABLE `activite`
  ADD PRIMARY KEY (`idActivite`),
  ADD KEY `id_agent` (`id_agent`);

--
-- Indexes for table `activite_voyage`
--
ALTER TABLE `activite_voyage`
  ADD PRIMARY KEY (`idActivite`,`idVoyage`),
  ADD KEY `idVoyage` (`idVoyage`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `agent`
--
ALTER TABLE `agent`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UK_6lwd9b2of6ok6kj6tv8d34i59` (`matricule`),
  ADD UNIQUE KEY `UK_k814ih613n8velwflax8t4jo4` (`emailProfessionnel`);

--
-- Indexes for table `analyse_saisonniere`
--
ALTER TABLE `analyse_saisonniere`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_saison_annee` (`saison`,`annee`);

--
-- Indexes for table `avis`
--
ALTER TABLE `avis`
  ADD PRIMARY KEY (`id_avis`),
  ADD KEY `fk_avis_hebergement` (`id_hebergement`);

--
-- Indexes for table `avis_activite`
--
ALTER TABLE `avis_activite`
  ADD PRIMARY KEY (`idAvis`),
  ADD KEY `idActivite` (`idActivite`),
  ADD KEY `idVoyageur` (`idVoyageur`);

--
-- Indexes for table `axe_activite`
--
ALTER TABLE `axe_activite`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `axe_hebergement`
--
ALTER TABLE `axe_hebergement`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `axe_transport`
--
ALTER TABLE `axe_transport`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `axe_voyage`
--
ALTER TABLE `axe_voyage`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `billet`
--
ALTER TABLE `billet`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transport_id` (`transport_id`);

--
-- Indexes for table `boutique_velo`
--
ALTER TABLE `boutique_velo`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `code_promo_velo`
--
ALTER TABLE `code_promo_velo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD UNIQUE KEY `unique_trajet` (`user_id`,`origine`,`destination`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_code` (`code`);

--
-- Indexes for table `coupon`
--
ALTER TABLE `coupon`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UK_bg4p9ontpj7adq7yr71h93sdn` (`code`);

--
-- Indexes for table `doctrine_migration_versions`
--
ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

--
-- Indexes for table `eco_scores`
--
ALTER TABLE `eco_scores`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hebergement`
--
ALTER TABLE `hebergement`
  ADD PRIMARY KEY (`id_hebergement`);

--
-- Indexes for table `paiement`
--
ALTER TABLE `paiement`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UK_tbcrsl4iq80xvxxu7vjww9v3k` (`reference_transaction`);

--
-- Indexes for table `panier`
--
ALTER TABLE `panier`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `planning`
--
ALTER TABLE `planning`
  ADD PRIMARY KEY (`id_planning`),
  ADD KEY `id_voyageur` (`id_voyageur`),
  ADD KEY `id_activite` (`id_activite`);

--
-- Indexes for table `preferences`
--
ALTER TABLE `preferences`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `produit_panier`
--
ALTER TABLE `produit_panier`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `promo_code`
--
ALTER TABLE `promo_code`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UK_fplc11dewa94eib758xs5mrg9` (`code`);

--
-- Indexes for table `reclamation`
--
ALTER TABLE `reclamation`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reservation`
--
ALTER TABLE `reservation`
  ADD PRIMARY KEY (`id_reservation`),
  ADD KEY `fk_reservation_hebergement` (`id_hebergement`);

--
-- Indexes for table `reservation_guest`
--
ALTER TABLE `reservation_guest`
  ADD PRIMARY KEY (`id_guest`),
  ADD KEY `fk_guest_reservation` (`id_reservation`);

--
-- Indexes for table `saved_cards`
--
ALTER TABLE `saved_cards`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schema_version`
--
ALTER TABLE `schema_version`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_version` (`version`);

--
-- Indexes for table `transport`
--
ALTER TABLE `transport`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UK_rma38wvnqfaf66vvmi57c71lo` (`email`);

--
-- Indexes for table `verification_codes`
--
ALTER TABLE `verification_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vc_email` (`email`),
  ADD KEY `idx_vc_code` (`code`),
  ADD KEY `idx_vc_expiration` (`expiration_time`);

--
-- Indexes for table `voyage`
--
ALTER TABLE `voyage`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activite`
--
ALTER TABLE `activite`
  MODIFY `idActivite` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `analyse_saisonniere`
--
ALTER TABLE `analyse_saisonniere`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `avis`
--
ALTER TABLE `avis`
  MODIFY `id_avis` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `avis_activite`
--
ALTER TABLE `avis_activite`
  MODIFY `idAvis` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `billet`
--
ALTER TABLE `billet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `boutique_velo`
--
ALTER TABLE `boutique_velo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `code_promo_velo`
--
ALTER TABLE `code_promo_velo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupon`
--
ALTER TABLE `coupon`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `hebergement`
--
ALTER TABLE `hebergement`
  MODIFY `id_hebergement` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `paiement`
--
ALTER TABLE `paiement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `panier`
--
ALTER TABLE `panier`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `planning`
--
ALTER TABLE `planning`
  MODIFY `id_planning` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `preferences`
--
ALTER TABLE `preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `produit_panier`
--
ALTER TABLE `produit_panier`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `promo_code`
--
ALTER TABLE `promo_code`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `reclamation`
--
ALTER TABLE `reclamation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservation`
--
ALTER TABLE `reservation`
  MODIFY `id_reservation` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `reservation_guest`
--
ALTER TABLE `reservation_guest`
  MODIFY `id_guest` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `saved_cards`
--
ALTER TABLE `saved_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `schema_version`
--
ALTER TABLE `schema_version`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transport`
--
ALTER TABLE `transport`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `verification_codes`
--
ALTER TABLE `verification_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `voyage`
--
ALTER TABLE `voyage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activite`
--
ALTER TABLE `activite`
  ADD CONSTRAINT `activite_ibfk_1` FOREIGN KEY (`id_agent`) REFERENCES `utilisateur` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `activite_voyage`
--
ALTER TABLE `activite_voyage`
  ADD CONSTRAINT `activite_voyage_ibfk_1` FOREIGN KEY (`idActivite`) REFERENCES `activite` (`idActivite`) ON DELETE CASCADE,
  ADD CONSTRAINT `activite_voyage_ibfk_2` FOREIGN KEY (`idVoyage`) REFERENCES `voyage` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `FKgodqjbbtwk30kf3s0xuxklkr3` FOREIGN KEY (`id`) REFERENCES `utilisateur` (`id`);

--
-- Constraints for table `agent`
--
ALTER TABLE `agent`
  ADD CONSTRAINT `FKoqghuuphfog6kj5cwvmy9movn` FOREIGN KEY (`id`) REFERENCES `utilisateur` (`id`);

--
-- Constraints for table `avis`
--
ALTER TABLE `avis`
  ADD CONSTRAINT `fk_avis_hebergement` FOREIGN KEY (`id_hebergement`) REFERENCES `hebergement` (`id_hebergement`) ON DELETE CASCADE;

--
-- Constraints for table `avis_activite`
--
ALTER TABLE `avis_activite`
  ADD CONSTRAINT `avis_activite_ibfk_1` FOREIGN KEY (`idActivite`) REFERENCES `activite` (`idActivite`) ON DELETE CASCADE,
  ADD CONSTRAINT `avis_activite_ibfk_2` FOREIGN KEY (`idVoyageur`) REFERENCES `utilisateur` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `axe_activite`
--
ALTER TABLE `axe_activite`
  ADD CONSTRAINT `FKe28axik6q44ijkhtng58yl7pl` FOREIGN KEY (`id`) REFERENCES `preferences` (`id`);

--
-- Constraints for table `axe_hebergement`
--
ALTER TABLE `axe_hebergement`
  ADD CONSTRAINT `FKa60lmk3v0eg77wuxi9jn4b8u0` FOREIGN KEY (`id`) REFERENCES `preferences` (`id`);

--
-- Constraints for table `axe_transport`
--
ALTER TABLE `axe_transport`
  ADD CONSTRAINT `FKd0kegdv8ihbg08cty4mbhw1u` FOREIGN KEY (`id`) REFERENCES `preferences` (`id`);

--
-- Constraints for table `axe_voyage`
--
ALTER TABLE `axe_voyage`
  ADD CONSTRAINT `FK4991wxtl45hsid59ottkki3jp` FOREIGN KEY (`id`) REFERENCES `preferences` (`id`);

--
-- Constraints for table `billet`
--
ALTER TABLE `billet`
  ADD CONSTRAINT `billet_ibfk_1` FOREIGN KEY (`transport_id`) REFERENCES `transport` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `client`
--
ALTER TABLE `client`
  ADD CONSTRAINT `FKod74ye6k4t6qnirp5a5a8bkm9` FOREIGN KEY (`id`) REFERENCES `utilisateur` (`id`);

--
-- Constraints for table `planning`
--
ALTER TABLE `planning`
  ADD CONSTRAINT `planning_ibfk_1` FOREIGN KEY (`id_voyageur`) REFERENCES `utilisateur` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `planning_ibfk_2` FOREIGN KEY (`id_activite`) REFERENCES `activite` (`idActivite`) ON DELETE CASCADE;

--
-- Constraints for table `reservation`
--
ALTER TABLE `reservation`
  ADD CONSTRAINT `fk_reservation_hebergement` FOREIGN KEY (`id_hebergement`) REFERENCES `hebergement` (`id_hebergement`) ON DELETE CASCADE;

--
-- Constraints for table `reservation_guest`
--
ALTER TABLE `reservation_guest`
  ADD CONSTRAINT `fk_guest_reservation` FOREIGN KEY (`id_reservation`) REFERENCES `reservation` (`id_reservation`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
