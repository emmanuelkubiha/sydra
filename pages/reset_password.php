<?php
/** @var array|null $activation */
if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return '';
    }
}

$token = trim((string) ($_GET['token'] ?? ''));
?>
<section class="card">
    <h1>Réinitialiser le mot de passe</h1>

    <?php if (!is_array($activation)): ?>
        <p class="muted">Le lien de réinitialisation est invalide ou a expiré.</p>
        <a class="btn" href="?page=connexion">Retour à la connexion</a>
    <?php else: ?>
        <form method="post" action="?page=reinitialiser_mot_de_passe">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

            <label>Nouveau mot de passe</label>
            <input type="password" name="password" required>

            <label>Confirmer le mot de passe</label>
            <input type="password" name="password_confirmation" required>

            <small class="muted">Règle: 10 caractères minimum, majuscule, minuscule, chiffre et caractère spécial.</small>
            <button type="submit">Enregistrer le nouveau mot de passe</button>
        </form>
    <?php endif; ?>
</section>
