<?php
/** @var array $config */
/** @var array|null $authUser */
/** @var string $pageTitle */
/** @var array<int, array<string, mixed>> $topNotifications */
/** @var int $unreadNotificationsCount */

$lang = function_exists('current_lang') ? current_lang() : 'fr';
$loaderContext = (string) ($_GET['page'] ?? 'connexion');
$notifItems = is_array($topNotifications ?? null) ? $topNotifications : [];
$notifCount = (int) ($unreadNotificationsCount ?? 0);
$pendingValidationCount = 0;
$notifDisplayCount = 0;

$sessionUserId = (int) ($_SESSION['user_id'] ?? $_SESSION['auth_user_id'] ?? 0);
if (isset($pdo) && $pdo instanceof PDO && $sessionUserId > 0) {
    try {
        $colStmt = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table');
        $colStmt->execute(['table' => 'notifications']);
        $notifColumns = array_map('strtolower', $colStmt->fetchAll(PDO::FETCH_COLUMN));

        $hasIsRead = in_array('is_read', $notifColumns, true);
        $hasReadAt = in_array('read_at', $notifColumns, true);

        if ($hasIsRead || $hasReadAt) {
            $countSql = $hasIsRead
                ? 'SELECT COUNT(*) FROM notifications WHERE (user_id = :uid OR user_id IS NULL) AND is_read = 0'
                : 'SELECT COUNT(*) FROM notifications WHERE (user_id = :uid OR user_id IS NULL) AND read_at IS NULL';
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute(['uid' => $sessionUserId]);
            $notifCount = (int) $countStmt->fetchColumn();

            $listSql = 'SELECT n.id, n.title, n.message, n.target_url, n.created_at
                        FROM notifications n
                        WHERE (n.user_id = :uid OR n.user_id IS NULL)
                        ORDER BY n.created_at DESC
                        LIMIT 5';
            $listStmt = $pdo->prepare($listSql);
            $listStmt->execute(['uid' => $sessionUserId]);
            $notifItems = $listStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    } catch (Throwable $e) {
        // Fallback silencieux sur les variables deja injectees.
    }
}

if (isset($pdo) && $pdo instanceof PDO && is_array($authUser)) {
    $roleCode = strtoupper((string) ($authUser['role'] ?? $authUser['role_code'] ?? ''));
    if ($roleCode === '' && isset($authUser['role_id'])) {
        try {
            $roleStmt = $pdo->prepare('SELECT COALESCE(code, "") FROM roles WHERE id = :id LIMIT 1');
            $roleStmt->execute(['id' => (int) ($authUser['role_id'] ?? 0)]);
            $roleCode = strtoupper((string) $roleStmt->fetchColumn());
        } catch (Throwable $e) {
            $roleCode = '';
        }
    }

    if (in_array($roleCode, ['ADMIN', 'CLUSTER_LEADER', 'GTMP_LEAD', 'GTMP_COLEAD', 'CLUSTER_PROTECTION', 'LEAD_GTMP'], true)) {
        try {
            $pendingStmt = $pdo->query('SELECT COUNT(*)
                                        FROM reports
                                        WHERE LOWER(REPLACE(REPLACE(REPLACE(COALESCE(workflow_status, ""), "é", "e"), "è", "e"), "ê", "e"))
                                              IN ("soumis", "submitted", "en revue", "en revision", "under_review")');
            $pendingValidationCount = (int) $pendingStmt->fetchColumn();
        } catch (Throwable $e) {
            $pendingValidationCount = 0;
        }
    }
}

$notifDisplayCount = max(0, $notifCount) + max(0, $pendingValidationCount);

$formatNotifDate = static function (string $raw): string {
    $ts = strtotime($raw);
    if ($ts === false) {
        return $raw;
    }

    $months = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
        7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
    ];
    $month = $months[(int) date('n', $ts)] ?? date('m', $ts);
    return 'Le ' . date('d', $ts) . ' ' . $month . ' ' . date('Y', $ts) . ' à ' . date('H\\hi', $ts);
};
$isAuth = is_array($authUser);
$homeLink = $isAuth ? '?page=tableau_de_bord' : '?page=connexion';
$menuFile = __DIR__ . '/menus/menu_reporter.php';
$orgDisplayName = trim((string) ($authUser['organization_name'] ?? $authUser['full_name'] ?? 'Organisation'));
$orgLogoPath = trim((string) ($authUser['logo_path'] ?? $authUser['avatar_path'] ?? ''));
$orgEmail = trim((string) ($authUser['email'] ?? ''));
$orgInitials = 'OG';
if ($orgDisplayName !== '') {
    $parts = preg_split('/\s+/', $orgDisplayName) ?: [];
    $first = isset($parts[0][0]) ? strtoupper((string) $parts[0][0]) : '';
    $second = isset($parts[1][0]) ? strtoupper((string) $parts[1][0]) : '';
    $orgInitials = ($first . $second) !== '' ? ($first . $second) : 'OG';
}

$logoWhitePath = 'assets/img/sydra-logo/WHITE-PRIMARY-SYDRA-LOGO.png';
$logoBluePath = 'assets/img/sydra-logo/BLEU-PRIMARY-SYDRA-LOGO.png';
$loaderGifWhitePath = 'assets/img/sydra-logo/animated/sydra-loader-white.gif';
$loaderGifBluePath = 'assets/img/sydra-logo/animated/sydra-loader-blue.gif';

// Overlay bleu/sombre => GIF blanc; overlay clair => GIF bleu.
$loaderIsDark = true;
$loaderGifPath = $loaderIsDark ? $loaderGifWhitePath : $loaderGifBluePath;

if ($isAuth) {
    $role = strtoupper((string) ($authUser['role'] ?? 'REPORTER'));
    if ($role === 'ADMIN') {
        $menuFile = __DIR__ . '/menus/menu_admin.php';
    } elseif (in_array($role, ['CLUSTER_LEADER', 'LEAD_GTMP', 'GTMP_LEAD'], true)) {
        $menuFile = __DIR__ . '/menus/menu_lead.php';
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="icon" type="image/png" href="assets/img/sydra-logo/BLEU-PRIMARY-SYDRA-LOGO.png">
    <link rel="apple-touch-icon" href="assets/img/sydra-logo/BLEU-PRIMARY-SYDRA-LOGO.png">
    <?php $cssVersion = @filemtime(__DIR__ . '/../assets/css/style.css') ?: time(); ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= (int) $cssVersion; ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-dt@2.0.8/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
</head>
<body data-loader-context="<?= htmlspecialchars($loaderContext, ENT_QUOTES, 'UTF-8'); ?>">
<div id="app-loader" class="app-loader<?= $loaderIsDark ? ' loader-dark' : ' loader-light'; ?>" aria-hidden="true">
    <div class="app-loader-box">
        <div class="app-loader-logo"><?= htmlspecialchars($config['app_name'], ENT_QUOTES, 'UTF-8'); ?></div>
        <img
            class="app-loader-gif"
            src="<?= htmlspecialchars($loaderGifPath, ENT_QUOTES, 'UTF-8'); ?>"
            alt="Chargement SyDRA"
            width="84"
            height="84"
        >
        <p class="app-loader-title"><?= htmlspecialchars(t('loader.title'), ENT_QUOTES, 'UTF-8'); ?></p>
        <p class="app-loader-subtitle" id="app-loader-subtitle"><?= htmlspecialchars(t('loader.default'), ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
</div>

<?php if ($isAuth): ?>
<div class="app-shell">
    <aside class="sidebar-left" id="mobile-sidebar" aria-label="Navigation principale">
        <a class="sidebar-brand" href="<?= htmlspecialchars($homeLink, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Retour accueil SyDRA">
            <img class="brand-logo-img" src="<?= htmlspecialchars($logoWhitePath, ENT_QUOTES, 'UTF-8'); ?>" alt="Logo SyDRA" height="40">
            <div>
                <strong><?= htmlspecialchars($config['app_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <small>Cluster Protection</small>
            </div>
        </a>

        <nav class="sidebar-menu">
            <?php if (is_file($menuFile)) {
                require $menuFile;
            } ?>
        </nav>

        <div class="sidebar-help-card shadow-sm rounded-4">
            <div class="sidebar-help-icon" aria-hidden="true"><i class="fa-solid fa-life-ring"></i></div>
            <div class="sidebar-help-content">
                <strong>Besoin d'aide ?</strong>
                <span>Consultez les guides et la FAQ SyDRA.</span>
                <a href="?page=aide" class="sidebar-help-link">Consulter la FAQ</a>
            </div>
        </div>
    </aside>
    <button type="button" class="sidebar-mobile-overlay" id="sidebar-mobile-overlay" aria-hidden="true" tabindex="-1"></button>

    <main class="main-panel">
        <header class="topbar-adminlte">
            <div class="topbar-left">
                <button
                    type="button"
                    class="mobile-menu-btn"
                    id="mobile-menu-toggle"
                    aria-label="Ouvrir le menu"
                    aria-controls="mobile-sidebar"
                    aria-expanded="false"
                >
                    <i class="bi bi-list"></i>
                </button>
                <a class="topbar-brand-link" href="<?= htmlspecialchars($homeLink, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Retour dashboard">
                    <img class="brand-logo-img topbar-logo" src="<?= htmlspecialchars($logoBluePath, ENT_QUOTES, 'UTF-8'); ?>" alt="Logo SyDRA" height="40">
                </a>
                <h1><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
            </div>

            <div class="topbar-right">
                <form class="lang-switch" action="" method="get">
                    <input type="hidden" name="page" value="<?= htmlspecialchars($loaderContext, ENT_QUOTES, 'UTF-8'); ?>">
                    <select name="lang" aria-label="Language switcher" onchange="this.form.submit()">
                        <option value="fr" <?= $lang === 'fr' ? 'selected' : ''; ?>>FR</option>
                        <option value="en" <?= $lang === 'en' ? 'selected' : ''; ?>>EN</option>
                    </select>
                </form>

                <div class="notif-wrapper" id="notif-wrapper">
                    <button type="button" id="notif-toggle" class="notif-btn" aria-label="Notifications">
                        <i class="bi bi-bell-fill"></i>
                        <?php if ($notifDisplayCount > 0): ?>
                            <span class="notif-badge"><?= (int) $notifDisplayCount; ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="notif-menu" id="notif-menu">
                        <div class="notif-menu-head">Notifications recentes</div>
                        <?php if ($notifItems === []): ?>
                            <?php if ($pendingValidationCount > 0): ?>
                                <a class="notif-item" href="?page=rapportage-coordination">
                                    <strong>Validation requise</strong>
                                    <span><?= (int) $pendingValidationCount; ?> rapport(s) en attente de validation.</span>
                                    <em>Accéder à la coordination</em>
                                </a>
                            <?php else: ?>
                                <div class="notif-item muted">Aucune notification.</div>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php foreach ($notifItems as $notif): ?>
                                <a class="notif-item js-notif-item"
                                   data-notif-id="<?= (int) ($notif['id'] ?? 0); ?>"
                                   href="<?= htmlspecialchars((string) ($notif['target_url'] ?? 'index.php?page=tableau_de_bord'), ENT_QUOTES, 'UTF-8'); ?>">
                                    <strong><?= htmlspecialchars((string) ($notif['title'] ?? 'Notification'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span><?= htmlspecialchars((string) ($notif['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <em><?= htmlspecialchars($formatNotifDate((string) ($notif['created_at'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></em>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="profile-dropdown" id="profile-dropdown-wrapper">
                    <button type="button" class="topbar-user org-topbar-user profile-dropdown-toggle" id="profile-dropdown-toggle" aria-label="Menu profil" aria-expanded="false">
                        <?php if ($orgLogoPath !== ''): ?>
                            <img
                                src="<?= htmlspecialchars($orgLogoPath, ENT_QUOTES, 'UTF-8'); ?>"
                                alt="Logo organisation"
                                class="org-avatar-img rounded-circle"
                                height="35"
                                width="35"
                            >
                        <?php else: ?>
                            <span class="org-avatar-fallback rounded-circle"><?= htmlspecialchars($orgInitials, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                        <span class="org-topbar-name"><?= htmlspecialchars($orgDisplayName, ENT_QUOTES, 'UTF-8'); ?></span>
                        <i class="bi bi-chevron-down profile-caret" aria-hidden="true"></i>
                    </button>

                    <div class="profile-dropdown-menu" id="profile-dropdown-menu">
                        <div class="profile-dropdown-head">
                            <strong><?= htmlspecialchars($orgDisplayName, ENT_QUOTES, 'UTF-8'); ?></strong>
                            <?php if ($orgEmail !== ''): ?>
                                <span><?= htmlspecialchars($orgEmail, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="?page=profil"><i class="bi bi-person-circle"></i> Mon profil</a>
                        <a href="?page=deconnexion" class="danger"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
                    </div>
                </div>
            </div>
        </header>

        <div class="container full-width">
<?php else: ?>
<div class="topnav">
    <a class="brand-wrap" href="<?= htmlspecialchars($homeLink, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Retour accueil SyDRA">
        <img class="brand-logo-img" src="<?= htmlspecialchars($logoWhitePath, ENT_QUOTES, 'UTF-8'); ?>" alt="Logo SyDRA" height="40">
    </a>

    <form class="lang-switch" action="" method="get">
        <input type="hidden" name="page" value="<?= htmlspecialchars($loaderContext, ENT_QUOTES, 'UTF-8'); ?>">
        <select name="lang" aria-label="Language switcher" onchange="this.form.submit()">
            <option value="fr" <?= $lang === 'fr' ? 'selected' : ''; ?>>FR</option>
            <option value="en" <?= $lang === 'en' ? 'selected' : ''; ?>>EN</option>
        </select>
    </form>
</div>
<div class="public-tagline">
    <span class="tagline-pill"><?= htmlspecialchars(t('intro.line1'), ENT_QUOTES, 'UTF-8'); ?></span>
    <span class="tagline-typing" data-rotate-text="1" data-rotate-messages="<?= htmlspecialchars(t('intro.line1') . '|' . t('intro.line3'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(t('intro.line3'), ENT_QUOTES, 'UTF-8'); ?></span>
</div>
<div class="container">
<?php endif; ?>
