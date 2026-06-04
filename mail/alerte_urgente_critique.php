<?php

declare(strict_types=1);

/** @var array<string, mixed> $donnees */

$lieu = (string) ($donnees['lieu'] ?? 'N/A');
$typeIncident = (string) ($donnees['type_incident'] ?? 'FLASH');
$detailsUrl = (string) ($donnees['details_url'] ?? '#');
$resume = (string) ($donnees['resume'] ?? 'Urgence critique signalee.');

return [
    'subject' => 'ALERTE CRITIQUE - action immediate requise',
    'title' => 'Urgence critique',
    'intro' => 'Une alerte de niveau critique vient d etre soumise.',
    'body_html' => '<p style="margin:0 0 10px;color:#991b1b;font-weight:700;">Diffusion prioritaire GTMP/Cluster.</p>'
        . '<table style="width:100%;border-collapse:collapse;border:1px solid #fecaca;background:#fff5f5;">'
        . '<tr><td style="padding:8px;border-bottom:1px solid #fecaca;"><strong>Lieu</strong></td><td style="padding:8px;border-bottom:1px solid #fecaca;">' . htmlspecialchars($lieu, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:8px;border-bottom:1px solid #fecaca;"><strong>Type</strong></td><td style="padding:8px;border-bottom:1px solid #fecaca;">' . htmlspecialchars($typeIncident, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:8px;"><strong>Resume</strong></td><td style="padding:8px;">' . htmlspecialchars($resume, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '</table>',
    'cta_label' => 'Consulter le rapport complet',
    'cta_url' => $detailsUrl,
    'variant' => 'critical',
];
