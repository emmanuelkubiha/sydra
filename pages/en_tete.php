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

            $selectFields = ['n.id', 'n.title', 'n.message', 'n.target_url', 'n.created_at'];
            if ($hasIsRead) {
                $selectFields[] = 'n.is_read';
            }
            if ($hasReadAt) {
                $selectFields[] = 'n.read_at';
            }
            $listSql = 'SELECT ' . implode(', ', $selectFields) . '
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

$actionableTasks = [];
$isCoordinator = false;
if (isset($pdo) && $pdo instanceof PDO && is_array($authUser)) {
    $isCoordinator = in_array($roleCode, ['ADMIN', 'CLUSTER_LEADER', 'GTMP_LEAD', 'GTMP_COLEAD', 'CLUSTER_PROTECTION', 'LEAD_GTMP'], true);
    $userId = (int) ($authUser['id'] ?? 0);
    
    try {
        if ($isCoordinator) {
            // Pour les coordinateurs : rapports soumis / en attente de validation
            $taskStmt = $pdo->query('SELECT r.id, r.created_at, r.incident_title, r.location_text, r.workflow_status
                                     FROM reports r
                                     WHERE LOWER(REPLACE(REPLACE(REPLACE(COALESCE(r.workflow_status, ""), "é", "e"), "è", "e"), "ê", "e"))
                                           IN ("soumis", "submitted", "en revue", "en revision", "under_review")
                                     ORDER BY r.created_at DESC
                                     LIMIT 5');
            $coordinatorReports = $taskStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($coordinatorReports as $rep) {
                $actionableTasks[] = [
                    'type' => 'coordination_pending',
                    'id' => (int) $rep['id'],
                    'title' => 'Validation requise',
                    'message' => 'Rapport sur "' . ($rep['incident_title'] ?? 'Incident') . '" à ' . ($rep['location_text'] ?? 'lieu inconnu') . ' en attente de validation.',
                    'target_url' => '?page=rapportage-voir&id=' . $rep['id'],
                    'created_at' => $rep['created_at']
                ];
            }
        } else {
            // Pour les reporters : rapports avec demande d'informations (UNDER_REVIEW)
            $userFk = 'owner_user_id';
            $colCheck = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "reports" AND COLUMN_NAME = :col');
            $colCheck->execute(['col' => 'owner_user_id']);
            if (!$colCheck->fetchColumn()) {
                $colCheck->execute(['col' => 'reporter_user_id']);
                if ($colCheck->fetchColumn()) {
                    $userFk = 'reporter_user_id';
                } else {
                    $userFk = 'user_id';
                }
            }
            
            $taskStmt = $pdo->prepare('SELECT r.id, r.created_at, r.incident_title, r.location_text, r.workflow_status
                                     FROM reports r
                                     WHERE r.' . $userFk . ' = :uid
                                       AND LOWER(REPLACE(REPLACE(REPLACE(COALESCE(r.workflow_status, ""), "é", "e"), "è", "e"), "ê", "e"))
                                           IN ("en revue", "en revision", "under_review")
                                     ORDER BY r.created_at DESC
                                     LIMIT 5');
            $taskStmt->execute(['uid' => $userId]);
            $userReportsUnderReview = $taskStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($userReportsUnderReview as $rep) {
                $actionableTasks[] = [
                    'type' => 'info_request',
                    'id' => (int) $rep['id'],
                    'title' => 'Demande d\'informations',
                    'message' => 'Des informations complémentaires sont requises pour votre rapport sur "' . ($rep['incident_title'] ?? 'Incident') . '".',
                    'target_url' => '?page=rapportage-creer-wizar&id_brouillon=' . $rep['id'] . '&step=4',
                    'created_at' => $rep['created_at']
                ];
            }
        }
    } catch (Throwable $e) {
        // Fallback
    }
}

// Liste finale unifiée pour l'affichage
$displayNotifs = [];
foreach ($actionableTasks as $task) {
    $displayNotifs[] = [
        'id' => 0,
        'is_actionable' => true,
        'type' => $task['type'],
        'report_id' => $task['id'],
        'title' => $task['title'],
        'message' => $task['message'],
        'target_url' => $task['target_url'],
        'created_at' => $task['created_at'],
        'is_read' => 0
    ];
}

foreach (($notifItems ?? []) as $notif) {
    $isDup = false;
    if ($isCoordinator && stripos((string)($notif['title'] ?? ''), 'validation') !== false) {
        $isDup = true;
    }
    if (!$isDup) {
        $displayNotifs[] = [
            'id' => (int) ($notif['id'] ?? 0),
            'is_actionable' => false,
            'type' => 'standard',
            'title' => $notif['title'] ?? 'Notification',
            'message' => $notif['message'] ?? '',
            'target_url' => $notif['target_url'] ?? 'index.php?page=tableau_de_bord',
            'created_at' => $notif['created_at'] ?? '',
            'is_read' => isset($notif['is_read']) ? (int)$notif['is_read'] : (isset($notif['read_at']) && $notif['read_at'] !== null ? 1 : 0)
        ];
    }
}

$actionableTasksCount = count($actionableTasks);
if ($isCoordinator) {
    $notifDisplayCount = max(0, $notifCount) + max(0, $pendingValidationCount);
} else {
    $notifDisplayCount = max(0, $notifCount) + $actionableTasksCount;
}

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no, viewport-fit=cover, maximum-scale=1.0, user-scalable=0">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="icon" type="image/png" href="assets/img/sydra-logo/BLEU-PRIMARY-SYDRA-LOGO.png">
    <link rel="apple-touch-icon" href="assets/img/BLEU-PRIMARY-SYDRA-LOGO.png">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0d6efd">
    <?php $cssVersion = @filemtime(__DIR__ . '/../assets/css/style.css') ?: time(); ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= (int) $cssVersion; ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-dt@2.0.8/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.2/css/responsive.bootstrap5.min.css">
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

        <!-- Carte d'installation PWA -->
        <div class="sidebar-help-card shadow-sm rounded-4 mb-3">
            <div class="sidebar-help-icon" aria-hidden="true"><i class="fa-solid fa-mobile-screen-button"></i></div>
            <div class="sidebar-help-content">
                <strong>SyDRA Mobile</strong>
                <span>Installez SyDRA sur votre appareil.</span>
                <a href="?page=telecharger" class="sidebar-help-link">Installer l'application</a>
            </div>
        </div>

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
                <div class="d-flex align-items-center me-3 mt-1" id="network-indicator">
                    <!-- Rempli par offline_manager.js -->
                </div>

                <div class="notif-wrapper" id="notif-wrapper">
                    <button type="button" id="notif-toggle" class="notif-btn<?= $notifDisplayCount > 0 ? ' notif-btn-alert' : ''; ?>" aria-label="Notifications">
                        <i class="fa-solid fa-bell"></i>
                        <?php if ($notifDisplayCount > 0): ?>
                            <span class="notif-badge"><?= (int) $notifDisplayCount; ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="notif-menu" id="notif-menu">
                        <div class="notif-menu-head">Notifications récentes</div>
                        <?php if ($displayNotifs === []): ?>
                            <div class="notif-empty">
                                <div class="notif-empty-icon">
                                    <i class="fa-regular fa-bell-slash"></i>
                                </div>
                                <h6 class="notif-empty-title">Aucune notification</h6>
                                <p class="notif-empty-desc mb-0">Vous êtes à jour !</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($displayNotifs as $notif): ?>
                                <?php
                                $notifTitle = (string) ($notif['title'] ?? 'Notification');
                                $notifMessage = (string) ($notif['message'] ?? '');
                                $isActionable = !empty($notif['is_actionable']);
                                
                                $iconClass = 'fa-solid fa-circle-info';
                                $themeClass = 'notif-icon-circle-primary';
                                
                                if (($notif['type'] ?? '') === 'coordination_pending') {
                                    $iconClass = 'fa-solid fa-file-signature';
                                    $themeClass = 'notif-icon-circle-warning';
                                } elseif (($notif['type'] ?? '') === 'info_request') {
                                    $iconClass = 'fa-solid fa-circle-question';
                                    $themeClass = 'notif-icon-circle-danger';
                                } else {
                                    $isWarning = stripos($notifTitle, 'validation') !== false || stripos($notifTitle, 'refus') !== false || stripos($notifTitle, 'annul') !== false;
                                    $isDanger = stripos($notifTitle, 'supprim') !== false || stripos($notifTitle, 'erreur') !== false || stripos($notifTitle, 'alert') !== false;
                                    if ($isWarning) {
                                        $iconClass = 'fa-solid fa-triangle-exclamation';
                                        $themeClass = 'notif-icon-circle-warning';
                                    } elseif ($isDanger) {
                                        $iconClass = 'fa-solid fa-circle-exclamation';
                                        $themeClass = 'notif-icon-circle-danger';
                                    }
                                }

                                $isUnread = (int)($notif['is_read'] ?? 0) === 0;
                                ?>
                                <a class="notif-item js-notif-item<?= $isActionable ? ' notif-item-actionable' : ''; ?><?= ($notif['type'] ?? '') === 'coordination_pending' ? ' notif-item-coordination' : ''; ?><?= ($notif['type'] ?? '') === 'info_request' ? ' notif-item-info-request' : ''; ?><?= $isUnread ? ' notif-item-unread' : ' notif-item-read'; ?>"
                                   data-notif-id="<?= (int) ($notif['id'] ?? 0); ?>"
                                   href="<?= htmlspecialchars((string) ($notif['target_url'] ?? 'index.php?page=tableau_de_bord'), ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="notif-icon-circle <?= $themeClass; ?>">
                                        <i class="<?= $iconClass; ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between gap-1">
                                            <strong class="notif-title"><?= htmlspecialchars($notifTitle, ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <?php if ($isActionable): ?>
                                                <span class="badge bg-danger text-white px-2 py-0.5 rounded-pill" style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px;">Action</span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="notif-message"><?= htmlspecialchars($notifMessage, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php if ($isActionable): ?>
                                            <span class="notif-action-link">Traiter le rapport <i class="fa-solid fa-arrow-right ms-1"></i></span>
                                        <?php else: ?>
                                            <span class="notif-date"><?= htmlspecialchars($formatNotifDate((string) ($notif['created_at'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isUnread && !$isActionable): ?>
                                        <span class="notif-unread-dot" title="Non lu"></span>
                                    <?php endif; ?>
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
                        <a href="?page=deconnexion" class="danger" onclick="return confirm('Voulez-vous vraiment vous déconnecter ?');"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
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


</div>
<div class="public-tagline">
    <span class="tagline-pill"><?= htmlspecialchars(t('intro.line1'), ENT_QUOTES, 'UTF-8'); ?></span>
    <span class="tagline-typing" data-rotate-text="1" data-rotate-messages="<?= htmlspecialchars(t('intro.line1') . '|' . t('intro.line3'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(t('intro.line3'), ENT_QUOTES, 'UTF-8'); ?></span>
</div>
<div class="container">
<?php endif; ?>
