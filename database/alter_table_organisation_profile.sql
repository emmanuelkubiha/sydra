-- Mission 1: Extension du profil organisation (table users)
-- Adaptez le nom de table si votre architecture utilise `organisations`.

ALTER TABLE users
    ADD COLUMN telephone_organisation VARCHAR(80) NULL,
    ADD COLUMN site_web VARCHAR(255) NULL,
    ADD COLUMN bio_organisation TEXT NULL;
