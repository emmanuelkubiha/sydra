<?php
/** @var array|null $authUser */
if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return '';
    }
}
?>
<section class="login-hero card">
    <h1><?= htmlspecialchars(t('login.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="muted"><?= htmlspecialchars(t('login.subtitle'), ENT_QUOTES, 'UTF-8'); ?></p>
    <p class="hero-intro"><?= htmlspecialchars(t('intro.line2'), ENT_QUOTES, 'UTF-8'); ?></p>
</section>

<div class="login-grid">
    <div class="card">
        <div class="login-logo-wrap">
            <img class="login-logo" src="assets/img/sydra-logo/BLEU-PRIMARY-SYDRA-LOGO.png" alt="Logo SyDRA" height="40">
        </div>
        <h2><?= htmlspecialchars(t('login.secure_title'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <form method="post" action="?page=connexion">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

            <label><?= htmlspecialchars(t('login.email'), ENT_QUOTES, 'UTF-8'); ?></label>
            <input type="email" name="email" required>

            <label><?= htmlspecialchars(t('login.password'), ENT_QUOTES, 'UTF-8'); ?></label>
            <input type="password" name="password" required>

            <button type="submit"><?= htmlspecialchars(t('login.submit'), ENT_QUOTES, 'UTF-8'); ?></button>
        </form>
        <p class="forgot-password-link">
            <a href="?page=mot_de_passe_oublie"><?= htmlspecialchars(t('login.forgot'), ENT_QUOTES, 'UTF-8'); ?></a>
        </p>
    </div>

    <div class="card">
        <h2><i class="bi bi-life-preserver icon-inline"></i>Besoin d'aide</h2>
        <details class="aide-details">
            <summary>Afficher l'aide de connexion</summary>
            <p class="muted"><strong>Comptes démo temporaires</strong> (mot de passe pour tous: <code>password</code>)</p>
            <ul>
                <li>it@fosip-drc.org (ADMIN)</li>
                <li>lead.cluster@sydra.local (CLUSTER_LEADER)</li>
                <li>colead.cluster@sydra.local (CLUSTER_CO_LEAD)</li>
                <li>reporter@sydra.local (REPORTER)</li>
            </ul>
            <p class="muted">Si la connexion échoue, utilisez "Mot de passe oublié ?" sous le formulaire.</p>

            <p class="inline-hint">Si le problème persiste, contactez l'admin: <strong><?= htmlspecialchars((string) ($config['support_email'] ?? $config['mail']['from'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></p>
        </details>
    </div>
</div>
