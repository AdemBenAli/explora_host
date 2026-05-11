-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : sam. 18 avr. 2026 à 16:54
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `explora`
--

-- --------------------------------------------------------

--
-- Structure de la table `avis`
--

CREATE TABLE `avis` (
  `id_avis` int(11) NOT NULL,
  `id_hebergement` int(11) DEFAULT NULL,
  `nom_auteur` varchar(120) DEFAULT NULL,
  `note` int(11) DEFAULT NULL,
  `commentaire` text DEFAULT NULL,
  `date_avis` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `avis`
--

INSERT INTO `avis` (`id_avis`, `id_hebergement`, `nom_auteur`, `note`, `commentaire`, `date_avis`) VALUES
(1, 2, 'Guest', 3, 'woow!!', '2026-02-22 17:44:57'),
(2, 2, 'Guest', 4, 'maghinfique!!', '2026-02-22 17:53:06'),
(3, 2, 'Guest', 2, '', '2026-02-22 18:04:21'),
(4, 2, 'Guest', 2, '', '2026-02-22 18:04:34'),
(5, 2, 'Guest', 4, 'wow!!', '2026-02-23 09:11:04'),
(6, 2, 'Guest', 5, '', '2026-02-24 13:01:11'),
(7, 2, 'Guest', 5, '', '2026-02-24 13:01:17'),
(8, 2, 'Guest', 3, '', '2026-02-24 13:01:23'),
(9, 3, 'Guest', 3, 'je vois que c\'est extraordinaire et c\'est tres bien organisé', '2026-02-26 13:13:10'),
(10, 3, 'Guest', 3, 'its average', '2026-02-26 18:09:13'),
(11, 3, 'Guest', 5, 'so good', '2026-02-26 18:09:23'),
(12, 3, 'Guest', 1, 'its bad', '2026-02-26 18:09:34'),
(13, 3, 'Guest', 1, 'i hated the service', '2026-02-26 18:10:36'),
(14, 3, 'Guest', 5, 'there is a very good view', '2026-02-26 18:10:49'),
(15, 3, 'Guest', 5, 'very nice', '2026-02-26 18:11:34'),
(16, 3, 'Guest', 5, 'super comfortable', '2026-02-26 18:11:51'),
(17, 3, 'Guest', 1, 'i hate this place its so cold', '2026-02-26 20:03:53'),
(19, 4, 'emnaaaaaaaaaaaa', 5, 'its amazing', '2026-04-13 01:08:07'),
(20, 4, 'ammoun', 1, 'i hate it', '2026-04-13 01:09:17'),
(21, 4, 'ammoun', 3, 'i didnt like the service', '2026-04-13 01:09:54'),
(22, 4, 'Guest', 5, 'its very good', '2026-04-13 01:10:25'),
(23, 4, 'gggghhhh', 3, 'i like the view very much', '2026-04-13 01:51:10'),
(24, 4, 'Guest', 3, 'the rooms are too tiny', '2026-04-13 01:52:57'),
(25, 4, 'Guest', 5, 'so amazing experience', '2026-04-16 22:53:29'),
(26, 3, 'Guest', 5, 'i love this place', '2026-04-17 23:05:33');

-- --------------------------------------------------------

--
-- Structure de la table `doctrine_migration_versions`
--

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `hebergement`
--

CREATE TABLE `hebergement` (
  `id_hebergement` int(11) NOT NULL,
  `nom` varchar(150) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `localisation` varchar(150) DEFAULT NULL,
  `pays` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `prix_par_nuit` double DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `note_moyenne` double DEFAULT 0,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `image_path` varchar(255) DEFAULT NULL,
  `special_couple` tinyint(1) NOT NULL DEFAULT 0,
  `under18_allowed` tinyint(1) NOT NULL DEFAULT 0,
  `sea_view` tinyint(1) NOT NULL DEFAULT 0,
  `capacite` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `hebergement`
--

INSERT INTO `hebergement` (`id_hebergement`, `nom`, `type`, `localisation`, `pays`, `description`, `prix_par_nuit`, `latitude`, `longitude`, `note_moyenne`, `date_creation`, `image_path`, `special_couple`, `under18_allowed`, `sea_view`, `capacite`, `updated_at`) VALUES
(2, 'mariott', 'Hotel', 'Tunis', 'Tunisie', 'magnifique', 500, 36.816981, 10.16922, 0, '2026-02-22 12:30:53', 'hotel-1-69d416e7784b0.jpg', 0, 0, 0, 10000, NULL),
(3, 'badira', 'Hotel', 'kelibia', 'Tunisie', 'luxueux', 600, 36.849646, 11.096921, 3.4, '2026-02-24 21:19:06', 'hotel-2-69d416fdbf8c5.jpg', 0, 0, 1, 8000, NULL),
(4, 'parisienne', 'Hostel', 'Champs élysées', 'France', 'tres propre', 150, 48.86319, 2.463598, 3.6, '2026-02-27 15:13:35', 'hotel-paris-360-69d416a284cc1.jpg', 0, 1, 0, 5000, NULL),
(6, 'radisson', 'Hostel', 'Tunis', 'تونس', 'parfait', 300, 36.70366, 10.085449, 0, '2026-03-01 21:08:53', 'hotel1-360-69d416cb09880.jpg', 1, 0, 1, 7000, NULL),
(11, 'novotel', 'Hostel', 'marsa', NULL, 'tres top', 1000, NULL, NULL, NULL, '2026-04-05 23:02:43', 'hotel2-360-69d41689ea503.jpg', 0, 1, 0, 9000, NULL),
(14, 'laico', 'Hostel', 'Tunis', NULL, 'luxe', 1000, NULL, NULL, NULL, '2026-04-07 13:57:26', 'hotel3-360-69d50d4603f3c.jpg', 0, 1, 1, 10000, NULL),
(15, 'test', 'Motel', 'la marsa', 'تونس', 'hey', 500, 35.37113502280101, 9.206542968750002, 0, '2026-04-07 14:48:02', 'uploads/hotel_a553a05b21924aa088e7aa235efea8cd.jpg', 0, 0, 1, 5000, NULL),
(16, 'ttttt', 'Hotel', 'la marsa', NULL, 'kjnbhvbjhk', 400, NULL, NULL, NULL, '2026-04-17 01:34:45', 'greatest-show-on-earth-gif-by-kid-rock-69e18e35865b2817871010.gif', 0, 0, 0, 5000, '2026-04-17 03:34:45'),
(17, 'bundle', 'Motel', 'Kelibia Est', NULL, 'bundle bundle', 400, 36.862773, 11.084551, NULL, '2026-04-17 01:36:16', 'hotel-6-panorama-69e2d49fd96a7489125698.jpg', 0, 0, 0, 7000, '2026-04-18 02:47:27'),
(18, 'panorama', 'Hostel', 'Hammamet', NULL, 'panoramiqueeeee', 5000, 36.276336, 10.570145, NULL, '2026-04-18 00:33:53', 'hotel-5-panorama-69e2d246d5f15781075379.jpg', 0, 0, 0, 50000, '2026-04-18 02:37:26');

-- --------------------------------------------------------

--
-- Structure de la table `reservation`
--

CREATE TABLE `reservation` (
  `id_reservation` int(11) NOT NULL,
  `id_hebergement` int(11) DEFAULT NULL,
  `nom_client` varchar(120) DEFAULT NULL,
  `email_client` varchar(120) DEFAULT NULL,
  `date_checkin` date DEFAULT NULL,
  `date_checkout` date DEFAULT NULL,
  `statut` varchar(30) DEFAULT NULL,
  `prix_total` double DEFAULT NULL,
  `date_reservation` timestamp NOT NULL DEFAULT current_timestamp(),
  `guests_count` int(11) NOT NULL DEFAULT 1,
  `rooms_count` int(11) NOT NULL DEFAULT 1,
  `occupancy` varchar(10) NOT NULL DEFAULT 'DOUBLE',
  `room_type` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `reservation`
--

INSERT INTO `reservation` (`id_reservation`, `id_hebergement`, `nom_client`, `email_client`, `date_checkin`, `date_checkout`, `statut`, `prix_total`, `date_reservation`, `guests_count`, `rooms_count`, `occupancy`, `room_type`) VALUES
(1, 2, 'emna', NULL, '2026-03-01', '2026-03-08', 'CONFIRMED', 3500, '2026-02-22 13:53:39', 1, 1, 'DOUBLE', NULL),
(2, 2, 'ammouna', NULL, '2026-02-22', '2026-03-01', 'CONFIRMED', 3500, '2026-02-22 15:02:22', 1, 1, 'DOUBLE', NULL),
(4, 2, 'sousou', NULL, '2026-02-22', '2026-03-01', 'CONFIRMED', 4200, '2026-02-22 16:18:55', 1, 1, 'DOUBLE', NULL),
(8, 6, 'emna', 'ellafi', '2026-03-04', '2026-03-18', 'CONFIRMED', 3504, '2026-03-02 00:01:45', 1, 1, 'DOUBLE', 'Standard'),
(12, 4, 'emnaaa', NULL, '2026-04-14', '2026-04-21', 'CONFIRMED', 2266.7, '2026-04-07 14:22:50', 2, 2, 'SINGLE', 'Deluxe'),
(14, 4, 'maram', 'maram@gmail.com', '2026-04-14', '2026-04-28', 'CONFIRMED', 4029.6, '2026-04-07 15:02:24', 2, 2, 'SINGLE', 'Deluxe'),
(15, 15, 'emnaaa', 'maram@gmail.com', '2026-04-07', '2026-04-21', 'CONFIRMED', 6716, '2026-04-07 15:53:04', 2, 1, 'DOUBLE', 'Deluxe');

-- --------------------------------------------------------

--
-- Structure de la table `reservation_guest`
--

CREATE TABLE `reservation_guest` (
  `id_guest` int(11) NOT NULL,
  `id_reservation` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `birth_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `reservation_guest`
--

INSERT INTO `reservation_guest` (`id_guest`, `id_reservation`, `full_name`, `birth_date`) VALUES
(3, 8, 'chalh', NULL),
(7, 12, 'guest 1', '2025-11-18'),
(8, 12, 'guest 2', '2026-04-01'),
(10, 14, 'guest 1', '2026-04-01'),
(11, 14, 'guest 2', '2026-04-02'),
(12, 15, 'guest 1', '2026-04-01'),
(13, 15, 'guest 2', '2026-04-03');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `avis`
--
ALTER TABLE `avis`
  ADD PRIMARY KEY (`id_avis`),
  ADD KEY `id_hebergement` (`id_hebergement`);

--
-- Index pour la table `doctrine_migration_versions`
--
ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

--
-- Index pour la table `hebergement`
--
ALTER TABLE `hebergement`
  ADD PRIMARY KEY (`id_hebergement`);

--
-- Index pour la table `reservation`
--
ALTER TABLE `reservation`
  ADD PRIMARY KEY (`id_reservation`),
  ADD KEY `id_hebergement` (`id_hebergement`);

--
-- Index pour la table `reservation_guest`
--
ALTER TABLE `reservation_guest`
  ADD PRIMARY KEY (`id_guest`),
  ADD KEY `fk_guest_reservation` (`id_reservation`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `avis`
--
ALTER TABLE `avis`
  MODIFY `id_avis` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT pour la table `hebergement`
--
ALTER TABLE `hebergement`
  MODIFY `id_hebergement` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `reservation`
--
ALTER TABLE `reservation`
  MODIFY `id_reservation` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `reservation_guest`
--
ALTER TABLE `reservation_guest`
  MODIFY `id_guest` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `avis`
--
ALTER TABLE `avis`
  ADD CONSTRAINT `avis_ibfk_1` FOREIGN KEY (`id_hebergement`) REFERENCES `hebergement` (`id_hebergement`);

--
-- Contraintes pour la table `reservation`
--
ALTER TABLE `reservation`
  ADD CONSTRAINT `reservation_ibfk_1` FOREIGN KEY (`id_hebergement`) REFERENCES `hebergement` (`id_hebergement`);

--
-- Contraintes pour la table `reservation_guest`
--
ALTER TABLE `reservation_guest`
  ADD CONSTRAINT `fk_guest_reservation` FOREIGN KEY (`id_reservation`) REFERENCES `reservation` (`id_reservation`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
