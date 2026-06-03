<?php
/** @var array<string, mixed> $authUser */
if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return '';
    }
}
?>
<div class="card">
    <h1>Profil</h1>
    <form method="post" action="?page=profil" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update_profile">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

        <label>Nom complet</label>
        <input name="full_name" value="<?= htmlspecialchars((string) $authUser['full_name'], ENT_QUOTES, 'UTF-8'); ?>" required>

        <label>Email</label>
        <input value="<?= htmlspecialchars((string) $authUser['email'], ENT_QUOTES, 'UTF-8'); ?>" disabled>

        <div class="grid">
            <div>
                <label>Téléphone</label>
                <input name="phone" value="<?= htmlspecialchars((string) ($authUser['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="+243...">
            </div>
            <div>
                <label>Fonction</label>
                <input name="job_title" value="<?= htmlspecialchars((string) ($authUser['job_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Coordinateur, Agent terrain...">
            </div>
        </div>

        <label>Organisation</label>
        <input name="organization_name" value="<?= htmlspecialchars((string) ($authUser['organization_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Nom de l'organisation">

        <label>Bio / Notes</label>
        <textarea name="bio" rows="3" placeholder="Informations utiles sur votre profil"><?= htmlspecialchars((string) ($authUser['bio'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>

        <label>Photo de profil</label>
        <input type="file" name="avatar_file" accept="image/jpeg,image/png,image/webp,image/gif">

        <?php if (!empty($authUser['avatar_path'])): ?>
            <p><img class="avatar" src="<?= htmlspecialchars((string) $authUser['avatar_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="avatar"></p>
        <?php endif; ?>

        <button type="submit">Mettre à jour</button>
    </form>
</div>
