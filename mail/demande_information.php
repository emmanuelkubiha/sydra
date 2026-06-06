<?php

declare(strict_types=1);

/** @var array<string, mixed> $donnees */

$nom = (string) ($donnees['nom'] ?? 'Organisation');
$editUrl = (string) ($donnees['edit_url'] ?? '#');
$commentaire = (string) ($donnees['commentaire'] ?? 'Merci de completer les informations manquantes.');
$deadlineHuman = trim((string) ($donnees['review_deadline_human'] ?? $donnees['review_deadline'] ?? ''));
$deadlineLabel = $deadlineHuman !== '' ? $deadlineHuman : 'la date limite définie';

return [
    'subject' => 'Demande d\'information complementaire sur votre alerte',
    'title' => 'Informations complementaires requises',
    'intro' => 'Le Lead GTMP demande des precisions avant decision finale.',
    'body_html' => '<p style="margin:0 0 10px;">Bonjour ' . htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') . ',</p>'
        . '<p style="margin:0 0 12px;">Commentaire du Lead:</p>'
        . '<blockquote style="margin:0;padding:12px;border-left:4px solid #005bbb;background:#f8fafc;">' . nl2br(htmlspecialchars($commentaire, ENT_QUOTES, 'UTF-8')) . '</blockquote>'
        . '<p style="margin:12px 0 0;"><strong>Action requise :</strong> Veuillez modifier et compléter votre alerte avant le <strong>' . htmlspecialchars($deadlineLabel, ENT_QUOTES, 'UTF-8') . '</strong>. Passé ce délai, le système considèrera cette alerte comme non-avérée et la rejettera automatiquement.</p>',
    'cta_label' => 'Modifier mon rapport',
    'cta_url' => $editUrl,
];
