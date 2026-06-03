<?php
/** @var array<int, array<string, mixed>> $users */
if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return '';
    }
}
?>
<div class="card">
    <h1>Inviter un utilisateur</h1>
    <p><small class="muted">Compte inactif + email d'activation valable 48h.</small></p>

    <form method="post" action="?page=utilisateurs">
        <input type="hidden" name="action" value="invite_user">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

        <div class="grid">
            <div>
                <label>Nom complet</label>
                <input name="full_name" required>
            </div>
            <div>
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
        </div>

        <label>Rôle</label>
        <select name="role" required>
            <option value="REPORTER">Reporteur</option>
            <option value="CLUSTER_LEADER">Cluster Leader</option>
            <option value="CLUSTER_CO_LEAD">Co-Lead Cluster</option>
        </select>

        <button type="submit">Créer et envoyer l'invitation</button>
    </form>
</div>

<div class="card">
    <h2><i class="bi bi-envelope-check icon-inline"></i>Test SMTP</h2>
    <p><small class="muted">Envoyer un email de test pour diagnostiquer la configuration SMTP.</small></p>

    <form method="post" action="?page=utilisateurs">
        <input type="hidden" name="action" value="test_smtp">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

        <label>Email destinataire du test</label>
        <input type="email" name="test_email" value="<?= htmlspecialchars((string) ($config['support_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>

        <button type="submit">Lancer le test SMTP</button>
    </form>
</div>

<div class="card">
    <h2>Utilisateurs</h2>
    <table class="table">
        <thead>
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Email</th>
            <th>Rôle</th>
            <th>Actif</th>
            <th>Créé le</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= (int) $u['id']; ?></td>
                <td><?= htmlspecialchars((string) $u['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars((string) $u['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars((string) $u['role'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= ((int) $u['is_active'] === 1) ? 'Oui' : 'Non'; ?></td>
                <td><?= htmlspecialchars((string) $u['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
