<?php
/** @var array $config */
/** @var array|null $authUser */
/** @var string $pageTitle */
$lang = function_exists('current_lang') ? current_lang() : 'fr';
$supportEmail = (string) ($config['support_email'] ?? $config['mail']['from'] ?? 'it@fosip-drc.org');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <?php $cssVersion = @filemtime(__DIR__ . '/../assets/css/style.css') ?: time(); ?>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= (int) $cssVersion; ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<?php $loaderContext = (string) ($_GET['page'] ?? 'connexion'); ?>
<body data-loader-context="<?= htmlspecialchars($loaderContext, ENT_QUOTES, 'UTF-8'); ?>">
<div id="app-loader" class="app-loader" aria-hidden="true">
    <div class="app-loader-box">
        <div class="app-loader-logo"><i class="bi bi-shield-lock icon-inline"></i><?= htmlspecialchars($config['app_name'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="app-loader-spinner"></div>
        <p class="app-loader-title"><?= htmlspecialchars(t('loader.title'), ENT_QUOTES, 'UTF-8'); ?></p>
        <p class="app-loader-subtitle" id="app-loader-subtitle"><?= htmlspecialchars(t('loader.default'), ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
</div>
<div class="topnav">
    <div class="brand-wrap">
        <span class="brand-default-logo" aria-hidden="true"><i class="bi bi-shield-fill-check"></i></span>
        <strong><?= htmlspecialchars($config['app_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
    </div>

    <form class="lang-switch" action="" method="get">
        <input type="hidden" name="page" value="<?= htmlspecialchars($loaderContext, ENT_QUOTES, 'UTF-8'); ?>">
        <select name="lang" aria-label="Language switcher" onchange="this.form.submit()">
            <option value="fr" <?= $lang === 'fr' ? 'selected' : ''; ?>>🇫🇷 FR</option>
            <option value="en" <?= $lang === 'en' ? 'selected' : ''; ?>>🇬🇧 EN</option>
        </select>
    </form>

    <?php if (is_array($authUser)): ?>
        <span style="margin-left:12px;"><?= htmlspecialchars(t('nav.hello'), ENT_QUOTES, 'UTF-8'); ?> <?= htmlspecialchars((string) $authUser['full_name'], ENT_QUOTES, 'UTF-8'); ?></span>
        <a href="?page=tableau_de_bord"><?= htmlspecialchars(t('nav.dashboard'), ENT_QUOTES, 'UTF-8'); ?></a>
        <a href="?page=rapport_creer"><?= htmlspecialchars(t('nav.create_report'), ENT_QUOTES, 'UTF-8'); ?></a>
        <a href="?page=rapports_liste"><?= htmlspecialchars(t('nav.reports'), ENT_QUOTES, 'UTF-8'); ?></a>
        <a href="?page=profil"><?= htmlspecialchars(t('nav.profile'), ENT_QUOTES, 'UTF-8'); ?></a>
        <?php if (($authUser['role'] ?? '') === 'ADMIN'): ?>
            <a href="?page=utilisateurs"><?= htmlspecialchars(t('nav.users'), ENT_QUOTES, 'UTF-8'); ?></a>
        <?php endif; ?>
        <a href="?page=deconnexion"><?= htmlspecialchars(t('nav.logout'), ENT_QUOTES, 'UTF-8'); ?></a>
    <?php endif; ?>
</div>
<?php if (!is_array($authUser)): ?>
    <div class="public-tagline">
        <span class="tagline-pill"><?= htmlspecialchars(t('intro.line1'), ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="tagline-typing" data-rotate-text="1" data-rotate-messages="<?= htmlspecialchars(t('intro.line1') . '|' . t('intro.line3'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(t('intro.line3'), ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
<?php endif; ?>
<div class="container">
