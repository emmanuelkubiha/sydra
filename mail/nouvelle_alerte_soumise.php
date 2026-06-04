<?php

declare(strict_types=1);

/** @var array<string, mixed> $donnees */

$lieu = (string) ($donnees['lieu'] ?? 'Localisation non precisee');
$typeIncident = (string) ($donnees['type_incident'] ?? 'FLASH');
$urgence = (string) ($donnees['urgence'] ?? 'Moyenne');
$detailsUrl = (string) ($donnees['details_url'] ?? '#');
$organisation = (string) ($donnees['organisation'] ?? 'Organisation');

return [
    'subject' => 'Nouvelle alerte soumise',
    'title' => 'Nouvelle alerte a valider',
    'intro' => 'Une organisation vient de soumettre un rapport.',
    'body_html' => '<p style="margin:0 0 10px;">Organisation: <strong>' . htmlspecialchars($organisation, ENT_QUOTES, 'UTF-8') . '</strong></p>'
        . '<table style="width:100%;border-collapse:collapse;border:1px solid #e2e8f0;">'
        . '<tr><td style="padding:8px;border-bottom:1px solid #e2e8f0;"><strong>Lieu</strong></td><td style="padding:8px;border-bottom:1px solid #e2e8f0;">' . htmlspecialchars($lieu, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:8px;border-bottom:1px solid #e2e8f0;"><strong>Type</strong></td><td style="padding:8px;border-bottom:1px solid #e2e8f0;">' . htmlspecialchars($typeIncident, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:8px;"><strong>Urgence</strong></td><td style="padding:8px;">' . htmlspecialchars($urgence, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '</table>',
    'cta_label' => 'Consulter et valider',
    'cta_url' => $detailsUrl,
];
