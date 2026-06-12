# Changelog

Tous les changements notables du projet SyDRA sont documentés ici.

## v0.9.3 - 2026-06-12

### Correctifs (Sprint 4 — Antigravity)
- **Viewport** : Remplacement de la balise viewport restrictive (`maximum-scale=1.0, user-scalable=no`) par la version standard, permettant le zoom sur les pages à données lourdes (tableaux, rapports).
- **Responsivité tables** : Remplacement de `overflow-x: hidden` sur body/html par `overflow-x: clip`. Les conteneurs `.table-responsive` scrollent désormais horizontalement de manière indépendante sans couper la page.
- **Timeline (rapportage-details)** : Correction du chevauchement des éléments avec `margin-bottom: 1.5rem`, padding et box propres pour chaque bloc.
- **Filtre (applyFilter)** : Le popup SweetAlert2 affiche désormais l'erreur PHP/SQL brute (via `<pre>`) au lieu du message générique, permettant un débogage direct à l'écran.

### Améliorations IA (Chatbot)
- **Message d'accueil personnalisé** : L'assistant salue l'utilisateur par son nom (injecté via PHP/sessionStorage) avec un message aléatoire parmi 5 variantes.
- **Avertissement de sécurité contextuel** : Si l'utilisateur n'est pas sur une page de rapportage (wizard, IA, détails), un avertissement 🔒 est ajouté au message d'accueil.
- **Smart Chips enrichis** : Trois boutons cliquables — 📝 Créer via le Wizard, ✨ Créer avec l'IA, 📋 Voir mes rapports — affichés dans la bulle d'accueil en mode Aide Générale.
- **Persistance mémoire** : Sauvegarde systématique dans `sessionStorage` après chaque message (utilisateur et assistant), rechargement au changement de page.

## v0.9.2 - 2026-06-07

### Ajouts
- Intégration d'un widget IA global flottant (toutes pages authentifiées) avec ouverture Offcanvas.
- Ajout d'un badge dynamique de mode dans le chat: aide générale ou analyse codifiée.
- Nouveau script client `assets/js/ai_chat.js` pour routage sécurisé par page.

### Sécurité
- Politique Zero Data Leak côté frontend:
	- `GENERIC_HELP` sur pages non autorisées (aucune donnée métier envoyée).
	- `DRAFTING` sur pages de création.
	- `ANALYSIS` sur détail alerte avec envoi du seul `report_id`.
- Durcissement de `api/ai_handler.php`:
	- logique serveur pilotée par mode,
	- récupération des données d'alerte directement en base en mode `ANALYSIS`,
	- codification obligatoire (`codification_rules`) avant injection dans le prompt,
	- blocage des rôles non autorisés pour l'analyse.

### Refactoring
- Suppression du chatbot local spécifique de la page détail au profit d'un canal IA global unique.

### Correctifs
- Ajustement du loader global pour ignorer le formulaire du chat IA et éviter les comportements de faux rechargement/boucle visuelle.

## v0.9.1 - 2026-06-06

### Ajouts
- Hero dashboard orienté rôle avec CTA métiers.
- Boutons contextuels pour profils décisionnels: Gérer les alertes, Consulter mes alertes.
- Rétablissement de l'accès Statistiques avancées dans le hero admin/lead.
- Icônes Bootstrap dans les popups de retour de décision (approuvé, en revue, rejeté, soumis).

### Documentation
- Ajout du suivi version/date dans la documentation.
- Création du présent fichier CHANGELOG pour traçabilité des mises à jour.

## v0.9.0 - 2026-06-06

### Correctifs
- API de décision durcie: réponse JSON garantie avec gestion des erreurs.
- Ajout d'un warning explicite quand la base est mise à jour mais l'email échoue.
- Correction des redirections de session/login.

### UX workflow décision
- Verrouillage du panneau d'actions quand une décision existe déjà.
- Affichage du statut courant, commentaire lead, date/heure de soumission.
- Ajout du bouton Modifier la décision avec confirmation SweetAlert2.
- Réouverture du panneau d'actions après confirmation.

### Documentation et sécurité
- Guide de déploiement production (local vers en ligne) enrichi dans le README.
- Exclusion des fichiers d'environnement sensibles du versioning.
