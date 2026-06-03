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

## Roles utilisateurs (etat actuel)
Le systeme est pensé avec 4 roles principaux:
- `ADMIN` : administration globale, supervision systeme, configuration.
- `CLUSTER_LEADER` : pilotage, validation et coordination des alertes.
- `CLUSTER_CO_LEAD` : soutien du lead, revue et suivi operationnel.
- `REPORTER` : production terrain, creation et soumission des rapports.

Si tu veux une version encore plus minimale, `CLUSTER_LEADER` et `CLUSTER_CO_LEAD` peuvent etre fusionnes en un seul role `CLUSTER`.

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
- Accueil differencie par role (Admin / Lead-CoLead-Cluster / Rapporteur / Defaut)
- Carte decisionnelle lead sur l'accueil (marqueurs colores par gravite + popup incident)
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

## Securite appliquee dans le systeme
- Controle d'acces centralise via front-controller `public/index.php` (routes privees bloquees hors session).
- Session utilisateur obligatoire sur tous les modules metier via `Auth::requireLogin()`.
- Protection CSRF sur formulaires critiques (connexion, profil, mot de passe, organisations, actions notifications).
- Hashage des mots de passe avec `password_hash()` (BCRYPT) et verification `password_verify()`.
- Politique mot de passe renforcee: minimum 10 caracteres, majuscule, minuscule, chiffre, caractere special.
- Echappement HTML systematique dans les vues via `htmlspecialchars(...)` pour limiter les risques XSS.
- Validation de type MIME et de taille pour les uploads (preuves, logos, photo profil).
- Restriction des methodes HTTP (retour 405 sur routes sensibles appelees avec une methode invalide).
- Reponses JSON controlees pour endpoints API avec statut d'erreur explicite.
- Isolation des ressources upload dans `public/uploads/*` avec noms de fichiers assainis.

## Routes publiques francisees (URL)
Routes canoniques utilisees dans les menus et formulaires:
- `?r=connexion`
- `?r=deconnexion`
- `?r=accueil`
- `?r=rapports/creer`
- `?r=rapports/liste`
- `?r=profil`
- `?r=profil/mot-de-passe`
- `?r=notifications`
- `?r=notifications/tout-lire`

Compatibilite legacy conservee:
- `login`, `logout`, `dashboard`, `reports/create`, `reports/list`, `notifications/mark-all-read`.

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

## Invitation utilisateur par email (admin)
Flux simplifie en 3 etapes:
1. L'admin ouvre `Organisations` -> `Inviter un utilisateur`.
2. Le systeme pre-cree un compte inactif et envoie un email d'activation.
3. L'utilisateur clique le lien, definit son mot de passe et active son compte.

Regles de securite du ticket d'activation:
- Lien unique signe par token aleatoire fort (hashe en base).
- Expiration automatique apres 48 heures.
- Ticket invalide apres utilisation.
- Si l'utilisateur n'active pas son compte dans ce delai, le ticket expire dans le systeme.

Message email envoye:
- Felicitations pour la creation de compte SyDRA.
- Rappel du role de SyDRA.
- Lien d'activation.
- Mention explicite: ignorer le message en cas d'erreur.

Configuration SMTP a renseigner dans `.env`:
- `MAIL_FROM`
- `MAIL_FROM_NAME`
- `SMTP_HOST`
- `SMTP_PORT`
- `SMTP_AUTH` (`true`/`false`)
- `SMTP_SECURE` (`tls`, `ssl` ou `none`)
- `SMTP_USER`
- `SMTP_PASS`

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

## Configuration Gmail / SMTP
Pour Gmail, tu ne cherches pas un `smtp_host` dans ta boite de reception. Les valeurs a utiliser sont:
- `SMTP_HOST=smtp.gmail.com`
- `SMTP_PORT=587`
- `SMTP_SECURE=tls`
- `SMTP_USER=ton_adresse@gmail.com`
- `SMTP_PASS=mot_de_passe_d_application`

Dans Google, va dans:
- Compte Google
- Sécurité
- Validation en deux étapes
- Mots de passe des applications

Gmail ne laisse pas utiliser le mot de passe normal du compte pour SMTP si la validation en deux étapes est active. Il faut un mot de passe d'application.

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

## Fichiers d'accueil par role
Le controleur choisit explicitement un fichier d'accueil selon le role:
- `app/Controllers/DashboardController.php` -> `resolveDashboardViewByRole()`
- `app/Views/accueil/accueil_admin.php`
- `app/Views/accueil/accueil_lead.php`
- `app/Views/accueil/accueil_rapporteur.php`
- `app/Views/accueil/accueil_defaut.php`
- `app/Views/accueil/tableau_de_bord.php` (base commune partagee)

## Convention de nommage FR des vues
Pour faciliter la comprehension metier, les vues principales ont ete francisees:
- `app/Views/authentification/connexion.php` (ancien `auth/login.php`)
- `app/Views/rapports/creer.php` (ancien `reports/create.php`)
- `app/Views/rapports/liste.php` (ancien `reports/index.php`)

## Ce que tu peux fournir ensuite
- Charte graphique exacte (logo, polices, couleurs officielles)
- Regles metier precises de validation par role
- Regles de codification officielles initiales
- Parametres SMTP de production
