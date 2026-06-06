<?php

declare(strict_types=1);

/** @var array<string, mixed> $donnees */

$nom = (string) ($donnees['nom'] ?? 'Organisation');
$detailsUrl = (string) ($donnees['details_url'] ?? '#');
$deadline = trim((string) ($donnees['review_deadline_human'] ?? $donnees['review_deadline'] ?? ''));
$deadlineText = $deadline !== '' ? $deadline : 'le délai imparti';

return [
    'subject' => 'Alerte rejetee automatiquement (delai expire)',
    'title' => 'Rejet automatique de votre alerte',
    'intro' => 'Le delai de reponse a une demande d\'informations est arrive a expiration.',
    'body_html' => '<p style="margin:0 0 10px;">Bonjour ' . htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') . ',</p>'
        . '<p style="margin:0 0 12px;">Votre alerte a ete rejetee automatiquement, car aucune reponse n\'a ete soumise avant <strong>' . htmlspecialchars($deadlineText, ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
        . '<p style="margin:0;">Statut applique par le systeme: <strong>Rejete</strong>.</p>',
    'cta_label' => 'Voir les details',
    'cta_url' => $detailsUrl,
    'variant' => 'critical',
];
