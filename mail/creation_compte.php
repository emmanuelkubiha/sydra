<?php

declare(strict_types=1);

/** @var array<string, mixed> $donnees */

$email = (string) ($donnees['email'] ?? '');
$password = (string) ($donnees['mot_de_passe_genere'] ?? '');
$loginUrl = (string) ($donnees['login_url'] ?? '#');
$nom = (string) ($donnees['nom'] ?? 'Utilisateur');

return [
    'subject' => 'Votre compte SyDRA a ete cree',
    'title' => 'Creation de compte',
    'intro' => 'Votre acces a SyDRA est pret.',
    'body_html' => '<p style="margin:0 0 10px;">Bonjour ' . htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') . ',</p>'
        . '<p style="margin:0 0 12px;">Voici vos identifiants de connexion:</p>'
        . '<div style="border:1px solid #dbeafe;background:#f8fafc;border-radius:8px;padding:12px;">'
        . '<p style="margin:0 0 6px;"><strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p style="margin:0;"><strong>Mot de passe temporaire:</strong> ' . htmlspecialchars($password, ENT_QUOTES, 'UTF-8') . '</p>'
        . '</div>'
        . '<p style="margin:12px 0 0;">Pour des raisons de securite, vous devrez changer ce mot de passe a votre premiere connexion.</p>',
    'cta_label' => 'Se connecter',
    'cta_url' => $loginUrl,
];
