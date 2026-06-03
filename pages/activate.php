<div class="card">
    <h1>Activation du compte</h1>

    <?php if (!is_array($activation)): ?>
        <p>Ce lien est invalide, expiré ou déjà utilisé.</p>
        <a class="btn" href="?page=connexion">Retour à la connexion</a>
    <?php else: ?>
        <p>
            <small class="muted">
                Compte: <?= htmlspecialchars((string) $activation['full_name'], ENT_QUOTES, 'UTF-8'); ?>
                | Email: <?= htmlspecialchars((string) $activation['email'], ENT_QUOTES, 'UTF-8'); ?>
                | Expire le: <?= htmlspecialchars((string) $activation['expires_at'], ENT_QUOTES, 'UTF-8'); ?>
            </small>
        </p>

        <form method="post" action="?page=activation_compte&token=<?= urlencode((string) ($_GET['token'] ?? '')); ?>">
            <input type="hidden" name="action" value="activate_account">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars((string) ($_GET['token'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <label>Nouveau mot de passe</label>
            <input type="password" name="password" required>

            <label>Confirmer le mot de passe</label>
            <input type="password" name="password_confirmation" required>

            <button type="submit">Activer mon compte</button>
        </form>
    <?php endif; ?>
</div>
