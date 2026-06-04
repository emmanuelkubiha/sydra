-- Mission 1: Mise a jour securite/logs de la table users
-- Executer sur la base cible avant mise en production si necessaire.

ALTER TABLE users
    ADD COLUMN statut ENUM('Actif', 'Bloque') NOT NULL DEFAULT 'Actif',
    ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN last_login_at DATETIME NULL;
