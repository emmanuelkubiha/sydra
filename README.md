# SyDRA - Application web de rapportage et alerte

SyDRA (Systeme de Documentation, de Rapportage et d'Alerte) est une application PHP/MySQL simple, sans MVC lourd, utilisee pour la saisie, le suivi et la coordination d'informations terrain.

## 1) Ce que fait l'application

- Authentification des utilisateurs (admin, lead, co-lead, reporter)
- Tableau de bord connecte
- Creation et listing de rapports (FLASH/NOTE)
- Hub Rapportage moderne (accueil, KPI, cartographie operationnelle)
- Wizard manuel en 4 etapes (localisation, faits, analyse, pieces jointes)
- API AJAX de sauvegarde rapport (`api/save_report.php`) avec codification de termes sensibles
- Profil utilisateur avec photo/avatar
- Invitation utilisateur par email avec activation (48h)
- Mot de passe oublie et reinitialisation securisee
- Messages flash affiches en toasts AJAX

## 2) Structure de l'interface (header, footer, pages)

- Header global: `pages/en_tete.php`
  - charge CSS + bootstrap-icons
  - affiche logo, nom applicatif, switch langue FR/EN
  - affiche menu selon session/role
  - affiche loader global + bandeau public en mode non connecte
- Footer global: `pages/pied_de_page.php`
  - ferme le layout principal
  - charge `assets/js/app.js`
- Pages principales:
  - `pages/login.php`
  - `pages/forgot_password.php`
  - `pages/reset_password.php`
  - `pages/dashboard.php`
  - `pages/reports_create.php`
  - `pages/reports_list.php`
  - `pages/profile.php`
  - `pages/users.php`
  - `pages/rapportage/index.php`
  - `pages/rapportage/creer_wizard.php`
  - `pages/rapportage/creer_ia.php`

## 3) Arborescence du projet

```text
SyDRA/
├── api/
│   ├── save_report.php          # Sauvegarde AJAX Wizard + upload pieces jointes
│   ├── get_dashboard_filtered.php # Filtres hub (KPI + carte + séries)
│   ├── change_status.php        # Boucle de décision Lead/Admin (statut + historique + notif + email)
│   └── mark_notification_read.php # Marquage notification lue (AJAX)
├── actions/
│   └── create_user.php           # Endpoint creation compte utilisateur
├── index.php                     # Routeur frontal + logique metier
├── config/
│   ├── config.php                # Chargement .env/.env. + config app/db/mail
│   └── mail.php                  # Envoi SMTP (PHPMailer) + diagnostics
├── database/
│   └── schema.sql                # Schema SQL de base
├── pages/
│   ├── en_tete.php               # Header global
│   ├── pied_de_page.php          # Footer global
│   ├── partials/
│   │   └── report_header.php     # En-tete branding rapports (logo bleu)
│   ├── reports/
│   │   └── alerte_details.php    # Detail rapport/alerte
│   ├── rapportage/
│   │   ├── index.php             # Hub de rapportage (hero + KPI + map Leaflet)
│   │   ├── creer_wizard.php      # Formulaire manuel BS-Stepper + Dropzone
│   │   └── creer_ia.php          # Page d'attente Assistant IA
│   ├── login.php
│   ├── forgot_password.php
│   ├── reset_password.php
│   ├── dashboard.php
│   ├── reports_create.php
│   ├── reports_list.php
│   ├── profile.php
│   └── users.php
├── assets/
│   ├── css/style.css             # Styles globaux + toasts + layout
│   └── js/app.js                 # Loader, toasts, interactions UI
│   └── img/sydra-logo/           # Logos officiels utilises en production
├── Annexes/
│   └── assets-source/SyDRA-Logo/ # Sources graphiques archivees
├── uploads/
│   ├── avatars/
│   ├── reports/
│   │   └── attachments/
│   └── organizations/logos/
├── .env.example
├── README.md
├── composer.json
└── vendor/                       # Dependances Composer (local)
```

## 4) Architecture technique

- Point d'entree unique: `index.php`
- Routage par query string: `?page=...`
- Actions POST centralisees dans `index.php` via `action`
- Vues rendues par inclusion des fichiers de `pages/`
- Session PHP pour auth + CSRF + flashes
- PDO pour toutes les operations SQL sensibles

### 4.1 Role des API (dossier api/)

- `api/save_report.php`
  - Rôle: enregistre un rapport depuis le Wizard (brouillon ou soumis), pièces jointes incluses.
  - Entrées: localisation, incident, analyse, `status_action`, `csrf`.
  - Sortie: JSON avec `report_id`, `status`, message.

- `api/get_dashboard_filtered.php`
  - Rôle: alimente le Hub Rapportage en mode AJAX.
  - Entrées: `date_debut`, `date_fin`, `organisation_id`.
  - Sortie: JSON avec `stats`, `markers`, `charts`.

- `api/change_status.php`
  - Rôle: traite les décisions Lead/Admin (`VALIDATE`, `REJECT`, `REQUEST_INFO`).
  - Effets: met à jour `reports`, écrit `report_status_history`, crée notification, envoie email.
  - Sortie: JSON de confirmation.

- `api/mark_notification_read.php`
  - Rôle: marque une notification comme lue lors du clic dans le dropdown header.
  - Entrées: `notification_id`, `csrf`.
  - Sortie: JSON `{ ok: true }`.

## 5) Configuration environnement

Le systeme charge `.env`, puis `.env.`.

Variables importantes:

- App: `APP_NAME`, `APP_URL`, `APP_ENV`, `APP_DEBUG`
- Base: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
- Mail: `MAIL_FROM`, `MAIL_FROM_NAME`, `SUPPORT_EMAIL`
- SMTP: `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`, `SMTP_SECURE`, `SMTP_AUTH`

Comportement production:

- `APP_ENV=production` desactive le provisionnement automatique des comptes demo.
- Cette mesure evite toute reecriture non voulue des mots de passe en production.

## 6) Base de donnees: tables et schema

### 6.1 Tables principales

- `users`
  - compte utilisateur, hash mot de passe, role/etat, infos profil
- `reports`
  - rapports terrain lies aux utilisateurs (workflow, localisation detaillee, analyse)
- `report_status_history`
  - journal des changements de statut (brouillon, soumis, revision, valide, publie)
- `report_attachments`
  - pieces jointes des rapports (preuves photos/documents)
- `account_invitations`
  - invitations avec token hash, expiration, marqueur usage
- `password_reset_requests`
  - demandes de reset avec token hash, expiration, marqueur usage

Note compatibilite role:

- Certaines bases utilisent `users.role` (ENUM)
- D'autres utilisent `users.role_id` (FK vers table `roles`)
- L'application detecte automatiquement le mode via `role_storage_mode()`

### 6.2 Relations logiques

- `users (1) -> (N) reports`
- `reports (1) -> (N) report_status_history`
- `reports (1) -> (N) report_attachments`
- `users (1) -> (N) account_invitations`
- `users (1) -> (N) password_reset_requests`

### 6.3 DDL de reference

Le schema initial se trouve dans `database/schema.sql`.

En plus, l'application applique des migrations defensives au demarrage:

- creation de `password_reset_requests` si absente
- ajout de colonnes profil manquantes dans `users` (dont `avatar_path`)
- ajout automatique des colonnes workflow/wizard manquantes dans `reports`
- creation de `report_status_history` et `report_attachments` si absentes

## 11) Module Rapportage (v2)

### 11.1 Ecrans principaux

- `?page=rapportage`: hub d'accueil premium (hero + actions + KPI + map Leaflet)
- `?page=rapportage-creer-wizar`: Wizard manuel en 4 etapes
- `?page=rapportage-creer-AI`: page d'attente pour l'assistant IA
- `?page=rapportage-liste-user`: liste des rapports de l'organisation connectee
- `?page=rapportage-admin-list`: tour de controle Lead/Admin

### 11.2 Wizard manuel

- Etape 1: localisation + geolocalisation navigateur
- Etape 2: faits et bilan (gravite, victimes, deplacements, description)
- Etape 3: analyse et recommandations
- Etape 4: pieces jointes (Dropzone), brouillon ou soumission cluster

### 11.3 Codification de termes sensibles

Le backend applique une codification automatique avant insertion:

- `AFC/M23` -> `GA001`
- `Wazalendo` -> `GA002`

Cette codification est actuellement appliquee sur:

- `description`
- `analyse`
- `recommandations`

### 11.4 Cartographie operationnelle

La carte du hub est initialisee sur l'Est de la RDC avec:

- centre: `[-3.0, 27.5]`
- zoom initial: `7`
- zoom minimum: `6`
- limites de navigation (`maxBounds`): Nord `0.0`, Sud `-5.0`, Ouest `25.0`, Est `29.5`

### 11.5 Boucle de décision et notifications

- Les modals de la page détail alerte envoient désormais la décision vers `api/change_status.php`.
- Le backend applique la décision et notifie l'organisation:
  - notification in-app (`notifications`)
  - email transactionnel (`mail/demande_correction.php` ou `mail/alerte_validee.php`)
- Le centre de notifications du header lit les 5 dernières notifications et gère le marquage lu via `api/mark_notification_read.php`.

## 7) Processus de securite

### 7.1 Anti-injection SQL

- Requetes preparees PDO (`prepare/execute`)
- Aucune concatenation directe des entrées utilisateur dans les requetes critiques

### 7.2 Mots de passe

- Stockage hash uniquement: `password_hash(..., PASSWORD_BCRYPT)`
- Verification: `password_verify(...)`
- Pas de stockage en clair

### 7.3 CSRF

- Token CSRF en session
- Verification obligatoire pour POST sensibles

### 7.4 XSS

- Echappement HTML cote vue (`h(...)` / `htmlspecialchars`)

### 7.5 Tokens de securite

- Invitation et reset: token aleatoire fort
- Seul le hash SHA-256 du token est stocke en base
- Token invalide apres expiration ou utilisation

## 8) Processus connexion

1. L'utilisateur soumet email + mot de passe.
2. Le systeme charge l'utilisateur actif (`is_active = 1`).
3. Verification du hash avec `password_verify`.
4. Session auth creee (`auth_user_id`).
5. Redirection tableau de bord.

## 9) Processus mot de passe oublie et reinitialisation

### 9.1 Demande de reset

1. Verification email.
2. Generation token (32 octets aleatoires) + hash SHA-256.
3. Enregistrement dans `password_reset_requests` avec expiration (1h).
4. Envoi email SMTP avec lien `?page=reinitialiser_mot_de_passe&token=...`.

### 9.2 Validation reset

1. Verification token non utilise et non expire.
2. Validation de politique de mot de passe.
3. Ecriture du nouveau hash bcrypt dans `users.password_hash`.
4. Marquage `used_at` du token de reset.

### 9.3 Garantie de persistance du nouveau mot de passe

Le flux reset est execute dans une transaction SQL atomique:

- update `users.password_hash`
- update `password_reset_requests.used_at`
- commit unique, rollback en cas d'erreur

En production, le seed de comptes demo est desactive (`APP_ENV=production`), donc aucun composant ne peut reecraser ce nouveau mot de passe.

## 10) Processus email SMTP

`config/mail.php` centralise l'envoi via PHPMailer:

- verification de la configuration SMTP
- message d'erreur explicite si PHPMailer absent
- diagnostics host/port/auth/tls en cas d'echec

Parametres Gmail typiques:

- `SMTP_HOST=smtp.gmail.com`
- `SMTP_PORT=587`
- `SMTP_SECURE=tls`
- `SMTP_AUTH=true`
- `SMTP_USER=adresse@gmail.com`
- `SMTP_PASS=mot_de_passe_application`

### Gestion des Emails et Notifications Automatiques (Dossier `mail/`)

SyDRA centralise les emails transactionnels dans le dossier `mail/` avec une charte visuelle commune (template layout, couleurs institutionnelles, call-to-action).

Arborescence principale:

- `mail/layout.php`: layout HTML commun a tous les emails.
- `mail/creation_compte.php`: envoi des identifiants avec mot de passe genere.
- `mail/reinitialisation_mdp.php`: lien securise de reinitialisation de mot de passe.
- `mail/nouvelle_alerte_soumise.php`: notification de soumission d une nouvelle alerte.
- `mail/alerte_validee.php`: notification de validation/publication d une alerte.
- `mail/demande_correction.php`: demande de correction et complement d informations.

## 12) Mise en local (developpement)

### 12.1 Prerequis

- PHP 8.1+ (8.2/8.3 recommande)
- MySQL/MariaDB
- Composer
- Extension PDO MySQL active

### 12.2 Configuration locale rapide

1. Cloner le projet
2. Installer les dependances: `composer install`
3. Creer `.env` a partir de `.env.example`
4. Renseigner au minimum:
  - `APP_ENV=development`
  - `APP_DEBUG=true`
  - `APP_URL=http://localhost:8888/SyDRA`
  - `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
  - `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`, `MAIL_FROM`
5. Importer la base
6. Demarrer Apache/PHP + MySQL

## 13) Mise en production (sydra.fosip-drc.org)

### 13.1 Variables obligatoires en ligne

Configurer l environnement serveur (fichier `.env` ou variables systeme):

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://sydra.fosip-drc.org`
- Parametres DB de production
- Parametres SMTP de production (obligatoire pour invitations/reset/changements email)

Important:

- Ne pas exposer les comptes de test en production.
- Verifier les permissions ecriture sur `uploads/`.

## 14) Deploiement en Production (Local vers En Ligne)

### 14.1 Exporter la base locale et importer sur le serveur (phpMyAdmin/cPanel)

1. Sur la machine locale (MAMP), ouvrir phpMyAdmin.
2. Selectionner la base locale (ex: `sydra`).
3. Onglet Exporter -> format SQL -> methode Rapide (ou Personnalisee si besoin) -> Exporter.
4. Recuperer le fichier SQL (ex: `sydra.sql`).
5. Sur l hebergement (cPanel/phpMyAdmin distant), creer la base cible et l utilisateur MySQL de production.
6. Assigner l utilisateur a la base avec tous les privileges requis.
7. Ouvrir phpMyAdmin distant, selectionner la base cible puis onglet Importer.
8. Choisir le fichier SQL exporte localement, lancer l import.
9. Verifier la presence des tables principales (`users`, `reports`, `report_status_history`, `notifications`, etc.).

Alternative CLI (si SSH disponible):

- Export local: `mysqldump -h 127.0.0.1 -P 8889 -u root -p sydra > sydra.sql`
- Import distant: `mysql -h <DB_HOST_PROD> -u <DB_USER_PROD> -p <DB_NAME_PROD> < sydra.sql`

### 14.2 Variables exactes a modifier dans `.env`

Avant mise en ligne, adapter le fichier `.env` du serveur de production:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://votre-domaine`

Base de donnees de production:

- `DB_HOST=<host_mysql_serveur>`
- `DB_PORT=<port_mysql_serveur>`
- `DB_NAME=<nom_base_production>`
- `DB_USER=<utilisateur_mysql_production>`
- `DB_PASS=<mot_de_passe_mysql_production>`

SMTP de production (emails depuis le serveur en ligne):

- `MAIL_FROM=<adresse_expediteur>`
- `MAIL_FROM_NAME=SyDRA Notifications`
- `SMTP_HOST=<smtp_serveur_prod>`
- `SMTP_PORT=<port_smtp_prod>`
- `SMTP_AUTH=true`
- `SMTP_SECURE=tls` (ou `ssl` selon fournisseur)
- `SMTP_USER=<compte_smtp_prod>`
- `SMTP_PASS=<mot_de_passe_smtp_prod>`
- `SUPPORT_EMAIL=<adresse_support_prod>`

Controle recommande:

- Tester un reset de mot de passe en production.
- Tester une decision Lead (validation/rejet/demande info) pour confirmer l envoi d email.

### 14.3 Permissions de dossiers (ecriture)

Les dossiers de stockage doivent etre inscriptibles par l utilisateur web (Apache/Nginx/PHP-FPM):

- `uploads/`
- `uploads/avatars/`
- `uploads/reports/`
- `uploads/reports/attachments/`
- `uploads/organizations/logos/`

Exemple Linux:

- `chown -R www-data:www-data uploads`
- `find uploads -type d -exec chmod 775 {} \;`
- `find uploads -type f -exec chmod 664 {} \;`

### 14.4 Check-list rapide avant ouverture publique

1. `APP_ENV=production` et `APP_DEBUG=false` confirmes.
2. Connexion DB production validee.
3. SMTP production valide (emails sortants OK).
4. Dossiers `uploads/` inscriptibles.
5. Comptes de test non exposes.
6. Login, creation rapport, decision Lead et notifications verifies en conditions reelles.

### 13.2 Deploiement type

1. Sauvegarder la base actuelle (local)
2. Importer la base sur le serveur de production
3. Deployer le code applicatif
4. Executer `composer install --no-dev --optimize-autoloader`
5. Configurer `.env` prod
6. Tester les flux critiques (connexion, rapportage, decision, notifications, email)

## 14) Export de la base actuelle vers la production

Exemple local MAMP:

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysqldump \
  -h127.0.0.1 -P8889 -uroot -proot sydra > sydra_export.sql
```

Import sur serveur cible:

```bash
mysql -h<HOST_PROD> -P<PORT_PROD> -u<USER_PROD> -p <DB_PROD> < sydra_export.sql
```

Recommandations:

- Faire un backup de la base de production avant import.
- Tester d abord sur une base/staging de preproduction.

## 15) Flux de decision Lead/Admin (UX et statuts)

Lors d un clic sur une decision (`Valider et publier`, `Demander des informations`, `Rejeter`):

1. Un petit modal de confirmation stylise affiche:
  - l action choisie,
  - les implications metier,
  - le commentaire qui sera transmis.
2. Au submit, API `api/change_status.php` traite la decision.
3. Le retour utilisateur distingue:
  - succes serveur + succes email,
  - succes serveur + echec email,
  - echec serveur.

Interpretation:

- `succes serveur`: la decision est bien en base (statut/historique/notification in-app)
- `echec email`: la decision reste appliquee, mais l envoi mail a echoue
- `echec serveur`: aucune decision appliquee

## 16) Checklist go-live (minimum)

1. `APP_ENV=production`, `APP_DEBUG=false`
2. `APP_URL=https://sydra.fosip-drc.org`
3. SMTP valide et teste (invitation/reset/decision)
4. Suppression/masquage des identifiants de test dans l UI login
5. Permissions dossiers `uploads/`
6. Test fonctionnel complet:
  - connexion/logout
  - creation rapport + pieces jointes
  - decision lead (3 cas)
  - notifications + emails
  - exports PDF/Excel
- `mail/rappel_validation_lead.php`: rappel automatique des alertes non validees (>24h).
- `mail/alerte_urgente_critique.php`: diffusion prioritaire d urgence critique.

Fonction centrale d envoi:

- `envoyerNotificationEmail($type, $destinataire, $donnees)` dans `config/mail.php`.
- Cette fonction:
  - selectionne automatiquement le bon template selon `$type`.
  - injecte les donnees metier (`$donnees`) dans le template.
  - applique le layout commun.
  - envoie via PHPMailer en mode HTML.
  - ajoute automatiquement Lead GTMP + Admin en destinataires/CC pour les notifications administratives.

Types de mails, evenement declencheur et destinataires:

1. `creation_compte`
  - Declencheur: creation d un compte utilisateur/organisation avec mot de passe provisoire.
  - Destinataire: utilisateur/organisation concerne(e).

2. `reinitialisation_mdp`
  - Declencheur: action "Mot de passe oublie".
  - Destinataire: utilisateur ayant demande la reinitialisation.

3. `nouvelle_alerte_soumise`
  - Declencheur: soumission d un rapport Flash/Note par une organisation.
  - Destinataires: Lead GTMP et Admin (automatique, meme si un destinataire initial est fourni).

4. `alerte_validee`
  - Declencheur: validation d une alerte par le Cluster/Lead.
  - Destinataire: organisation rapportante (auteur de l alerte).

5. `demande_correction`
  - Declencheur: demande de complement/correction par le Lead.
  - Destinataire: organisation rapportante.

6. `rappel_validation_lead`
  - Declencheur: job de relance (alertes en attente > 24h).
  - Destinataires: Lead GTMP et Admin (automatique).

7. `alerte_urgente_critique`
  - Declencheur: soumission d une alerte avec niveau d urgence critique.
  - Destinataires: diffusion large et prioritaire, avec inclusion systematique de Lead GTMP + Admin.

## 11) Installation rapide

1. Configurer `.env.` depuis `.env.example`.
2. Importer `database/schema.sql`.
3. Lancer `composer install`.
4. Verifier permissions sur `uploads/`.
5. Tester: login, reset password, invitation, SMTP.

## 12) Verification technique

Commande utile:

- `php -l index.php`

Pour MAMP:

- `/Applications/MAMP/bin/php/php8.3.14/bin/php -l index.php`
