# SyDRA - Application web de Monitoring de Protection

SyDRA (Systeme de Documentation, de Rapportage et d'Alerte) est une application PHP/MySQL pour le monitoring humanitaire.

## Stack utilisee
- PHP orienté objet (architecture MVC)
- MySQL
- Bootstrap 5
- AdminLTE
- AJAX / Fetch API
- DataTables
- Chart.js
- Leaflet.js + heatmap
- PHPMailer (service prevu)
- TCPDF (service prevu)
- PhpSpreadsheet (service prevu)

## Pourquoi plusieurs dossiers et pas un seul index.php
Un seul `index.php` est vite difficile a maintenir. En MVC:
- `public/index.php` = routeur frontal (point d'entree unique)
- `app/Controllers` = logique des ecrans/API
- `app/Models` = acces donnees
- `app/Views` = interfaces
- `app/Helpers` = utilitaires (langue)
- `app/Services` = integrations (IA, PDF, Excel, mail)
- `config` = configuration
- `database` = schema SQL
- `public/assets` = JS/CSS/images
- `public/uploads` = fichiers exportes

Cette structure est professionnelle, evolutive, testable et plus sure.

## Rôles
- Administrateur
- Lead GTMP
- Co-Lead GTMP
- Organisation Rapportante
- Cluster Protection

## Comptes de test par défaut
Mot de passe pour tous: `password`
- `admin@sydra.local` -> Administrateur
- `lead.gtmp@sydra.local` -> Lead GTMP
- `colead.gtmp@sydra.local` -> Co-Lead GTMP
- `reporter@sydra.local` -> Organisation Rapportante
- `cluster@sydra.local` -> Cluster Protection

## Workflow
Brouillon -> Soumis -> En revision -> Valide -> Publie

## Fonctionnalites MVP deja en place
- Authentification securisee (session + CSRF)
- Gestion organisations (creer, activer, desactiver)
- Rapportage FLASH / NOTE
- Formulaire avec localisation et coordonnees GPS
- Auto-remplissage intelligent des champs localisation a partir de la recherche carte
- Reverse geocoding lors du clic/deplacement sur la carte
- Codification automatique des mots sensibles
- Dashboard ameliore (KPIs, graphique, bloc priorites, icones)
- Statistiques quantifiees des categories vulnerables (enfants, personnes agees, handicap)
- Cartographie Leaflet avec filtres territoire/gravite et heatmap
- Changement de mot de passe utilisateur avec exigences de securite
- Header + footer discrets
- Aide connexion via bouton discret "Besoin d'aide ?"
- Loader global avec messages tips dynamiques
- Parametres organisation etendus: logo, site web, email de contact
- Endpoints AJAX:
  - recherche lieu
  - IA assistive (base)
  - export PDF/Excel (base)
  - notification email (base)

## Installation (MAMP)
1. Mettre le projet dans:
   - `/Applications/MAMP/htdocs/SyDRA`
2. Copier l'environnement:
   - `cp .env.example .env`
3. Importer `database/schema.sql` dans MySQL (phpMyAdmin).
4. Si le compte demo existe deja et refuse la connexion, executer `database/fix_login.sql`.
5. IMPORTANT: dans MAMP, choisir **PHP 8.x** (pas 7.x), sinon erreur 500 possible.
6. Ouvrir:
   - `http://localhost:8888/SyDRA/public`

Important apres mise a jour du schema:
- Reimporter `database/schema.sql` pour obtenir les nouveaux champs (vulnerabilites, logo/site/contact).

## Compte demo
- Email: `reporter@sydra.local`
- Mot de passe: `password`

## Exigences mot de passe utilisateur
- Au moins 10 caracteres
- Au moins 1 majuscule
- Au moins 1 minuscule
- Au moins 1 chiffre
- Au moins 1 caractere special

## Rapportage: categories vulnerables quantifiees
- Enfants
- Personnes agees
- Femmes
- Hommes
- Personnes avec handicap
- Autres

Ces champs permettent de produire des statistiques rapides et plus fiables.

## Optionnel: installer les libs backend
Si `composer` est disponible:
- `composer install`

## Cause probable de ton erreur 500
- Apache lisait `.htaccess` avec directives non compatibles, ou
- MAMP etait en PHP 7.x alors que le projet cible PHP 8.

Les correctifs appliques:
- `.htaccess` tolerant
- compatibilite de certaines fonctions
- chargement `.env` plus robuste
- protection stricte des routes hors connexion
- page de chargement globale (loader)
- schema SQL avec champs physiques en francais (et vues de compatibilite)

## Ce qui manque encore (prochaine phase)
- Workflow complet par role (validation stricte et droits fins)
- Journal des connexions et reset mot de passe par email
- Ecrans d'administration avances (roles, permissions)
- Export PDF/Excel finalise avec templates professionnels
- Module IA guide avec questionnaire dynamique avant validation
- Tableau de bord adapte dynamiquement selon role connecte
- Gestion d'upload logo avec optimisation d'image et suppression

## Ce que tu peux fournir ensuite
- Charte graphique exacte (logo, polices, couleurs officielles)
- Regles metier precises de validation par role
- Regles de codification officielles initiales
- Parametres SMTP de production
