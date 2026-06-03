CREATE DATABASE IF NOT EXISTS sydra CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sydra;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('ADMIN','CLUSTER_LEADER','CLUSTER_CO_LEAD','REPORTER') NOT NULL DEFAULT 'REPORTER',
    avatar_path VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(60) DEFAULT NULL,
    job_title VARCHAR(120) DEFAULT NULL,
    organization_name VARCHAR(180) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS account_invitations (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(180) NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_invitation_email (email),
    INDEX idx_invitation_expiry (expires_at)
);

CREATE TABLE IF NOT EXISTS reports (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    report_type ENUM('FLASH','NOTE') NOT NULL DEFAULT 'FLASH',
    content TEXT NOT NULL,
    location_text VARCHAR(200) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_reports_user_created (user_id, created_at)
);

INSERT INTO users (full_name, email, password_hash, role, is_active)
SELECT 'Admin SyDRA', 'admin@sydra.local', '$2y$10$1SKa.6KrgQhA4btw8/7EquzwHTLHCbiHe.UgEX1grfHJLcmJJ3sCK', 'ADMIN', 1
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@sydra.local');
