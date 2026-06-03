<?php
if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return '';
    }
}

$supportEmail = (string) ($config['support_email'] ?? $config['mail']['from'] ?? 'it@fosip-drc.org');
$recentEmail = (string) ($_SESSION['password_reset_recent'] ?? '');
if ($recentEmail !== '') {
    unset($_SESSION['password_reset_recent']);
}
$smtpHost = trim((string) ($config['mail']['smtp_host'] ?? ''));
?>
<section class="card">
    <h1><?= htmlspecialchars(t('forgot.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="muted"><?= htmlspecialchars(t('forgot.subtitle'), ENT_QUOTES, 'UTF-8'); ?></p>

    <?php if ($recentEmail !== ''): ?>
        <div class="flash success">Un email de réinitialisation a déjà été envoyé à <?= htmlspecialchars($recentEmail, ENT_QUOTES, 'UTF-8'); ?>. Vérifiez votre boîte de réception avant de refaire une demande.</div>
    <?php else: ?>
        <form method="post" action="?page=mot_de_passe_oublie">
            <input type="hidden" name="action" value="request_password_reset">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

            <label>Email</label>
            <input type="email" name="reset_email" placeholder="votre.email@exemple.org" required>

            <button type="submit"><?= htmlspecialchars(t('forgot.submit'), ENT_QUOTES, 'UTF-8'); ?></button>
        </form>
    <?php endif; ?>

    <?php if ($smtpHost === ''): ?>
        <div class="flash error">SMTP non configuré: SMTP_HOST est vide dans la configuration actuelle.</div>
    <?php endif; ?>

    <div class="forgot-actions">
        <a class="btn" href="?page=connexion"><?= htmlspecialchars(t('forgot.back'), ENT_QUOTES, 'UTF-8'); ?></a>
    </div>

    <details class="aide-details">
        <summary><?= htmlspecialchars(t('forgot.help'), ENT_QUOTES, 'UTF-8'); ?></summary>
        <p><a class="btn" href="mailto:<?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(t('forgot.write_us'), ENT_QUOTES, 'UTF-8'); ?></a></p>
    </details>
</section>
