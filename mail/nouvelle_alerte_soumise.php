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
        . '<table style="width:100%;border-collapse:collapse;border:1px solid #dbeafe;border-radius:10px;overflow:hidden;background:#f8fbff;">'
        . '<tr><td style="padding:9px;border-bottom:1px solid #dbeafe;width:34%;"><strong>Lieu</strong></td><td style="padding:9px;border-bottom:1px solid #dbeafe;">' . htmlspecialchars($lieu, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:9px;border-bottom:1px solid #dbeafe;"><strong>Type</strong></td><td style="padding:9px;border-bottom:1px solid #dbeafe;">' . htmlspecialchars($typeIncident, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:9px;"><strong>Urgence</strong></td><td style="padding:9px;">' . htmlspecialchars($urgence, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '</table>',
    'cta_label' => 'Consulter et valider',
    'cta_url' => $detailsUrl,
];
