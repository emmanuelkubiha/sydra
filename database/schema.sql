-- ============================================================
-- SyDRA – Schéma complet de la base de données
-- Exporté depuis MySQL (SHOW CREATE TABLE)
-- Mis à jour le : 2026-06-04
-- Engine : InnoDB | Charset : utf8mb4_0900_ai_ci
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET NAMES utf8mb4;

-- ============================================================
-- 1. TABLES DE RÉFÉRENCE (aucune dépendance)
-- ============================================================

CREATE TABLE IF NOT EXISTS `organizations` (
  `id`         int            NOT NULL AUTO_INCREMENT,
  `name`       varchar(180)   NOT NULL,
  `email`      varchar(180)   DEFAULT NULL,
  `is_active`  tinyint(1)     NOT NULL DEFAULT '1',
  `created_at` timestamp      NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `roles` (
  `id`    int          NOT NULL AUTO_INCREMENT,
  `code`  varchar(50)  NOT NULL,
  `label` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT IGNORE INTO `roles` (`code`, `label`) VALUES
  ('CLUSTER_LEADER',     'Chef de Cluster'),
  ('GTMP_LEAD',          'Chef GTMP'),
  ('GTMP_COLEAD',        'Co-Chef GTMP'),
  ('ORG_REPORTER',       'Rapporteur Organisation'),
  ('ADMIN',              'Administrateur'),
  ('CLUSTER_PROTECTION', 'Cluster Protection');

CREATE TABLE IF NOT EXISTS `report_statuses` (
  `id`    int          NOT NULL AUTO_INCREMENT,
  `code`  varchar(50)  NOT NULL,
  `label` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT IGNORE INTO `report_statuses` (`code`, `label`) VALUES
  ('DRAFT',        'Brouillon'),
  ('SUBMITTED',    'Soumis'),
  ('UNDER_REVIEW', 'En revue'),
  ('APPROVED',     'Approuvé'),
  ('REJECTED',     'Rejeté');

CREATE TABLE IF NOT EXISTS `incident_types` (
  `id`    int          NOT NULL AUTO_INCREMENT,
  `code`  varchar(50)  NOT NULL,
  `label` varchar(120) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT IGNORE INTO `incident_types` (`code`, `label`) VALUES
  ('SECURITY',         'Sécurité'),
  ('DISPLACEMENT',     'Déplacement'),
  ('VIOLATION',        'Violation droits humains'),
  ('NATURAL_DISASTER', 'Catastrophe naturelle');

CREATE TABLE IF NOT EXISTS `severity_levels` (
  `id`    int          NOT NULL AUTO_INCREMENT,
  `code`  varchar(50)  NOT NULL,
  `label` varchar(120) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT IGNORE INTO `severity_levels` (`code`, `label`) VALUES
  ('LOW',      'Faible'),
  ('MEDIUM',   'Moyenne'),
  ('HIGH',     'Elevée'),
  ('CRITICAL', 'Critique');

CREATE TABLE IF NOT EXISTS `urgencies` (
  `id`    int          NOT NULL AUTO_INCREMENT,
  `code`  varchar(50)  NOT NULL,
  `label` varchar(120) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT IGNORE INTO `urgencies` (`code`, `label`) VALUES
  ('IMMEDIATE', 'Immédiate'),
  ('URGENT',    'Urgente'),
  ('NORMAL',    'Normale');

CREATE TABLE IF NOT EXISTS `codification_rules` (
  `id`               int          NOT NULL AUTO_INCREMENT,
  `term`             varchar(150) NOT NULL,
  `replacement_code` varchar(80)  NOT NULL,
  `is_active`        tinyint(1)   NOT NULL DEFAULT '1',
  `created_at`       timestamp    NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT IGNORE INTO `codification_rules` (`term`, `replacement_code`) VALUES
  ('AFC/M23',   'GA001'),
  ('Wazalendo', 'GA002'),
  ('FARDC',     'GA003'),
  ('FDLR',      'GA004');

-- ============================================================
-- 2. USERS (dépend de organizations + roles)
-- ============================================================

CREATE TABLE IF NOT EXISTS `users` (
  `id`                     int           NOT NULL AUTO_INCREMENT,
  `organization_id`        int           DEFAULT NULL,
  `role_id`                int           NOT NULL,
  `full_name`              varchar(150)  NOT NULL,
  `email`                  varchar(180)  NOT NULL,
  `password_hash`          varchar(255)  NOT NULL,
  `is_active`              tinyint(1)    NOT NULL DEFAULT '1',
  `created_at`             timestamp     NULL DEFAULT CURRENT_TIMESTAMP,
  `phone`                  varchar(60)   DEFAULT NULL,
  `job_title`              varchar(120)  DEFAULT NULL,
  `organization_name`      varchar(180)  DEFAULT NULL,
  `bio`                    text,
  `avatar_path`            varchar(255)  DEFAULT NULL,
  `statut`                 enum('Actif','Bloque') NOT NULL DEFAULT 'Actif',
  `must_change_password`   tinyint(1)    NOT NULL DEFAULT '1',
  `last_login_at`          datetime      DEFAULT NULL,
  `logo_path`              varchar(255)  DEFAULT NULL,
  `telephone_organisation` varchar(80)   DEFAULT NULL,
  `site_web`               varchar(255)  DEFAULT NULL,
  `bio_organisation`       text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `organization_id` (`organization_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `users_ibfk_2` FOREIGN KEY (`role_id`)         REFERENCES `roles`         (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================
-- 3. TABLE PRINCIPALE : reports
-- ============================================================

CREATE TABLE IF NOT EXISTS `reports` (
  `id`                   bigint         NOT NULL AUTO_INCREMENT,
  `reference_code`       varchar(40)    NOT NULL,
  `organization_id`      int            NOT NULL,
  `reporter_user_id`     int            NOT NULL,
  `report_type`          enum('FLASH','NOTE') NOT NULL,
  `status_id`            int            NOT NULL,
  `incident_type_id`     int            DEFAULT NULL,
  `severity_id`          int            DEFAULT NULL,
  `urgency_id`           int            DEFAULT NULL,
  -- Localisation
  `province`             varchar(120)   DEFAULT NULL,
  `territory`            varchar(120)   DEFAULT NULL,
  `groupement`           varchar(120)   DEFAULT NULL,
  `locality`             varchar(160)   DEFAULT NULL,
  `place_search_text`    varchar(255)   DEFAULT NULL,
  `latitude`             decimal(10,7)  DEFAULT NULL,
  `longitude`            decimal(10,7)  DEFAULT NULL,
  `health_zone`          varchar(140)   DEFAULT NULL,
  `village`              varchar(140)   DEFAULT NULL,
  `gps_lat`              decimal(10,7)  DEFAULT NULL,
  `gps_lng`              decimal(10,7)  DEFAULT NULL,
  -- Population affectée
  `households_count`     int            DEFAULT NULL,
  `people_count`         int            DEFAULT NULL,
  `displaced_households` int            DEFAULT NULL,
  `victims_count`        int            DEFAULT NULL,
  `vulnerable_categories` varchar(255)  DEFAULT NULL,
  -- Contenu textuel
  `incident_label`       varchar(190)   DEFAULT NULL,
  `incident_type`        varchar(160)   DEFAULT NULL,
  `context_text`         text,
  `facts_text`           text,
  `analysis_text`        text,
  `impacts_text`         text,
  `needs_text`           text,
  `priority_needs_text`  text,
  `recommendations_text` text,
  `additional_notes`     text,
  -- Workflow
  `urgency_level`        varchar(20)    NOT NULL DEFAULT 'Moyenne',
  `workflow_status`      varchar(40)    NOT NULL DEFAULT 'Brouillon',
  `is_validated`         tinyint(1)     NOT NULL DEFAULT '0',
  `validated_by`         int            DEFAULT NULL,
  `validated_at`         datetime       DEFAULT NULL,
  -- Timestamps
  `created_at`           timestamp      NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           timestamp      NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `submitted_at`         timestamp      NULL DEFAULT NULL,
  `reviewed_at`          datetime       DEFAULT NULL,
  `published_at`         datetime       DEFAULT NULL,
  `diffused_at`          datetime       DEFAULT NULL,
  `rejected_at`          datetime       DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_code` (`reference_code`),
  KEY `organization_id`  (`organization_id`),
  KEY `reporter_user_id` (`reporter_user_id`),
  KEY `status_id`        (`status_id`),
  KEY `incident_type_id` (`incident_type_id`),
  KEY `severity_id`      (`severity_id`),
  KEY `urgency_id`       (`urgency_id`),
  CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`organization_id`)  REFERENCES `organizations`  (`id`),
  CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`reporter_user_id`) REFERENCES `users`           (`id`),
  CONSTRAINT `reports_ibfk_3` FOREIGN KEY (`status_id`)        REFERENCES `report_statuses` (`id`),
  CONSTRAINT `reports_ibfk_4` FOREIGN KEY (`incident_type_id`) REFERENCES `incident_types`  (`id`),
  CONSTRAINT `reports_ibfk_5` FOREIGN KEY (`severity_id`)      REFERENCES `severity_levels` (`id`),
  CONSTRAINT `reports_ibfk_6` FOREIGN KEY (`urgency_id`)       REFERENCES `urgencies`       (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================
-- 4. TABLES DÉPENDANTES DE reports + users
-- ============================================================

CREATE TABLE IF NOT EXISTS `report_status_history` (
  `id`           bigint       NOT NULL AUTO_INCREMENT,
  `report_id`    bigint       NOT NULL,
  `status_label` varchar(60)  NOT NULL,
  `event_note`   varchar(255) DEFAULT NULL,
  `changed_by`   int          DEFAULT NULL,
  `created_at`   timestamp    NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rsh_report`  (`report_id`),
  KEY `idx_rsh_created` (`created_at`),
  KEY `fk_rsh_user`     (`changed_by`),
  CONSTRAINT `fk_rsh_report` FOREIGN KEY (`report_id`)  REFERENCES `reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rsh_user`   FOREIGN KEY (`changed_by`) REFERENCES `users`   (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `report_attachments` (
  `id`            bigint       NOT NULL AUTO_INCREMENT,
  `report_id`     bigint       NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `storage_path`  varchar(255) NOT NULL,
  `mime_type`     varchar(120) DEFAULT NULL,
  `file_size`     bigint       DEFAULT NULL,
  `created_at`    timestamp    NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ra_report` (`report_id`),
  CONSTRAINT `fk_ra_report` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `report_comments` (
  `id`           bigint     NOT NULL AUTO_INCREMENT,
  `report_id`    bigint     NOT NULL,
  `user_id`      int        NOT NULL,
  `comment_type` enum('CLARIFICATION','CORRECTION','VALIDATION') NOT NULL,
  `body`         text       NOT NULL,
  `created_at`   timestamp  NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `report_id` (`report_id`),
  KEY `user_id`   (`user_id`),
  CONSTRAINT `report_comments_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `report_comments_ibfk_2` FOREIGN KEY (`user_id`)   REFERENCES `users`   (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `report_exports` (
  `id`            bigint      NOT NULL AUTO_INCREMENT,
  `report_id`     bigint      NOT NULL,
  `exported_by`   int         NOT NULL,
  `export_format` enum('PDF','XLSX','CSV') NOT NULL,
  `file_path`     varchar(255) NOT NULL,
  `created_at`    timestamp   NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `report_id`   (`report_id`),
  KEY `exported_by` (`exported_by`),
  CONSTRAINT `report_exports_ibfk_1` FOREIGN KEY (`report_id`)   REFERENCES `reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `report_exports_ibfk_2` FOREIGN KEY (`exported_by`) REFERENCES `users`   (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `codification_logs` (
  `id`               bigint NOT NULL AUTO_INCREMENT,
  `report_id`        bigint NOT NULL,
  `field_name`       varchar(80) NOT NULL,
  `original_excerpt` text NOT NULL,
  `coded_excerpt`    text NOT NULL,
  `created_at`       timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `report_id` (`report_id`),
  CONSTRAINT `codification_logs_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `email_logs` (
  `id`              bigint       NOT NULL AUTO_INCREMENT,
  `report_id`       bigint       DEFAULT NULL,
  `sender_user_id`  int          NOT NULL,
  `recipient_email` varchar(180) NOT NULL,
  `subject_line`    varchar(255) NOT NULL,
  `status`          varchar(40)  NOT NULL,
  `created_at`      timestamp    NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `report_id`      (`report_id`),
  KEY `sender_user_id` (`sender_user_id`),
  CONSTRAINT `email_logs_ibfk_1` FOREIGN KEY (`report_id`)      REFERENCES `reports` (`id`) ON DELETE SET NULL,
  CONSTRAINT `email_logs_ibfk_2` FOREIGN KEY (`sender_user_id`) REFERENCES `users`   (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id`           bigint       NOT NULL AUTO_INCREMENT,
  `user_id`      int          NOT NULL,
  `report_id`    bigint       NOT NULL,
  `status_code`  varchar(50)  NOT NULL,
  `title`        varchar(160) NOT NULL,
  `message`      text         NOT NULL,
  `is_read`      tinyint(1)   NOT NULL DEFAULT '0',
  `mail_sent_at` datetime     DEFAULT NULL,
  `created_at`   timestamp    NULL DEFAULT CURRENT_TIMESTAMP,
  `target_url`   varchar(255) DEFAULT NULL,
  `read_at`      datetime     DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_notification_status` (`user_id`,`report_id`,`status_code`),
  KEY `idx_notifications_user_read`  (`user_id`,`is_read`),
  KEY `idx_notifications_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id`          bigint       NOT NULL AUTO_INCREMENT,
  `user_id`     int          DEFAULT NULL,
  `action_type` varchar(100) NOT NULL,
  `entity_type` varchar(80)  NOT NULL,
  `entity_id`   varchar(80)  DEFAULT NULL,
  `details`     text,
  `created_at`  timestamp    NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================
-- 5. SÉCURITÉ / AUTHENTIFICATION
-- ============================================================

CREATE TABLE IF NOT EXISTS `password_reset_requests` (
  `id`         int          NOT NULL AUTO_INCREMENT,
  `user_id`    int          NOT NULL,
  `email`      varchar(190) NOT NULL,
  `token_hash` char(64)     NOT NULL,
  `expires_at` datetime     NOT NULL,
  `used_at`    datetime     DEFAULT NULL,
  `created_at` timestamp    NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reset_token_hash` (`token_hash`),
  KEY `idx_reset_user_id`    (`user_id`),
  CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `email_change_requests` (
  `id`           bigint       NOT NULL AUTO_INCREMENT,
  `user_id`      int          NOT NULL,
  `requested_by` int          DEFAULT NULL,
  `old_email`    varchar(190) NOT NULL,
  `new_email`    varchar(190) NOT NULL,
  `token_hash`   char(64)     NOT NULL,
  `expires_at`   datetime     NOT NULL,
  `used_at`      datetime     DEFAULT NULL,
  `created_at`   timestamp    NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email_change_token` (`token_hash`),
  KEY `idx_email_change_user`  (`user_id`),
  CONSTRAINT `fk_email_change_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `account_invitations` (
  `id`         bigint       NOT NULL AUTO_INCREMENT,
  `user_id`    int          NOT NULL,
  `email`      varchar(190) NOT NULL,
  `token_hash` char(64)     NOT NULL,
  `expires_at` datetime     NOT NULL,
  `used_at`    datetime     DEFAULT NULL,
  `created_at` timestamp    NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_account_invite_user`    (`user_id`),
  KEY `idx_account_invite_token`   (`token_hash`),
  KEY `idx_account_invite_expires` (`expires_at`),
  CONSTRAINT `fk_account_invite_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;
-- ============================================================
-- NOTE : Ce fichier représente la structure complète de la BD.
-- Les données réelles (users, rapports) ne sont pas incluses.
-- Scripts de données de test : database/seed_caritas.php
-- ============================================================
