<?php

declare(strict_types=1);

/** @var array<string, mixed> $donnees */

$nom = (string) ($donnees['nom'] ?? 'Organisation');
$detailsUrl = (string) ($donnees['details_url'] ?? '#');
$commentaire = (string) ($donnees['commentaire'] ?? 'Votre alerte a ete rejetee apres analyse.');

return [
    'subject' => 'Alerte rejetee',
    'title' => 'Votre alerte a ete rejetee',
    'intro' => 'Le Lead GTMP a rejete votre soumission.',
    'body_html' => '<p style="margin:0 0 10px;">Bonjour ' . htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') . ',</p>'
        . '<p style="margin:0 0 12px;">Motif du rejet:</p>'
        . '<blockquote style="margin:0;padding:12px;border-left:4px solid #9b1c1c;background:#fff5f5;">' . nl2br(htmlspecialchars($commentaire, ENT_QUOTES, 'UTF-8')) . '</blockquote>'
        . '<p style="margin:12px 0 0;">Cette notification confirme la decision de rejet.</p>',
    'cta_label' => 'Voir les details',
    'cta_url' => $detailsUrl,
    'variant' => 'critical',
];
