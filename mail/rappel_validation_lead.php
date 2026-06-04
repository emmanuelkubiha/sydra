<?php

declare(strict_types=1);

/** @var array<string, mixed> $donnees */

$nbAlertes = (int) ($donnees['nb_alertes'] ?? 0);
$dashboardUrl = (string) ($donnees['dashboard_url'] ?? '#');

return [
    'subject' => 'Rappel validation: alertes en attente',
    'title' => 'Rappel de validation',
    'intro' => 'Des alertes sont en attente depuis plus de 24h.',
    'body_html' => '<p style="margin:0 0 10px;">Le systeme detecte <strong>' . $nbAlertes . ' alerte(s)</strong> en attente de validation.</p>'
        . '<p style="margin:0;">Merci de verifier le tableau de bord et de traiter les cas prioritaires.</p>',
    'cta_label' => 'Ouvrir le tableau de validation',
    'cta_url' => $dashboardUrl,
];
