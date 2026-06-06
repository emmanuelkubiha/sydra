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
        . '<div style="border:1px solid #dbeafe;background:#f8fbff;border-radius:10px;padding:12px 14px;">'
        . '<p style="margin:0 0 6px;"><strong>Duree de validite :</strong> ' . htmlspecialchars($expires, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p style="margin:0;color:#475569;">Pour votre securite, ce lien ne peut etre utilise qu\'une seule fois.</p>'
        . '</div>'
        . '<p style="margin:12px 0 0;color:#7f1d1d;"><strong>Important :</strong> Si vous n etes pas a l origine de cette demande, ignorez cet email.</p>',
    'cta_label' => 'Reinitialiser mon mot de passe',
    'cta_url' => $resetUrl,
];
