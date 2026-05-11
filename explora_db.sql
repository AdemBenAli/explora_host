SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
SET time_zone = "+00:00";

-- TABLE: transport
DROP TABLE IF EXISTS `transport`;
CREATE TABLE `transport` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
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

INSERT INTO `transport` (`id`, `type`, `categorie`, `origine`, `destination`, `date_depart`, `date_arrivee`, `heure_depart`, `heure_arrivee`, `prix`, `places_disponibles`, `compagnie`, `numero_vol`, `image_url`, `description`, `etat_trafic`, `image_trafic_url`, `derniere_analyse`, `score_confiance`, `prix_original`, `derniere_maj_prix`, `distance_km`, `emissions_kg_co2`, `categorie_ecologique`) VALUES
(1, 'BUS', 'EN_COURS', 'Centre-ville', 'Les berges du lac', '2026-02-10', NULL, '08:00:00', '09:00:00', '9.00', 45, 'TunisTransport', NULL, NULL, NULL, 'FLUIDE', NULL, NULL, NULL, '10.00', '2026-03-03 08:46:39', 500, 34, 'BON'),
(2, 'AVION', 'PRE_VOYAGE', 'Tunis', 'Paris', '2026-02-15', '2026-02-15', '14:30:00', '17:45:00', '350.00', 173, 'Air France', 'AF1234', NULL, NULL, 'FLUIDE', NULL, NULL, NULL, '350.00', '2026-03-01 23:04:37', 1482.3240429784696, 277.1945960369738, 'MAUVAIS'),
(3, 'BATEAU', 'PRE_VOYAGE', 'Tunis', 'Marseille', '2026-02-20', '2026-02-21', '22:00:00', '08:00:00', '231.00', 200, 'CTN', 'CTN456', NULL, NULL, 'FLUIDE', NULL, NULL, NULL, '150.00', '2026-03-03 08:46:39', 829.4085265862739, 207.35213164656847, 'MAUVAIS'),
(4, 'TRAIN', 'PRE_VOYAGE', 'Milan', 'Paris', '2026-02-25', '2026-02-25', '10:00:00', '16:00:00', '278.00', 94, 'Trenitalia', 'MIL205', NULL, NULL, 'FLUIDE', NULL, NULL, NULL, '200.00', '2026-03-03 08:46:40', 500, 7, 'EXCELLENT'),
(5, 'TAXI', 'EN_COURS', 'Aéroport Tunis', 'Centre-ville', '2026-02-12', NULL, '15:00:00', '16:00:00', '22.00', 4, 'Taxi Tunis', NULL, NULL, NULL, 'FLUIDE', NULL, NULL, NULL, '30.00', '2026-03-03 08:46:39', 500, 96.5, 'MOYEN'),
(6, 'VOITURE', 'EN_COURS', 'Sousse', 'Hammamet', '2026-02-18', NULL, '09:00:00', '10:30:00', '25.00', 3, 'Location Auto', NULL, NULL, NULL, 'FLUIDE', NULL, NULL, NULL, NULL, NULL, 70, 12, 'BON'),
(7, 'AVION', 'PRE_VOYAGE', 'Tunis', 'Rome', '2026-03-01', '2026-03-01', '11:00:00', '12:30:00', '260.00', 150, 'Tunisair', 'TU789', NULL, NULL, 'FLUIDE', NULL, NULL, NULL, '280.00', '2026-03-03 08:46:38', 600.5523606416775, 154.9425090455528, 'MAUVAIS'),
(8, 'BUS', 'EN_COURS', 'Hammamet', 'Sousse', '2026-02-19', NULL, '14:00:00', '15:30:00', '6.00', 50, 'SNTRI', NULL, NULL, NULL, 'FLUIDE', NULL, NULL, NULL, '8.00', '2026-03-03 08:46:39', 63.896135990913436, 4.344937247382114, 'EXCELLENT'),
(9, 'VOITURE', 'EN_COURS', 'Sfax', 'Sousse', '2026-02-17', NULL, '11:30:00', NULL, '35.00', 4, 'Rent a car', NULL, NULL, NULL, 'FLUIDE', NULL, NULL, NULL, '50.00', '2026-03-03 08:46:39', 121.16529350043277, 23.384901645583522, 'BON'),
(10, 'BUS', 'EN_COURS', 'Tunis', 'Bardo', '2026-03-05', NULL, '07:00:00', '07:45:00', '2.50', 50, 'SNTRI', NULL, NULL, 'Trajet court Tunis-Bardo', 'FLUIDE', NULL, NULL, NULL, '2.50', '2026-03-02 00:09:46', 8.5, 0.68, 'EXCELLENT'),
(11, 'BUS', 'EN_COURS', 'Bardo', 'Tunis', '2026-03-05', NULL, '08:30:00', '09:15:00', '2.50', 45, 'SNTRI', NULL, NULL, 'Retour Bardo vers Tunis', 'FLUIDE', NULL, NULL, NULL, '2.50', '2026-03-02 00:09:46', 8.5, 0.68, 'EXCELLENT'),
(12, 'BUS', 'EN_COURS', 'Tunis', 'Ariana', '2026-03-05', NULL, '06:30:00', '07:15:00', '3.00', 48, 'SNTRI', NULL, NULL, 'Tunis à Ariana - court trajet', 'FLUIDE', NULL, NULL, NULL, '3.00', '2026-03-02 00:09:46', 7.2, 0.58, 'EXCELLENT'),
(13, 'BUS', 'EN_COURS', 'Ariana', 'Tunis', '2026-03-05', NULL, '17:00:00', '17:45:00', '3.00', 42, 'SNTRI', NULL, NULL, 'Ariana vers Tunis', 'FLUIDE', NULL, NULL, NULL, '3.00', '2026-03-02 00:09:46', 7.2, 0.58, 'EXCELLENT'),
(14, 'BUS', 'EN_COURS', 'Tunis', 'La Marsa', '2026-03-05', NULL, '08:00:00', '09:00:00', '4.00', 55, 'TunisTransport', NULL, NULL, 'Tunis à La Marsa', 'FLUIDE', NULL, NULL, NULL, '4.00', '2026-03-02 00:09:46', 10, 0.8, 'BON'),
(15, 'BUS', 'EN_COURS', 'La Marsa', 'Carthage', '2026-03-05', NULL, '09:30:00', '10:15:00', '2.00', 50, 'TunisTransport', NULL, NULL, 'La Marsa à Carthage', 'FLUIDE', NULL, NULL, NULL, '2.00', '2026-03-02 00:09:46', 3.5, 0.28, 'EXCELLENT'),
(16, 'BUS', 'EN_COURS', 'Sidi Bou Said', 'La Marsa', '2026-03-05', NULL, '10:00:00', '10:25:00', '2.00', 50, 'TunisTransport', NULL, NULL, 'Sidi Bou Said à La Marsa', 'FLUIDE', NULL, NULL, NULL, '2.00', '2026-03-02 00:09:46', 4, 0.32, 'EXCELLENT'),
(17, 'TAXI', 'EN_COURS', 'Tunis', 'Bardo', '2026-03-05', NULL, '10:00:00', '10:30:00', '8.50', 4, 'Taxi Blanc', NULL, NULL, 'Taxi rapide Tunis-Bardo', 'FLUIDE', NULL, NULL, NULL, '10.00', '2026-03-02 00:09:46', 8.5, 1.53, 'BON'),
(18, 'TAXI', 'EN_COURS', 'Bardo', 'Ariana', '2026-03-05', NULL, '14:00:00', '14:25:00', '6.50', 4, 'Taxi Blanc', NULL, NULL, 'Bardo à Ariana', 'FLUIDE', NULL, NULL, NULL, '8.00', '2026-03-02 00:09:46', 6, 1.08, 'BON'),
(19, 'TAXI', 'EN_COURS', 'Tunis', 'Manouba', '2026-03-05', NULL, '11:00:00', '11:35:00', '7.00', 4, 'Taxi Blanc', NULL, NULL, 'Tunis à Manouba', 'FLUIDE', NULL, NULL, NULL, '8.50', '2026-03-02 00:09:46', 8, 1.44, 'BON'),
(20, 'TRAIN', 'EN_COURS', 'Tunis', 'Sousse', '2026-03-06', NULL, '06:00:00', '08:00:00', '15.00', 120, 'SNCFT', 'TUN001', NULL, 'Train régional Tunis-Sousse', 'FLUIDE', NULL, NULL, NULL, '15.00', '2026-03-02 00:09:46', 140, 5.6, 'EXCELLENT'),
(21, 'TRAIN', 'EN_COURS', 'Sousse', 'Hammamet', '2026-03-06', NULL, '09:30:00', '11:00:00', '12.00', 100, 'SNCFT', 'SOU002', NULL, 'Sousse à Hammamet', 'FLUIDE', NULL, NULL, NULL, '12.00', '2026-03-02 00:09:46', 50, 2, 'EXCELLENT'),
(22, 'TRAIN', 'EN_COURS', 'Tunis', 'Sfax', '2026-03-06', NULL, '07:30:00', '10:45:00', '22.00', 150, 'SNCFT', 'TUN003', NULL, 'Train express Tunis-Sfax', 'FLUIDE', NULL, NULL, NULL, '22.00', '2026-03-02 00:09:46', 280, 11.2, 'EXCELLENT'),
(23, 'VOITURE', 'EN_COURS', 'Tunis', 'Ezzahra', '2026-03-05', NULL, '13:00:00', '13:35:00', '9.50', 4, 'Location Auto Tunis', NULL, NULL, 'Tunis à Ezzahra', 'FLUIDE', NULL, NULL, NULL, '10.00', '2026-03-02 00:09:46', 6.5, 1.17, 'BON'),
(24, 'VOITURE', 'EN_COURS', 'Hammamet', 'Sousse', '2026-03-05', NULL, '09:00:00', '10:15:00', '18.00', 4, 'Location Auto', NULL, NULL, 'Hammamet à Sousse', 'FLUIDE', NULL, NULL, NULL, '20.00', '2026-03-02 00:09:46', 65, 11.7, 'BON');

-- TABLE: billet
DROP TABLE IF EXISTS `billet`;
CREATE TABLE `billet` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `transport_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `voyage_id` int(11) DEFAULT NULL,
  `nombre_places` int(11) DEFAULT NULL,
  `prix_total` double DEFAULT NULL,
  `date_reservation` date DEFAULT NULL,
  `statut` varchar(20) DEFAULT 'EN_ATTENTE',
  `created_at` datetime DEFAULT NULL,
  `qr_code` longtext DEFAULT NULL,
  CONSTRAINT `fk_billet_transport` FOREIGN KEY (`transport_id`) REFERENCES `transport` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `billet` (`id`, `transport_id`, `user_id`, `voyage_id`, `nombre_places`, `prix_total`, `date_reservation`, `statut`) VALUES
(1, 1, 1, NULL, 2, 20, '2026-02-04', 'PAYE'),
(2, 2, 1, 1, 1, 350, '2026-02-03', 'CONFIRME'),
(3, 3, 2, 2, 2, 300, '2026-02-02', 'PAYE'),
(4, 4, 1, 3, 3, 600, '2026-02-01', 'CONFIRME'),
(5, 1, 1, NULL, 1, 10, '2026-02-05', 'EN_ATTENTE'),
(6, 7, 1, 4, 2, 560, '2026-02-04', 'EN_ATTENTE'),
(7, 5, 2, NULL, 4, 120, '2026-02-03', 'ANNULE'),
(8, 4, 1, NULL, 2, 400, '2026-02-07', 'EN_ATTENTE'),
(9, 4, 1, NULL, 4, 800, '2026-02-07', 'EN_ATTENTE'),
(17, 2, 1, NULL, 1, 350, '2026-03-02', 'EN_ATTENTE'),
(18, 7, 1, NULL, 1, 260, '2026-03-02', 'EN_ATTENTE'),
(20, 2, 1, NULL, 1, 350, '2026-03-02', 'EN_ATTENTE'),
(21, 2, 1, NULL, 1, 350, '2026-03-02', 'EN_ATTENTE'),
(22, 4, 1, NULL, 1, 278, '2026-03-02', 'EN_ATTENTE'),
(23, 2, 1, NULL, 1, 350, '2026-03-02', 'EN_ATTENTE'),
(24, 1, 1, NULL, 1, 9, '2026-03-02', 'EN_ATTENTE');

-- TABLE: eco_scores
DROP TABLE IF EXISTS `eco_scores`;
CREATE TABLE `eco_scores` (
  `user_id` int(11) NOT NULL PRIMARY KEY,
  `points_actuels` int(11) DEFAULT 0,
  `points_total` int(11) DEFAULT 0,
  `niveau_actuel` int(11) DEFAULT 0,
  `reduction_disponible` tinyint(1) DEFAULT 0,
  `voyages_eco` int(11) DEFAULT 0,
  `co2_economise` double DEFAULT 0,
  `derniere_maj` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `eco_scores` (`user_id`, `points_actuels`, `points_total`, `niveau_actuel`, `reduction_disponible`, `voyages_eco`, `co2_economise`, `derniere_maj`) VALUES
(1, 80, 80, 1, 1, 2, 41, '2026-03-02 10:32:39');

-- TABLE: boutique_velo
DROP TABLE IF EXISTS `boutique_velo`;
CREATE TABLE `boutique_velo` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `nom` varchar(100) NOT NULL,
  `adresse` varchar(255) NOT NULL,
  `ville` varchar(50) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `reduction` int(11) DEFAULT 50
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `boutique_velo` (`id`, `nom`, `adresse`, `ville`, `telephone`, `latitude`, `longitude`, `reduction`) VALUES
(1, 'EcoBike Tunis Centre', 'Avenue Habib Bourguiba', 'Tunis', '+216 71 123 456', '36.80650000', '10.18150000', 50),
(2, 'Velo City La Marsa', 'Avenue Taieb Mhiri', 'La Marsa', '+216 71 234 567', '36.87810000', '10.32500000', 50),
(11, 'Velo Shop La Marsa', '12 Avenue Habib Bourguiba, La Marsa', 'La Marsa', '71 123 456', '36.87810000', '10.32500000', 50),
(12, 'Bike Center Carthage', '45 Rue de Carthage, Carthage', 'Carthage', '71 234 567', '36.85310000', '10.32310000', 50);

-- TABLE: code_promo_velo
DROP TABLE IF EXISTS `code_promo_velo`;
CREATE TABLE `code_promo_velo` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `origine` varchar(50) NOT NULL,
  `destination` varchar(50) NOT NULL,
  `code` varchar(20) NOT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_utilisation` datetime DEFAULT NULL,
  `utilise` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
