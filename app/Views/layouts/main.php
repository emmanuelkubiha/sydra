<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Helpers\Lang;

$user = Auth::user();
$contentView = $contentView ?? '';
$appName = htmlspecialchars($app['name'] ?? 'SyDRA', ENT_QUOTES, 'UTF-8');
$pageTitle = htmlspecialchars($title ?? $appName, ENT_QUOTES, 'UTF-8');
$locale = Lang::current();
$route = (string) ($_GET['r'] ?? 'dashboard');
$isAuthPage = ($route === 'login');
?>
<!doctype html>
<html lang="<?= htmlspecialchars($locale, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div id="page-loader" class="page-loader">
    <div class="loader-box">
        <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
        <div class="loader-text" id="loader-tip">Chargement de SyDRA...</div>
    </div>
</div>
<?php if ($isAuthPage): ?>
<!-- Section: Header public (auth) -->
<header class="top-header public-header">
    <div class="container-fluid d-flex justify-content-between align-items-center py-2 px-3">
        <div class="brand-mini"><i class="bi bi-shield-lock-fill"></i> <?= $appName; ?></div>
        <div class="lang-compact dropdown">
            <button class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown" type="button">🌐</button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="?r=login&lang=fr">Francais</a></li>
                <li><a class="dropdown-item" href="?r=login&lang=en">English</a></li>
            </ul>
        </div>
    </div>
</header>
<div class="auth-shell">
    <div class="container py-5">
        <?php require $contentView; ?>
    </div>
</div>
<!-- Section: Footer public (auth) -->
<footer class="main-footer py-2 small text-center">&copy; <?= date('Y'); ?> <?= htmlspecialchars(Lang::tr('app.footer'), ENT_QUOTES, 'UTF-8'); ?></footer>
<?php else: ?>
<div class="wrapper">
    <!-- Section: Header prive -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button">☰</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="?r=dashboard" class="nav-link"><i class="bi bi-house-door"></i> <?= htmlspecialchars(Lang::tr('menu.dashboard'), ENT_QUOTES, 'UTF-8'); ?></a>
            </li>
        </ul>

        <ul class="navbar-nav ms-auto align-items-center">
            <li class="nav-item me-2 dropdown">
                <button class="btn btn-xs btn-light dropdown-toggle px-2 py-1" data-bs-toggle="dropdown" type="button" title="<?= htmlspecialchars(Lang::tr('menu.language'), ENT_QUOTES, 'UTF-8'); ?>">🌐</button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="?r=<?= htmlspecialchars($route, ENT_QUOTES, 'UTF-8'); ?>&lang=fr">Francais</a></li>
                    <li><a class="dropdown-item" href="?r=<?= htmlspecialchars($route, ENT_QUOTES, 'UTF-8'); ?>&lang=en">English</a></li>
                </ul>
            </li>
            <?php if ($user !== null): ?>
            <li class="nav-item small text-muted me-3">
                <?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?> | <?= htmlspecialchars($user['organization_name'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
            </li>
            <li class="nav-item"><a href="?r=logout" class="btn btn-sm btn-outline-danger"><?= htmlspecialchars(Lang::tr('menu.logout'), ENT_QUOTES, 'UTF-8'); ?></a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="?r=dashboard" class="brand-link">
            <span class="brand-title"><?= $appName; ?></span>
            <span class="brand-subtitle">Systeme de Documentation, Rapportage et Alerte</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    <!-- Section: Menu lateral -->
                    <li class="nav-item"><a href="?r=dashboard" class="nav-link"><i class="nav-icon bi bi-speedometer2"></i><p><?= htmlspecialchars(Lang::tr('menu.dashboard'), ENT_QUOTES, 'UTF-8'); ?></p></a></li>
                    <li class="nav-item"><a href="?r=reports/create" class="nav-link"><i class="nav-icon bi bi-pencil-square"></i><p><?= htmlspecialchars(Lang::tr('menu.new_report'), ENT_QUOTES, 'UTF-8'); ?></p></a></li>
                    <li class="nav-item"><a href="?r=reports/list" class="nav-link"><i class="nav-icon bi bi-list-ul"></i><p><?= htmlspecialchars(Lang::tr('menu.reports'), ENT_QUOTES, 'UTF-8'); ?></p></a></li>
                    <li class="nav-item"><a href="?r=cartographie" class="nav-link"><i class="nav-icon bi bi-geo-alt"></i><p><?= htmlspecialchars(Lang::tr('menu.map'), ENT_QUOTES, 'UTF-8'); ?></p></a></li>
                    <li class="nav-item"><a href="?r=organisations" class="nav-link"><i class="nav-icon bi bi-diagram-3"></i><p><?= htmlspecialchars(Lang::tr('menu.organisations'), ENT_QUOTES, 'UTF-8'); ?></p></a></li>
                    <li class="nav-item"><a href="?r=profil/mot-de-passe" class="nav-link"><i class="nav-icon bi bi-person-gear"></i><p><?= htmlspecialchars(Lang::tr('menu.profile'), ENT_QUOTES, 'UTF-8'); ?></p></a></li>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Section: Contenu principal -->
    <div class="content-wrapper">
        <section class="content pt-3">
            <div class="container-fluid">
                <?php require $contentView; ?>
            </div>
        </section>
    </div>

    <!-- Section: Footer prive -->
    <footer class="main-footer py-2 small text-center">&copy; <?= date('Y'); ?> <?= htmlspecialchars(Lang::tr('app.footer'), ENT_QUOTES, 'UTF-8'); ?></footer>
</div>
<?php endif; ?>

<input type="hidden" id="csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net@1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<script>
window.SYDRA = {
    route: "<?= htmlspecialchars($route, ENT_QUOTES, 'UTF-8'); ?>",
    lang: "<?= htmlspecialchars($locale, ENT_QUOTES, 'UTF-8'); ?>",
    isAuthPage: <?= $isAuthPage ? 'true' : 'false'; ?>
};
</script>
<script src="assets/js/app.js"></script>
</body>
</html>
