<?php
/** @var array<string, mixed>|null $emailChangeRequest */
if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return '';
    }
}
?>
<div class="card">
    <h1>Confirmation de changement d'adresse email</h1>

    <?php if (!is_array($emailChangeRequest)): ?>
        <p>Le lien est invalide, expiré ou déjà utilisé.</p>
        <p><a class="btn" href="?page=connexion">Retour à la connexion</a></p>
    <?php else: ?>
        <p>Vous êtes sur le point de confirmer le changement de votre adresse email sur SyDRA.</p>

        <div class="aide-details">
            <p><strong>Ancienne adresse:</strong> <?= htmlspecialchars((string) ($emailChangeRequest['old_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Nouvelle adresse:</strong> <?= htmlspecialchars((string) ($emailChangeRequest['new_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Expiration du lien:</strong> <?= htmlspecialchars((string) ($emailChangeRequest['expires_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <form method="post" action="?page=confirmer_email" class="mt-3">
            <input type="hidden" name="action" value="confirm_email_change">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars((string) ($emailChangeRequest['token'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <button type="submit">Confirmer ma nouvelle adresse email</button>
        </form>

        <p class="inline-hint">Après confirmation, vous devrez vous reconnecter. Si besoin, utilisez “Mot de passe oublié”.</p>
    <?php endif; ?>
</div>
