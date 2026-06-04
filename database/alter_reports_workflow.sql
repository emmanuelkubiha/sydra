-- Migration: alignement base de donnees avec le module rapportage
-- Compatible MySQL / MariaDB (version sans ADD COLUMN IF NOT EXISTS)
-- A executer une seule fois sur une base n'ayant pas encore ces colonnes.

USE sydra;

ALTER TABLE reports ADD COLUMN urgency_level VARCHAR(20) NOT NULL DEFAULT 'Moyenne';
ALTER TABLE reports ADD COLUMN workflow_status VARCHAR(40) NOT NULL DEFAULT 'Brouillon';
ALTER TABLE reports ADD COLUMN incident_label VARCHAR(190) DEFAULT NULL;
ALTER TABLE reports ADD COLUMN province VARCHAR(120) DEFAULT NULL;
ALTER TABLE reports ADD COLUMN territory VARCHAR(140) DEFAULT NULL;
ALTER TABLE reports ADD COLUMN health_zone VARCHAR(140) DEFAULT NULL;
ALTER TABLE reports ADD COLUMN groupement VARCHAR(140) DEFAULT NULL;
ALTER TABLE reports ADD COLUMN village VARCHAR(140) DEFAULT NULL;
ALTER TABLE reports ADD COLUMN incident_type VARCHAR(160) DEFAULT NULL;
ALTER TABLE reports ADD COLUMN gps_lat DECIMAL(10,7) DEFAULT NULL;
ALTER TABLE reports ADD COLUMN gps_lng DECIMAL(10,7) DEFAULT NULL;
ALTER TABLE reports ADD COLUMN victims_count INT DEFAULT NULL;
ALTER TABLE reports ADD COLUMN displaced_households INT DEFAULT NULL;
ALTER TABLE reports ADD COLUMN analysis_text TEXT DEFAULT NULL;
ALTER TABLE reports ADD COLUMN priority_needs_text TEXT DEFAULT NULL;
ALTER TABLE reports ADD COLUMN recommendations_text TEXT DEFAULT NULL;
ALTER TABLE reports ADD COLUMN additional_notes TEXT DEFAULT NULL;
ALTER TABLE reports ADD COLUMN is_validated TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE reports ADD COLUMN validated_by INT NULL;
ALTER TABLE reports ADD COLUMN submitted_at DATETIME NULL;
ALTER TABLE reports ADD COLUMN reviewed_at DATETIME NULL;
ALTER TABLE reports ADD COLUMN validated_at DATETIME NULL;
ALTER TABLE reports ADD COLUMN published_at DATETIME NULL;
ALTER TABLE reports ADD COLUMN rejected_at DATETIME NULL;
ALTER TABLE reports ADD COLUMN diffused_at DATETIME NULL;

CREATE TABLE IF NOT EXISTS report_status_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT NOT NULL,
    status_label VARCHAR(60) NOT NULL,
    event_note VARCHAR(255) DEFAULT NULL,
    changed_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rsh_report (report_id),
    INDEX idx_rsh_created (created_at),
    CONSTRAINT fk_rsh_report FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    CONSTRAINT fk_rsh_user FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS report_attachments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    storage_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) DEFAULT NULL,
    file_size BIGINT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ra_report (report_id),
    CONSTRAINT fk_ra_report FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
