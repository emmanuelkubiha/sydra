<?php
/** @var array|null $authUser */
?>
<div class="card">
    <h1><?= htmlspecialchars(t('dashboard.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="hero-intro"><?= htmlspecialchars(t('intro.line3'), ENT_QUOTES, 'UTF-8'); ?></p>
    <p><?= htmlspecialchars(t('dashboard.body'), ENT_QUOTES, 'UTF-8'); ?></p>
    <?php if (is_array($authUser) && ($authUser['role'] ?? '') === 'ADMIN'): ?>
        <a class="btn" href="?page=utilisateurs">Inviter un utilisateur</a>
    <?php endif; ?>
    <a class="btn" href="?page=rapport_creer">Créer un rapport</a>
</div>
