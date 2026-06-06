<?php

declare(strict_types=1);

/** @var array<string, mixed> $donnees */

$nbAlertes = (int) ($donnees['nb_alertes'] ?? 0);
$dashboardUrl = (string) ($donnees['dashboard_url'] ?? '#');
$oldestPendingSince = (string) ($donnees['oldest_pending_since'] ?? '');

return [
    'subject' => 'Rappel validation: alertes en attente',
    'title' => 'Rappel de validation',
    'intro' => 'Des alertes sont en attente depuis plus de 24h.',
    'body_html' => '<p style="margin:0 0 10px;">Le systeme detecte <strong>' . $nbAlertes . ' alerte(s)</strong> en attente de validation.</p>'
        . '<p style="margin:0 0 12px;">Merci de verifier le tableau de bord et de traiter en priorite les cas les plus urgents.</p>'
        . '<div style="border:1px solid #fcd34d;background:#fffbeb;border-radius:10px;padding:12px 14px;">'
        . '<p style="margin:0;"><strong>Anciennete la plus elevee detectee :</strong> ' . htmlspecialchars($oldestPendingSince !== '' ? $oldestPendingSince : 'Plus de 24h', ENT_QUOTES, 'UTF-8') . '</p>'
        . '</div>'
        . '<p style="margin:12px 0 0;">Merci de traiter cette alerte rapidement pour eviter un retard de prise en charge.</p>',
    'cta_label' => 'Ouvrir le tableau de validation',
    'cta_url' => $dashboardUrl,
];
