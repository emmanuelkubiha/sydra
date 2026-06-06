# Changelog

Tous les changements notables du projet SyDRA sont documentés ici.

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
