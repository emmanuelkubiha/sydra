<?php

declare(strict_types=1);

/** @var array<string, mixed> $donnees */

$nom = (string) ($donnees['nom'] ?? 'Organisation');
$detailsUrl = (string) ($donnees['details_url'] ?? '#');
$lieu = (string) ($donnees['lieu'] ?? 'N/A');
$typeIncident = (string) ($donnees['type_incident'] ?? 'FLASH');

return [
    'subject' => 'Votre alerte a ete validee',
    'title' => 'Alerte validee et publiee',
    'intro' => 'Le Cluster a valide votre soumission.',
    'body_html' => '<p style="margin:0 0 10px;">Bonjour ' . htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') . ',</p>'
        . '<p style="margin:0 0 12px;">Votre alerte est désormais validée et publiée pour la coordination opérationnelle.</p>'
        . '<p style="margin:0;">Lieu: <strong>' . htmlspecialchars($lieu, ENT_QUOTES, 'UTF-8') . '</strong><br>Type: <strong>' . htmlspecialchars($typeIncident, ENT_QUOTES, 'UTF-8') . '</strong></p>',
    'cta_label' => 'Voir le rapport publie',
    'cta_url' => $detailsUrl,
];
