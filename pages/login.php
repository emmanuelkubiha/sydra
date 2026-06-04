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
            <div class="password-field mb-2">
                <input type="password" id="login-password" name="password" required>
                <button class="password-toggle" type="button" data-toggle-password="login-password" aria-label="Afficher le mot de passe">
                    <i class="fa-regular fa-eye"></i>
                </button>
            </div>

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
            <p class="muted"><strong>Comptes de test prototype</strong> (mot de passe actuel pour tous: <code>password</code>)</p>
            <p class="inline-hint">Ces accès ne doivent jamais rester affichés en production.</p>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-2">
                    <thead>
                        <tr>
                            <th>Email de connexion</th>
                            <th>Rôle</th>
                            <th>Mot de passe test</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>it@fosip-drc.org</td><td>ADMIN</td><td><code>password</code></td></tr>
                        <tr><td>lead.cluster@sydra.local</td><td>CLUSTER_LEADER</td><td><code>password</code></td></tr>
                        <tr><td>cluster@sydra.local</td><td>CLUSTER_PROTECTION</td><td><code>password</code></td></tr>
                        <tr><td>lead.gtmp@sydra.local</td><td>GTMP_LEAD</td><td><code>password</code></td></tr>
                        <tr><td>colead.cluster@sydra.local</td><td>GTMP_COLEAD</td><td><code>password</code></td></tr>
                        <tr><td>colead.gtmp@sydra.local</td><td>GTMP_COLEAD</td><td><code>password</code></td></tr>
                        <tr><td>reporter@sydra.local</td><td>ORG_REPORTER</td><td><code>password</code></td></tr>
                        <tr><td>reporter@caritas-uvira.cd</td><td>ORG_REPORTER</td><td><code>password</code></td></tr>
                    </tbody>
                </table>
            </div>
            <p class="muted">Si la connexion échoue, utilisez "Mot de passe oublié ?" sous le formulaire.</p>

            <p class="inline-hint">Si le problème persiste, contactez l'admin: <strong><?= htmlspecialchars((string) ($config['support_email'] ?? $config['mail']['from'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></p>
        </details>
    </div>
</div>

<script>
(function () {
    Array.prototype.forEach.call(document.querySelectorAll('.password-toggle[data-toggle-password]'), function (button) {
        button.addEventListener('click', function () {
            var targetId = button.getAttribute('data-toggle-password') || '';
            var input = targetId ? document.getElementById(targetId) : null;
            if (!input) {
                return;
            }

            var isPassword = input.getAttribute('type') === 'password';
            input.setAttribute('type', isPassword ? 'text' : 'password');
            button.setAttribute('aria-label', isPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
            button.innerHTML = isPassword
                ? '<i class="fa-regular fa-eye-slash"></i>'
                : '<i class="fa-regular fa-eye"></i>';
        });
    });
})();
</script>
