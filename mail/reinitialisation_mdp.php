<?php

declare(strict_types=1);

/** @var array<string, mixed> $donnees */

$nom = (string) ($donnees['nom'] ?? 'Utilisateur');
$resetUrl = (string) ($donnees['reset_url'] ?? '#');
$expires = (string) ($donnees['expires_in'] ?? '1 heure');

return [
    'subject' => 'Reinitialisation de mot de passe SyDRA',
    'title' => 'Reinitialisation de mot de passe',
    'intro' => 'Une demande de reinitialisation a ete enregistree.',
    'body_html' => '<p style="margin:0 0 10px;">Bonjour ' . htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') . ',</p>'
        . '<p style="margin:0 0 12px;">Utilisez le bouton ci-dessous pour definir un nouveau mot de passe.</p>'
        . '<p style="margin:0;">Ce lien expire dans ' . htmlspecialchars($expires, ENT_QUOTES, 'UTF-8') . '.</p>'
        . '<p style="margin:12px 0 0;">Si vous n etes pas a l origine de cette demande, ignorez cet email.</p>',
    'cta_label' => 'Reinitialiser mon mot de passe',
    'cta_url' => $resetUrl,
];
