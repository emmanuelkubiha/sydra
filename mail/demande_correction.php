<?php

declare(strict_types=1);

/** @var array<string, mixed> $donnees */

$nom = (string) ($donnees['nom'] ?? 'Organisation');
$editUrl = (string) ($donnees['edit_url'] ?? '#');
$commentaire = (string) ($donnees['commentaire'] ?? 'Merci de completer les informations manquantes.');

return [
    'subject' => 'Demande de correction de votre alerte',
    'title' => 'Informations supplementaires requises',
    'intro' => 'Le Lead GTMP a demande une correction avant validation.',
    'body_html' => '<p style="margin:0 0 10px;">Bonjour ' . htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') . ',</p>'
        . '<p style="margin:0 0 12px;">Commentaire du Lead:</p>'
        . '<blockquote style="margin:0;padding:12px;border-left:4px solid #005bbb;background:#f8fafc;">' . nl2br(htmlspecialchars($commentaire, ENT_QUOTES, 'UTF-8')) . '</blockquote>'
        . '<p style="margin:12px 0 0;">Mettez a jour votre rapport puis soumettez-le a nouveau.</p>',
    'cta_label' => 'Editer le rapport',
    'cta_url' => $editUrl,
];
