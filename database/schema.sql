CREATE DATABASE IF NOT EXISTS sydra CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sydra;

-- Tables physiques en francais
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    libelle VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS organisations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(180) NOT NULL,
    email VARCHAR(180) DEFAULT NULL,
    email_contact VARCHAR(180) DEFAULT NULL,
    site_web VARCHAR(220) DEFAULT NULL,
    logo_url VARCHAR(255) DEFAULT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organisation_id INT DEFAULT NULL,
    role_id INT NOT NULL,
    nom_complet VARCHAR(150) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organisation_id) REFERENCES organisations(id),
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE IF NOT EXISTS statuts_rapport (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    libelle VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS types_incident (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    libelle VARCHAR(120) NOT NULL
);

CREATE TABLE IF NOT EXISTS niveaux_gravite (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    libelle VARCHAR(120) NOT NULL
);

CREATE TABLE IF NOT EXISTS niveaux_urgence (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    libelle VARCHAR(120) NOT NULL
);

CREATE TABLE IF NOT EXISTS rapports (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    code_reference VARCHAR(40) NOT NULL UNIQUE,
    organisation_id INT NOT NULL,
    rapporteur_id INT NOT NULL,
    type_rapport ENUM('FLASH', 'NOTE') NOT NULL,
    statut_id INT NOT NULL,
    type_incident_id INT DEFAULT NULL,
    niveau_gravite_id INT DEFAULT NULL,
    niveau_urgence_id INT DEFAULT NULL,
    province VARCHAR(120) DEFAULT NULL,
    territoire VARCHAR(120) DEFAULT NULL,
    zone_sante VARCHAR(120) DEFAULT NULL,
    groupement VARCHAR(120) DEFAULT NULL,
    village VARCHAR(160) DEFAULT NULL,
    localite VARCHAR(160) DEFAULT NULL,
    lieu_recherche VARCHAR(255) DEFAULT NULL,
    latitude DECIMAL(10,7) DEFAULT NULL,
    longitude DECIMAL(10,7) DEFAULT NULL,
    nb_menages_deplaces INT DEFAULT NULL,
    nb_victimes INT DEFAULT NULL,
    nb_enfants INT DEFAULT NULL,
    nb_personnes_agees INT DEFAULT NULL,
    nb_femmes INT DEFAULT NULL,
    nb_hommes INT DEFAULT NULL,
    nb_handicap INT DEFAULT NULL,
    nb_autres_vulnerables INT DEFAULT NULL,
    categories_vulnerables VARCHAR(255) DEFAULT NULL,
    texte_contexte TEXT,
    texte_faits TEXT,
    texte_analyse TEXT,
    texte_impacts TEXT,
    texte_besoins TEXT,
    texte_recommandations TEXT,
    cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    maj_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    soumis_le TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (organisation_id) REFERENCES organisations(id),
    FOREIGN KEY (rapporteur_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (statut_id) REFERENCES statuts_rapport(id),
    FOREIGN KEY (type_incident_id) REFERENCES types_incident(id),
    FOREIGN KEY (niveau_gravite_id) REFERENCES niveaux_gravite(id),
    FOREIGN KEY (niveau_urgence_id) REFERENCES niveaux_urgence(id)
);

CREATE TABLE IF NOT EXISTS commentaires_rapport (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    rapport_id BIGINT NOT NULL,
    utilisateur_id INT NOT NULL,
    type_commentaire ENUM('CLARIFICATION', 'CORRECTION', 'VALIDATION') NOT NULL,
    contenu TEXT NOT NULL,
    cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rapport_id) REFERENCES rapports(id) ON DELETE CASCADE,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
);

CREATE TABLE IF NOT EXISTS regles_codification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mot_sensible VARCHAR(150) NOT NULL,
    code_remplacement VARCHAR(80) NOT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS journaux_codification (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    rapport_id BIGINT NOT NULL,
    champ VARCHAR(80) NOT NULL,
    extrait_original TEXT NOT NULL,
    extrait_code TEXT NOT NULL,
    cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rapport_id) REFERENCES rapports(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS exportations_rapport (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    rapport_id BIGINT NOT NULL,
    exporte_par INT NOT NULL,
    format_export ENUM('PDF', 'XLSX', 'CSV') NOT NULL,
    chemin_fichier VARCHAR(255) NOT NULL,
    cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rapport_id) REFERENCES rapports(id) ON DELETE CASCADE,
    FOREIGN KEY (exporte_par) REFERENCES utilisateurs(id)
);

CREATE TABLE IF NOT EXISTS journaux_email (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    rapport_id BIGINT DEFAULT NULL,
    expediteur_id INT NOT NULL,
    destinataire_email VARCHAR(180) NOT NULL,
    objet VARCHAR(255) NOT NULL,
    statut VARCHAR(40) NOT NULL,
    cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rapport_id) REFERENCES rapports(id) ON DELETE SET NULL,
    FOREIGN KEY (expediteur_id) REFERENCES utilisateurs(id)
);

CREATE TABLE IF NOT EXISTS journaux_audit (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT DEFAULT NULL,
    type_action VARCHAR(100) NOT NULL,
    type_entite VARCHAR(80) NOT NULL,
    id_entite VARCHAR(80) DEFAULT NULL,
    details TEXT,
    cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
);

-- Vues de compatibilite (anglais) pour conserver le code applicatif existant
DROP VIEW IF EXISTS organizations;
CREATE VIEW organizations AS
SELECT id,
       nom AS name,
       email,
       email_contact AS contact_email,
       site_web AS website,
       logo_url,
       actif AS is_active,
       cree_le AS created_at
FROM organisations;

DROP VIEW IF EXISTS users;
CREATE VIEW users AS
SELECT id, organisation_id AS organization_id, role_id, nom_complet AS full_name,
       email, mot_de_passe AS password_hash, actif AS is_active, cree_le AS created_at
FROM utilisateurs;

DROP VIEW IF EXISTS report_statuses;
CREATE VIEW report_statuses AS
SELECT id, code, libelle AS label
FROM statuts_rapport;

DROP VIEW IF EXISTS incident_types;
CREATE VIEW incident_types AS
SELECT id, code, libelle AS label
FROM types_incident;

DROP VIEW IF EXISTS severity_levels;
CREATE VIEW severity_levels AS
SELECT id, code, libelle AS label
FROM niveaux_gravite;

DROP VIEW IF EXISTS urgencies;
CREATE VIEW urgencies AS
SELECT id, code, libelle AS label
FROM niveaux_urgence;

DROP VIEW IF EXISTS reports;
CREATE VIEW reports AS
SELECT id,
       code_reference AS reference_code,
       organisation_id AS organization_id,
       rapporteur_id AS reporter_user_id,
       type_rapport AS report_type,
       statut_id AS status_id,
       type_incident_id AS incident_type_id,
       niveau_gravite_id AS severity_id,
       niveau_urgence_id AS urgency_id,
       province,
       territoire AS territory,
       zone_sante AS health_zone,
       groupement,
       village,
       localite AS locality,
       lieu_recherche AS place_search_text,
       latitude,
       longitude,
       nb_menages_deplaces AS households_count,
       nb_victimes AS people_count,
    nb_enfants AS vulnerable_children_count,
    nb_personnes_agees AS vulnerable_elderly_count,
    nb_femmes AS vulnerable_women_count,
    nb_hommes AS vulnerable_men_count,
    nb_handicap AS vulnerable_disability_count,
    nb_autres_vulnerables AS vulnerable_other_count,
       categories_vulnerables AS vulnerable_categories,
       texte_contexte AS context_text,
       texte_faits AS facts_text,
       texte_analyse AS analysis_text,
       texte_impacts AS impacts_text,
       texte_besoins AS needs_text,
       texte_recommandations AS recommendations_text,
       cree_le AS created_at,
       maj_le AS updated_at,
       soumis_le AS submitted_at
FROM rapports;

DROP VIEW IF EXISTS report_comments;
CREATE VIEW report_comments AS
SELECT id, rapport_id AS report_id, utilisateur_id AS user_id,
       type_commentaire AS comment_type, contenu AS body, cree_le AS created_at
FROM commentaires_rapport;

DROP VIEW IF EXISTS codification_rules;
CREATE VIEW codification_rules AS
SELECT id, mot_sensible AS term, code_remplacement AS replacement_code,
       actif AS is_active, cree_le AS created_at
FROM regles_codification;

DROP VIEW IF EXISTS codification_logs;
CREATE VIEW codification_logs AS
SELECT id, rapport_id AS report_id, champ AS field_name,
       extrait_original AS original_excerpt, extrait_code AS coded_excerpt,
       cree_le AS created_at
FROM journaux_codification;

DROP VIEW IF EXISTS report_exports;
CREATE VIEW report_exports AS
SELECT id, rapport_id AS report_id, exporte_par AS exported_by,
       format_export AS export_format, chemin_fichier AS file_path,
       cree_le AS created_at
FROM exportations_rapport;

DROP VIEW IF EXISTS email_logs;
CREATE VIEW email_logs AS
SELECT id, rapport_id AS report_id, expediteur_id AS sender_user_id,
       destinataire_email AS recipient_email, objet AS subject_line,
       statut, cree_le AS created_at
FROM journaux_email;

DROP VIEW IF EXISTS audit_logs;
CREATE VIEW audit_logs AS
SELECT id, utilisateur_id AS user_id, type_action AS action_type,
       type_entite AS entity_type, id_entite AS entity_id,
       details, cree_le AS created_at
FROM journaux_audit;

-- Donnees initiales
INSERT INTO roles (code, libelle) VALUES
('ADMIN', 'Administrateur'),
('GTMP_LEAD', 'Lead GTMP'),
('GTMP_COLEAD', 'Co-Lead GTMP'),
('ORG_REPORTER', 'Organisation Rapportante'),
('CLUSTER_PROTECTION', 'Cluster Protection')
ON DUPLICATE KEY UPDATE libelle = VALUES(libelle);

INSERT INTO organisations (nom, email, email_contact, site_web, logo_url) VALUES
('Organisation Demo', 'org.demo@example.org', 'contact@orgdemo.org', 'https://orgdemo.org', NULL)
ON DUPLICATE KEY UPDATE
    email = VALUES(email),
    email_contact = VALUES(email_contact),
    site_web = VALUES(site_web);

INSERT INTO statuts_rapport (code, libelle) VALUES
('DRAFT', 'Brouillon'),
('SUBMITTED', 'Soumis'),
('UNDER_REVIEW', 'En revision'),
('VALIDATED', 'Valide'),
('PUBLISHED', 'Publie'),
('REJECTED', 'Rejete')
ON DUPLICATE KEY UPDATE libelle = VALUES(libelle);

INSERT INTO types_incident (code, libelle) VALUES
('SECURITY', 'Alerte securitaire'),
('DISPLACEMENT', 'Deplacement de population'),
('VIOLATION', 'Violation des droits humains'),
('NATURAL_DISASTER', 'Catastrophe naturelle')
ON DUPLICATE KEY UPDATE libelle = VALUES(libelle);

INSERT INTO niveaux_gravite (code, libelle) VALUES
('LOW', 'Faible'),
('MEDIUM', 'Moyenne'),
('HIGH', 'Elevee'),
('CRITICAL', 'Critique')
ON DUPLICATE KEY UPDATE libelle = VALUES(libelle);

INSERT INTO niveaux_urgence (code, libelle) VALUES
('IMMEDIATE', 'Immediat'),
('URGENT', 'Urgent'),
('NORMAL', 'Normal')
ON DUPLICATE KEY UPDATE libelle = VALUES(libelle);

INSERT INTO utilisateurs (organisation_id, role_id, nom_complet, email, mot_de_passe)
SELECT o.id, r.id, 'Compte Demo Organisation', 'reporter@sydra.local',
'$2y$10$L/APfRt7.hJsd2cdTyYqsuYS3aFdmd96voLq1zRnVM3KbyvsyyiRi'
FROM organisations o, roles r
WHERE o.nom = 'Organisation Demo' AND r.code = 'ORG_REPORTER'
AND NOT EXISTS (SELECT 1 FROM utilisateurs WHERE email = 'reporter@sydra.local');

INSERT INTO utilisateurs (organisation_id, role_id, nom_complet, email, mot_de_passe)
SELECT o.id, r.id, 'Compte Admin SyDRA', 'admin@sydra.local',
'$2y$10$L/APfRt7.hJsd2cdTyYqsuYS3aFdmd96voLq1zRnVM3KbyvsyyiRi'
FROM organisations o, roles r
WHERE o.nom = 'Organisation Demo' AND r.code = 'ADMIN'
AND NOT EXISTS (SELECT 1 FROM utilisateurs WHERE email = 'admin@sydra.local');

INSERT INTO utilisateurs (organisation_id, role_id, nom_complet, email, mot_de_passe)
SELECT o.id, r.id, 'Compte Lead GTMP', 'lead.gtmp@sydra.local',
'$2y$10$L/APfRt7.hJsd2cdTyYqsuYS3aFdmd96voLq1zRnVM3KbyvsyyiRi'
FROM organisations o, roles r
WHERE o.nom = 'Organisation Demo' AND r.code = 'GTMP_LEAD'
AND NOT EXISTS (SELECT 1 FROM utilisateurs WHERE email = 'lead.gtmp@sydra.local');

INSERT INTO utilisateurs (organisation_id, role_id, nom_complet, email, mot_de_passe)
SELECT o.id, r.id, 'Compte Co-Lead GTMP', 'colead.gtmp@sydra.local',
'$2y$10$L/APfRt7.hJsd2cdTyYqsuYS3aFdmd96voLq1zRnVM3KbyvsyyiRi'
FROM organisations o, roles r
WHERE o.nom = 'Organisation Demo' AND r.code = 'GTMP_COLEAD'
AND NOT EXISTS (SELECT 1 FROM utilisateurs WHERE email = 'colead.gtmp@sydra.local');

INSERT INTO utilisateurs (organisation_id, role_id, nom_complet, email, mot_de_passe)
SELECT o.id, r.id, 'Compte Cluster Protection', 'cluster@sydra.local',
'$2y$10$L/APfRt7.hJsd2cdTyYqsuYS3aFdmd96voLq1zRnVM3KbyvsyyiRi'
FROM organisations o, roles r
WHERE o.nom = 'Organisation Demo' AND r.code = 'CLUSTER_PROTECTION'
AND NOT EXISTS (SELECT 1 FROM utilisateurs WHERE email = 'cluster@sydra.local');
