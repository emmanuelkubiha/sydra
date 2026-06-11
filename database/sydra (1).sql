-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:8889
-- Généré le : sam. 06 juin 2026 à 23:32
-- Version du serveur : 8.0.40
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `sydra`
--

-- --------------------------------------------------------

--
-- Structure de la table `account_invitations`
--

CREATE TABLE `account_invitations` (
  `id` bigint NOT NULL,
  `user_id` int NOT NULL,
  `email` varchar(190) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint NOT NULL,
  `user_id` int DEFAULT NULL,
  `action_type` varchar(100) NOT NULL,
  `entity_type` varchar(80) NOT NULL,
  `entity_id` varchar(80) DEFAULT NULL,
  `details` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `codification_logs`
--

CREATE TABLE `codification_logs` (
  `id` bigint NOT NULL,
  `report_id` bigint NOT NULL,
  `field_name` varchar(80) NOT NULL,
  `original_excerpt` text NOT NULL,
  `coded_excerpt` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `codification_rules`
--

CREATE TABLE `codification_rules` (
  `id` int NOT NULL,
  `term` varchar(150) NOT NULL,
  `replacement_code` varchar(80) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `email_change_requests`
--

CREATE TABLE `email_change_requests` (
  `id` bigint NOT NULL,
  `user_id` int NOT NULL,
  `requested_by` int DEFAULT NULL,
  `old_email` varchar(190) NOT NULL,
  `new_email` varchar(190) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `email_change_requests`
--

INSERT INTO `email_change_requests` (`id`, `user_id`, `requested_by`, `old_email`, `new_email`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES
(1, 18, 16, 'reporter@caritas-uvira.cd', 'fosipdrc@gmail.com', '799d71caf70c4dbf5f87dd88c39099add4c367ae0618071c2287bfdc1322ea8e', '2026-06-08 10:23:38', '2026-06-06 12:24:40', '2026-06-06 10:23:38');

-- --------------------------------------------------------

--
-- Structure de la table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` bigint NOT NULL,
  `report_id` bigint DEFAULT NULL,
  `sender_user_id` int NOT NULL,
  `recipient_email` varchar(180) NOT NULL,
  `subject_line` varchar(255) NOT NULL,
  `status` varchar(40) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `incident_types`
--

CREATE TABLE `incident_types` (
  `id` int NOT NULL,
  `code` varchar(50) NOT NULL,
  `label` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `incident_types`
--

INSERT INTO `incident_types` (`id`, `code`, `label`) VALUES
(1, 'SECURITY', 'Alerte securitaire'),
(2, 'DISPLACEMENT', 'Deplacement de population'),
(3, 'VIOLATION', 'Violation des droits humains'),
(4, 'NATURAL_DISASTER', 'Catastrophe naturelle');

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint NOT NULL,
  `user_id` int NOT NULL,
  `report_id` bigint NOT NULL,
  `status_code` varchar(50) NOT NULL,
  `title` varchar(160) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `mail_sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `target_url` varchar(255) DEFAULT NULL,
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `report_id`, `status_code`, `title`, `message`, `is_read`, `mail_sent_at`, `created_at`, `target_url`, `read_at`) VALUES
(1, 18, 6, 'UNDER_REVIEW', 'Demande d\'information', 'Manque d\'informations précises', 0, NULL, '2026-06-04 18:44:00', 'index.php?page=rapportage-details&id=6', NULL),
(2, 18, 5, 'APPROVED', 'Alerte validée', 'Test automatique validation API', 0, NULL, '2026-06-06 06:42:52', 'index.php?page=rapportage-details&id=5', NULL),
(8, 18, 5, 'UNDER_REVIEW', 'Demande d\'information', 'Manque d\'informations précises', 0, NULL, '2026-06-06 07:42:28', 'index.php?page=rapportage-details&id=5', NULL),
(9, 16, 2, 'UNDER_REVIEW', 'Demande d\'information', 'Bilan à vérifier', 0, NULL, '2026-06-06 07:44:07', 'index.php?page=rapportage-details&id=2', NULL),
(10, 16, 3, 'UNDER_REVIEW', 'Demande d\'information', 'Manque d\'informations précises', 0, NULL, '2026-06-06 07:45:39', 'index.php?page=rapportage-details&id=3', NULL),
(12, 16, 4, 'REJECTED', 'Alerte rejetée', 'Incident doublon', 0, NULL, '2026-06-06 07:51:05', 'index.php?page=rapportage-details&id=4', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `organizations`
--

CREATE TABLE `organizations` (
  `id` int NOT NULL,
  `name` varchar(180) NOT NULL,
  `email` varchar(180) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `organizations`
--

INSERT INTO `organizations` (`id`, `name`, `email`, `is_active`, `created_at`) VALUES
(1, 'Organisation Demo', 'org.demo@example.org', 1, '2026-06-01 12:34:03'),
(2, 'Organisation Demo', 'org.demo@example.org', 1, '2026-06-02 09:40:18'),
(3, 'FOSIP-DRC', 'it@fosip-drc.org', 1, '2026-06-04 14:07:58'),
(4, 'CARITAS-UVIRA', 'contact@caritas-uvira.cd', 1, '2026-06-04 14:19:10');

-- --------------------------------------------------------

--
-- Structure de la table `password_reset_requests`
--

CREATE TABLE `password_reset_requests` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `email` varchar(190) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `password_reset_requests`
--

INSERT INTO `password_reset_requests` (`id`, `user_id`, `email`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES
(4, 16, 'it@fosip-drc.org', 'ba0d829febd14f29a742dd45a4b48a9c95372b37458d191d5f96f4109c44667d', '2026-06-03 12:23:00', '2026-06-03 13:26:17', '2026-06-03 11:23:00'),
(5, 16, 'it@fosip-drc.org', '400c96af8a3d65421ec573aa48b603483b9b0d9df048876e2f7ee95c1a04e0ed', '2026-06-04 07:31:45', NULL, '2026-06-04 06:31:45'),
(6, 18, 'fosipdrc@gmail.com', '86f45692ac3a67d7f5f59dcffa3e6136dae61a327311ef4c433f76c4927ca882', '2026-06-06 11:25:02', '2026-06-06 12:29:36', '2026-06-06 10:25:02');

-- --------------------------------------------------------

--
-- Structure de la table `reports`
--

CREATE TABLE `reports` (
  `id` bigint NOT NULL,
  `reference_code` varchar(40) NOT NULL,
  `organization_id` int NOT NULL,
  `reporter_user_id` int NOT NULL,
  `report_type` enum('FLASH','NOTE') NOT NULL,
  `status_id` int NOT NULL,
  `incident_type_id` int DEFAULT NULL,
  `severity_id` int DEFAULT NULL,
  `urgency_id` int DEFAULT NULL,
  `province` varchar(120) DEFAULT NULL,
  `territory` varchar(120) DEFAULT NULL,
  `groupement` varchar(120) DEFAULT NULL,
  `locality` varchar(160) DEFAULT NULL,
  `place_search_text` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `households_count` int DEFAULT NULL,
  `people_count` int DEFAULT NULL,
  `vulnerable_categories` varchar(255) DEFAULT NULL,
  `context_text` text,
  `facts_text` text,
  `analysis_text` text,
  `impacts_text` text,
  `needs_text` text,
  `recommendations_text` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `urgency_level` varchar(20) NOT NULL DEFAULT 'Moyenne',
  `is_validated` tinyint(1) NOT NULL DEFAULT '0',
  `validated_by` int DEFAULT NULL,
  `validated_at` datetime DEFAULT NULL,
  `diffused_at` datetime DEFAULT NULL,
  `workflow_status` varchar(40) NOT NULL DEFAULT 'Brouillon',
  `incident_label` varchar(190) DEFAULT NULL,
  `gps_lat` decimal(10,7) DEFAULT NULL,
  `gps_lng` decimal(10,7) DEFAULT NULL,
  `victims_count` int DEFAULT NULL,
  `additional_notes` text,
  `reviewed_at` datetime DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `health_zone` varchar(140) DEFAULT NULL,
  `village` varchar(140) DEFAULT NULL,
  `incident_type` varchar(160) DEFAULT NULL,
  `displaced_households` int DEFAULT NULL,
  `priority_needs_text` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `reports`
--

INSERT INTO `reports` (`id`, `reference_code`, `organization_id`, `reporter_user_id`, `report_type`, `status_id`, `incident_type_id`, `severity_id`, `urgency_id`, `province`, `territory`, `groupement`, `locality`, `place_search_text`, `latitude`, `longitude`, `households_count`, `people_count`, `vulnerable_categories`, `context_text`, `facts_text`, `analysis_text`, `impacts_text`, `needs_text`, `recommendations_text`, `created_at`, `updated_at`, `submitted_at`, `urgency_level`, `is_validated`, `validated_by`, `validated_at`, `diffused_at`, `workflow_status`, `incident_label`, `gps_lat`, `gps_lng`, `victims_count`, `additional_notes`, `reviewed_at`, `published_at`, `rejected_at`, `health_zone`, `village`, `incident_type`, `displaced_households`, `priority_needs_text`) VALUES
(1, 'FOSIP-20260604140758-207F', 3, 16, 'FLASH', 2, 1, 4, 1, 'Sud-Kivu', 'Kalehe', 'Buzi', 'Minova', 'Minova, Kalehe', -2.1547000, 28.9891000, 118, 23, NULL, 'Donnée de démonstration pour test carte et détail.', 'Alerte Flash - Minova - faits consolidés.', 'Analyse terrain pour Minova.', NULL, 'Abris, protection, appui psychosocial.', 'Coordination rapide avec acteurs locaux.', '2026-06-04 14:07:58', '2026-06-04 14:07:58', '2026-06-01 12:07:58', 'Critique', 0, NULL, NULL, NULL, 'Soumis', 'Alerte Flash - Minova', -2.1547000, 28.9891000, 23, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 118, 'Assistance immédiate ciblée.'),
(2, 'FOSIP-20260604140758-9960', 3, 16, 'NOTE', 3, 1, 3, 1, 'Sud-Kivu', 'Fizi', 'Mutambala', 'Fizi', 'Fizi, Fizi', -4.3014000, 28.9448000, 47, 11, NULL, 'Donnée de démonstration pour test carte et détail.', 'Note Monitoring - Fizi - faits consolidés.', 'Analyse terrain pour Fizi.', NULL, 'Abris, protection, appui psychosocial.', 'Coordination rapide avec acteurs locaux.', '2026-06-04 14:07:58', '2026-06-06 09:44:07', '2026-06-02 12:07:58', 'Elevee', 0, NULL, NULL, NULL, 'Demande information', 'Note Monitoring - Fizi', -4.3014000, 28.9448000, 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 47, 'Assistance immédiate ciblée.'),
(3, 'FOSIP-20260604140758-32F9', 3, 16, 'FLASH', 3, 1, 2, 1, 'Sud-Kivu', 'Uvira', 'Kakungwe', 'Uvira', 'Uvira, Uvira', -3.4067000, 29.1458000, 32, 6, NULL, 'Donnée de démonstration pour test carte et détail.', 'Flash - Uvira Plaine - faits consolidés.', 'Analyse terrain pour Uvira.', NULL, 'Abris, protection, appui psychosocial.', 'Coordination rapide avec acteurs locaux.', '2026-06-04 14:07:58', '2026-06-06 09:45:39', '2026-06-03 12:07:58', 'Moyenne', 0, NULL, NULL, NULL, 'Demande information', 'Flash - Uvira Plaine', -3.4067000, 29.1458000, 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 32, 'Assistance immédiate ciblée.'),
(4, 'FOSIP-20260604140758-286B', 3, 16, 'NOTE', 5, 1, 1, 1, 'Maniema', 'Kindu', 'Kasuku', 'Kindu', 'Kindu, Kindu', -2.9508000, 25.9464000, 9, 1, NULL, 'Donnée de démonstration pour test carte et détail.', 'Monitoring - Kindu - faits consolidés.', 'Analyse terrain pour Kindu.', NULL, 'Abris, protection, appui psychosocial.', 'Coordination rapide avec acteurs locaux.', '2026-06-04 14:07:58', '2026-06-06 09:51:05', NULL, 'Faible', 0, NULL, NULL, NULL, 'Rejeté', 'Monitoring - Kindu', -2.9508000, 25.9464000, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 9, 'Assistance immédiate ciblée.'),
(5, 'SY-2025-CARITAS-001', 4, 18, 'FLASH', 3, 1, 4, 1, 'Nord-Kivu', 'Nyiragongo', NULL, 'Goma', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Une attaque armée survenue dans le quartier Karisimbi a provoqué des déplacements massifs. Environ 800 ménages ont fui vers le centre de Goma.', NULL, NULL, 'Abris d\'urgence, soins médicaux, eau potable', NULL, '2026-06-04 14:19:11', '2026-06-06 09:42:28', NULL, 'Critique', 0, NULL, NULL, NULL, 'Demande information', 'Attaque armée – quartier Karisimbi', -1.6735000, 29.2236000, 12, NULL, NULL, NULL, NULL, NULL, NULL, 'SECURITY', 800, NULL),
(6, 'SY-2025-CARITAS-002', 4, 18, 'FLASH', 3, 2, 3, 2, 'Sud-Kivu', 'Fizi', NULL, 'Baraka', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Suite aux affrontements intercommunautaires, plus de 1 200 ménages ont été déplacés sur l\'axe Baraka-Minembwe. Les familles sont sans abri depuis 10 jours.', NULL, NULL, 'Vivres, NFI, protection enfants non accompagnés', NULL, '2026-06-04 14:19:11', '2026-06-04 20:44:00', NULL, 'Elevee', 0, NULL, NULL, NULL, 'Demande information', 'Déplacement massif – axe Baraka-Minembwe', -4.0879000, 29.0791000, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'DISPLACEMENT', 1200, NULL),
(7, 'SY-2025-CARITAS-003', 4, 18, 'FLASH', 4, 3, 2, 2, 'Sud-Kivu', 'Shabunda', NULL, 'Shabunda centre', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Des violations graves des droits humains ont été documentées dans la zone de Shabunda, incluant des arrestations arbitraires et des confiscations de biens.', NULL, NULL, 'Assistance juridique, documentation, soutien psychosocial', NULL, '2026-06-04 14:19:11', '2026-06-04 14:19:11', NULL, 'Elevee', 0, NULL, NULL, NULL, 'Approuve', 'Violations droits humains – zone de Shabunda', -2.5253000, 27.3293000, 35, NULL, NULL, NULL, NULL, NULL, NULL, 'VIOLATION', 0, NULL),
(8, 'SY-2025-CARITAS-004', 4, 18, 'FLASH', 1, 4, 2, 3, 'Nord-Kivu', 'Lubero', NULL, 'Butembo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Des pluies torrentielles ont provoqué des glissements de terrain dans les quartiers périphériques de Butembo. Plusieurs maisons ont été détruites.', NULL, NULL, 'Abris temporaires, eau propre, kits hygiène', NULL, '2026-06-04 14:19:11', '2026-06-04 14:19:11', NULL, 'Moyenne', 0, NULL, NULL, NULL, 'Brouillon', 'Inondations – périphérie de Butembo', -0.1408000, 29.2903000, 3, NULL, NULL, NULL, NULL, NULL, NULL, 'NATURAL_DISASTER', 90, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `report_attachments`
--

CREATE TABLE `report_attachments` (
  `id` bigint NOT NULL,
  `report_id` bigint NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `storage_path` varchar(255) NOT NULL,
  `mime_type` varchar(120) DEFAULT NULL,
  `file_size` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `report_comments`
--

CREATE TABLE `report_comments` (
  `id` bigint NOT NULL,
  `report_id` bigint NOT NULL,
  `user_id` int NOT NULL,
  `comment_type` enum('CLARIFICATION','CORRECTION','VALIDATION') NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `report_exports`
--

CREATE TABLE `report_exports` (
  `id` bigint NOT NULL,
  `report_id` bigint NOT NULL,
  `exported_by` int NOT NULL,
  `export_format` enum('PDF','XLSX','CSV') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `report_statuses`
--

CREATE TABLE `report_statuses` (
  `id` int NOT NULL,
  `code` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `report_statuses`
--

INSERT INTO `report_statuses` (`id`, `code`, `label`) VALUES
(1, 'DRAFT', 'Brouillon'),
(2, 'SUBMITTED', 'Soumis'),
(3, 'UNDER_REVIEW', 'En revue'),
(4, 'APPROVED', 'Approuve'),
(5, 'REJECTED', 'Rejete');

-- --------------------------------------------------------

--
-- Structure de la table `report_status_history`
--

CREATE TABLE `report_status_history` (
  `id` bigint NOT NULL,
  `report_id` bigint NOT NULL,
  `status_label` varchar(60) NOT NULL,
  `event_note` varchar(255) DEFAULT NULL,
  `changed_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `report_status_history`
--

INSERT INTO `report_status_history` (`id`, `report_id`, `status_label`, `event_note`, `changed_by`, `created_at`) VALUES
(1, 1, 'Soumis', 'Alerte de démonstration injectée pour tests UI carte.', 16, '2026-06-04 14:07:58'),
(2, 2, 'En revue', 'Alerte de démonstration injectée pour tests UI carte.', 16, '2026-06-04 14:07:58'),
(3, 3, 'Approuve', 'Alerte de démonstration injectée pour tests UI carte.', 16, '2026-06-04 14:07:58'),
(4, 4, 'Brouillon', 'Alerte de démonstration injectée pour tests UI carte.', 16, '2026-06-04 14:07:58'),
(7, 6, 'Demande information', NULL, 16, '2026-06-04 18:44:00'),
(8, 5, 'Approuvé', 'Test automatique validation API', 16, '2026-06-06 06:42:52'),
(11, 5, 'Approuvé', 'Validation Lead GTMP.', 16, '2026-06-06 07:30:11'),
(12, 5, 'Approuvé', 'Validation Lead GTMP.', 16, '2026-06-06 07:30:23'),
(13, 5, 'Approuvé', 'Validation Lead GTMP.', 16, '2026-06-06 07:40:10'),
(14, 5, 'Demande information', 'Manque d\'informations précises', 16, '2026-06-06 07:42:28'),
(15, 2, 'Demande information', 'Bilan à vérifier', 16, '2026-06-06 07:44:07'),
(16, 3, 'Demande information', 'Manque d\'informations précises', 16, '2026-06-06 07:45:39'),
(17, 5, 'Demande information', 'Test diagnostic mail', 16, '2026-06-06 07:47:09'),
(18, 4, 'Rejeté', 'Incident doublon', 16, '2026-06-06 07:51:05');

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

CREATE TABLE `roles` (
  `id` int NOT NULL,
  `code` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `roles`
--

INSERT INTO `roles` (`id`, `code`, `label`) VALUES
(1, 'CLUSTER_LEADER', 'Leader Cluster Protection'),
(2, 'GTMP_LEAD', 'Lead GTMP'),
(3, 'GTMP_COLEAD', 'Co-Lead GTMP'),
(4, 'ORG_REPORTER', 'Rapporteur Organisation'),
(5, 'ADMIN', 'Administrateur'),
(6, 'CLUSTER_PROTECTION', 'Cluster Protection');

-- --------------------------------------------------------

--
-- Structure de la table `severity_levels`
--

CREATE TABLE `severity_levels` (
  `id` int NOT NULL,
  `code` varchar(50) NOT NULL,
  `label` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `severity_levels`
--

INSERT INTO `severity_levels` (`id`, `code`, `label`) VALUES
(1, 'LOW', 'Faible'),
(2, 'MEDIUM', 'Moyenne'),
(3, 'HIGH', 'Elevee'),
(4, 'CRITICAL', 'Critique');

-- --------------------------------------------------------

--
-- Structure de la table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int UNSIGNED NOT NULL,
  `setting_key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'active_ai_provider', 'xai', 'Fournisseur IA actif', '2026-06-06 21:43:32', '2026-06-06 21:43:32');

-- --------------------------------------------------------

--
-- Structure de la table `urgencies`
--

CREATE TABLE `urgencies` (
  `id` int NOT NULL,
  `code` varchar(50) NOT NULL,
  `label` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `urgencies`
--

INSERT INTO `urgencies` (`id`, `code`, `label`) VALUES
(1, 'IMMEDIATE', 'Immediat'),
(2, 'URGENT', 'Urgent'),
(3, 'NORMAL', 'Normal');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `organization_id` int DEFAULT NULL,
  `role_id` int NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(180) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `phone` varchar(60) DEFAULT NULL,
  `job_title` varchar(120) DEFAULT NULL,
  `organization_name` varchar(180) DEFAULT NULL,
  `bio` text,
  `avatar_path` varchar(255) DEFAULT NULL,
  `statut` enum('Actif','Bloque') NOT NULL DEFAULT 'Actif',
  `must_change_password` tinyint(1) NOT NULL DEFAULT '1',
  `last_login_at` datetime DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `telephone_organisation` varchar(80) DEFAULT NULL,
  `site_web` varchar(255) DEFAULT NULL,
  `bio_organisation` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `organization_id`, `role_id`, `full_name`, `email`, `password_hash`, `is_active`, `created_at`, `phone`, `job_title`, `organization_name`, `bio`, `avatar_path`, `statut`, `must_change_password`, `last_login_at`, `logo_path`, `telephone_organisation`, `site_web`, `bio_organisation`) VALUES
(1, 1, 4, 'Reporteur Terrain', 'reporter@sydra.local', '$2y$10$8lFIovoc3m6Mmhwm3mvMouefKf6KEDry9pF1TgdloIyfOWufkRWSe', 1, '2026-06-01 12:34:03', NULL, NULL, 'Reporteur Terrain', NULL, NULL, 'Actif', 1, NULL, NULL, NULL, NULL, NULL),
(5, 1, 2, 'Lead Cluster', 'lead.gtmp@sydra.local', 'y/APfRt7.hJsd2cdTyYqsuYS3aFdmd96voLq1zRnVM3KbyvsyyiRi', 1, '2026-06-02 09:40:18', NULL, NULL, 'Lead Cluster', NULL, NULL, 'Actif', 1, NULL, NULL, NULL, NULL, NULL),
(8, 1, 3, 'Co-Lead Cluster', 'colead.gtmp@sydra.local', 'y/APfRt7.hJsd2cdTyYqsuYS3aFdmd96voLq1zRnVM3KbyvsyyiRi', 1, '2026-06-02 09:40:18', NULL, NULL, 'Co-Lead Cluster', NULL, NULL, 'Actif', 1, NULL, NULL, NULL, NULL, NULL),
(12, 1, 6, 'Coordination Cluster Protection', 'cluster@sydra.local', 'y/APfRt7.hJsd2cdTyYqsuYS3aFdmd96voLq1zRnVM3KbyvsyyiRi', 1, '2026-06-02 09:40:18', NULL, NULL, 'Coordination Cluster Protection', NULL, NULL, 'Actif', 1, NULL, NULL, NULL, NULL, NULL),
(14, NULL, 1, 'Lead Cluster', 'lead.cluster@sydra.local', '$2y$10$Z3b5Dg6TUNMUzpe054X2OuIlD1wNawbwl0tZuiavOgZ5ukH.NVG1.', 1, '2026-06-03 09:21:12', NULL, NULL, 'Lead Cluster', NULL, NULL, 'Actif', 1, '2026-06-06 10:42:14', NULL, NULL, NULL, NULL),
(15, NULL, 3, 'Co-Lead Cluster', 'colead.cluster@sydra.local', '$2y$10$8lFIovoc3m6Mmhwm3mvMouefKf6KEDry9pF1TgdloIyfOWufkRWSe', 1, '2026-06-03 09:49:14', NULL, NULL, 'Co-Lead Cluster', NULL, NULL, 'Actif', 1, NULL, NULL, NULL, NULL, NULL),
(16, 3, 5, 'FOSIP-DRC', 'it@fosip-drc.org', '$2y$10$KOAqIwy/6UdqN5Ljqa/Jm.aM/2H.wq3Y31m5NH8hdnTOYxi87P2um', 1, '2026-06-03 10:03:34', '+243974051239', 'MEAL', 'FOSIP-DRC', NULL, 'uploads/avatars/avatar_16_20260604_063411_76a9d269.png', 'Actif', 1, '2026-06-06 23:45:35', 'uploads/avatars/avatar_16_20260604_063411_76a9d269.png', '+243974051239', 'https://fosip-drc.org', NULL),
(18, 4, 4, 'CARITAS-SYDRA', 'fosipdrc@gmail.com', '$2y$10$gugOrxrZW1eD5j1heImzTOjjMir3WhmZ9FoKkXHKHVRxY3R1sOC/u', 1, '2026-06-04 14:19:11', '+243974051239', NULL, 'CARITAS-SYDRA', 'Test juste pas vrai caritas', 'uploads/organizations/logos/org_logo_18_20260606_103704_b5cfb077.png', 'Actif', 0, '2026-06-06 12:29:53', 'uploads/organizations/logos/org_logo_18_20260606_103704_b5cfb077.png', '+243974051239', 'https://caritas.org', 'Test juste pas vrai caritas');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `account_invitations`
--
ALTER TABLE `account_invitations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_account_invite_user` (`user_id`),
  ADD KEY `idx_account_invite_token` (`token_hash`),
  ADD KEY `idx_account_invite_expires` (`expires_at`);

--
-- Index pour la table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `codification_logs`
--
ALTER TABLE `codification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`);

--
-- Index pour la table `codification_rules`
--
ALTER TABLE `codification_rules`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `email_change_requests`
--
ALTER TABLE `email_change_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_change_token` (`token_hash`),
  ADD KEY `idx_email_change_user` (`user_id`);

--
-- Index pour la table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`),
  ADD KEY `sender_user_id` (`sender_user_id`);

--
-- Index pour la table `incident_types`
--
ALTER TABLE `incident_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_notification_status` (`user_id`,`report_id`,`status_code`),
  ADD KEY `idx_notifications_user_read` (`user_id`,`is_read`),
  ADD KEY `idx_notifications_created_at` (`created_at`);

--
-- Index pour la table `organizations`
--
ALTER TABLE `organizations`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reset_token_hash` (`token_hash`),
  ADD KEY `idx_reset_user_id` (`user_id`);

--
-- Index pour la table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference_code` (`reference_code`),
  ADD KEY `organization_id` (`organization_id`),
  ADD KEY `reporter_user_id` (`reporter_user_id`),
  ADD KEY `status_id` (`status_id`),
  ADD KEY `incident_type_id` (`incident_type_id`),
  ADD KEY `severity_id` (`severity_id`),
  ADD KEY `urgency_id` (`urgency_id`);

--
-- Index pour la table `report_attachments`
--
ALTER TABLE `report_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ra_report` (`report_id`);

--
-- Index pour la table `report_comments`
--
ALTER TABLE `report_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `report_exports`
--
ALTER TABLE `report_exports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`),
  ADD KEY `exported_by` (`exported_by`);

--
-- Index pour la table `report_statuses`
--
ALTER TABLE `report_statuses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Index pour la table `report_status_history`
--
ALTER TABLE `report_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rsh_report` (`report_id`),
  ADD KEY `idx_rsh_created` (`created_at`),
  ADD KEY `fk_rsh_user` (`changed_by`);

--
-- Index pour la table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Index pour la table `severity_levels`
--
ALTER TABLE `severity_levels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Index pour la table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_system_settings_key` (`setting_key`);

--
-- Index pour la table `urgencies`
--
ALTER TABLE `urgencies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `organization_id` (`organization_id`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `account_invitations`
--
ALTER TABLE `account_invitations`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `codification_logs`
--
ALTER TABLE `codification_logs`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `codification_rules`
--
ALTER TABLE `codification_rules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `email_change_requests`
--
ALTER TABLE `email_change_requests`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `incident_types`
--
ALTER TABLE `incident_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `organizations`
--
ALTER TABLE `organizations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `report_attachments`
--
ALTER TABLE `report_attachments`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `report_comments`
--
ALTER TABLE `report_comments`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `report_exports`
--
ALTER TABLE `report_exports`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `report_statuses`
--
ALTER TABLE `report_statuses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `report_status_history`
--
ALTER TABLE `report_status_history`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `severity_levels`
--
ALTER TABLE `severity_levels`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `urgencies`
--
ALTER TABLE `urgencies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `account_invitations`
--
ALTER TABLE `account_invitations`
  ADD CONSTRAINT `fk_account_invite_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `codification_logs`
--
ALTER TABLE `codification_logs`
  ADD CONSTRAINT `codification_logs_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `email_change_requests`
--
ALTER TABLE `email_change_requests`
  ADD CONSTRAINT `fk_email_change_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `email_logs`
--
ALTER TABLE `email_logs`
  ADD CONSTRAINT `email_logs_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `email_logs_ibfk_2` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`reporter_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reports_ibfk_3` FOREIGN KEY (`status_id`) REFERENCES `report_statuses` (`id`),
  ADD CONSTRAINT `reports_ibfk_4` FOREIGN KEY (`incident_type_id`) REFERENCES `incident_types` (`id`),
  ADD CONSTRAINT `reports_ibfk_5` FOREIGN KEY (`severity_id`) REFERENCES `severity_levels` (`id`),
  ADD CONSTRAINT `reports_ibfk_6` FOREIGN KEY (`urgency_id`) REFERENCES `urgencies` (`id`);

--
-- Contraintes pour la table `report_attachments`
--
ALTER TABLE `report_attachments`
  ADD CONSTRAINT `fk_ra_report` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `report_comments`
--
ALTER TABLE `report_comments`
  ADD CONSTRAINT `report_comments_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `report_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `report_exports`
--
ALTER TABLE `report_exports`
  ADD CONSTRAINT `report_exports_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `report_exports_ibfk_2` FOREIGN KEY (`exported_by`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `report_status_history`
--
ALTER TABLE `report_status_history`
  ADD CONSTRAINT `fk_rsh_report` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rsh_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
