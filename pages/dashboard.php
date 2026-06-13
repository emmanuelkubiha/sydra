<?php
/**
 * ROLE DU FICHIER:
 * - Afficher le tableau de bord principal SyDRA (KPIs, activité, carte, graphiques).
 * - Proposer des raccourcis d'action selon le rôle connecté.
 * - Déclencher de manière discrète le script d'auto-rejet des alertes expirées
 *   pour les rôles de décision, sans perturber l'expérience utilisateur.
 */
/** @var array|null $authUser */
/** @var array<int, array<string, mixed>> $dashboardKpis */
/** @var array<int, array<string, mixed>> $dashboardRecentReports */
/** @var array<int, array<string, mixed>> $dashboardMapAlerts */

$role = strtoupper((string) ($authUser['role'] ?? $authUser['role_code'] ?? 'REPORTER'));
// Active les contrôles opérationnels avancés pour les profils décisionnels.
$isDecisionRole = in_array($role, ['ADMIN', 'CLUSTER_LEADER', 'LEAD_GTMP', 'GTMP_LEAD', 'CLUSTER_PROTECTION'], true);
$displayName = trim((string) ($authUser['organization_name'] ?? $authUser['full_name'] ?? 'Organisation'));
$subtitle = $isDecisionRole
    ? 'Espace de coordination GTMP'
    : 'Votre espace de rapportage';

$heroPrimaryHref = $isDecisionRole ? '?page=rapportage-admin-list' : '?page=rapportage-creer-AI';
$heroPrimaryLabel = $isDecisionRole ? 'Gérer les alertes' : 'Nouvelle alerte';
$heroPrimaryIcon = $isDecisionRole ? 'bi bi-kanban-fill' : 'bi bi-plus-circle-fill';
$heroSecondaryHref = $isDecisionRole ? '?page=rapportage-liste-user' : '?page=rapportage';
$heroSecondaryLabel = $isDecisionRole ? 'Consulter mes alertes' : 'Commencer le rapportage';
$heroSecondaryIcon = $isDecisionRole ? 'bi bi-folder2-open' : 'bi bi-compass';
$heroTertiaryHref = '?page=stats';
$heroTertiaryLabel = 'Statistiques avancées';
$heroTertiaryIcon = 'bi bi-graph-up-arrow';

$tips = [
    'Un rapport Flash doit rester concis, factuel et daté.',
    'Utilisez le mode IA pour structurer rapidement vos notes longues.',
    'Vérifiez vos coordonnées GPS avant soumission.',
    'Ajoutez un commentaire explicite lors d\'une demande de complément.',
    'Mettez à jour votre profil organisationnel pour améliorer le suivi.',
];
// Rotation pseudo-aléatoire d'un conseil utile à l'ouverture du dashboard.
$tipOfDay = $tips[random_int(0, count($tips) - 1)];

$mapPayload = json_encode($dashboardMapAlerts ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
if (!is_string($mapPayload)) {
    $mapPayload = '[]';
}

$statusNormalized = static function (string $status): string {
    $s = strtolower(trim($status));
    return str_replace(['é', 'è', 'ê'], 'e', $s);
};

// Le hook d'auto-rejet est réservé aux rôles de validation/revue.
$autoRejectEnabled = $isDecisionRole;
$draftSummaryJson = json_encode($userDraftSummary ?? null, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
if (!is_string($draftSummaryJson)) {
    $draftSummaryJson = 'null';
}
$csrfTokenDashboard = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
?>

<style>
.dashboard-shell {
    display: grid;
    gap: 1.5rem;
    font-family: 'Poppins', 'Inter', sans-serif;
}

.dashboard-hero {
    border: none;
    border-radius: 20px;
    padding: 2.2rem 2rem;
    background: 
        radial-gradient(circle at 95% -15%, rgba(99, 102, 241, 0.15), transparent 45%),
        linear-gradient(135deg, #f8faff 0%, #e6f0ff 100%);
    box-shadow: 0 10px 30px rgba(0,0,0,0.03);
}

.dashboard-hero h1 {
    font-weight: 800;
    color: #0f172a;
    font-size: 2.1rem;
}

.dashboard-kpi-card {
    border: none;
    border-radius: 16px;
    background: #ffffff;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    transition: transform 0.2s, box-shadow 0.2s;
}

.dashboard-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.07);
}

.dashboard-tip-card {
    border: none;
    border-radius: 16px;
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    box-shadow: 0 4px 20px rgba(245, 158, 11, 0.05);
    padding: 1.5rem !important;
}

.dashboard-activity-card {
    border: none;
    border-radius: 16px;
    background: #ffffff;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
}

.dashboard-activity-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 0.75rem;
}

.dashboard-activity-list li {
    border: 1px solid #f1f5f9;
    border-radius: 12px;
    padding: 0.9rem 1rem;
    background: #ffffff;
    transition: all 0.25s ease;
}

.dashboard-activity-list li:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 22px rgba(0, 91, 187, 0.06);
    border-color: #cbd5e1;
}

.dashboard-ops-card {
    border: none;
    border-radius: 16px;
    background: #ffffff;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
}

.dashboard-map-shell {
    height: 380px;
    border-radius: 14px;
    border: 1px solid #cbd5e1;
    overflow: hidden;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
}

.dashboard-chart-shell {
    height: 320px;
}

.dashboard-territory-shell {
    height: 240px;
}

.dashboard-map-empty {
    position: absolute;
    inset: 0;
    display: none;
    align-items: center;
    justify-content: center;
    background: rgba(248, 251, 255, 0.88);
    color: #475569;
    font-weight: 600;
    border-radius: 14px;
}

#dashboard-operational-map .leaflet-popup-content-wrapper {
    border-radius: 16px;
    padding: 0;
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.15);
    overflow: hidden;
}
#dashboard-operational-map .leaflet-popup-content { margin: 0; }
#dashboard-operational-map .leaflet-popup-tip { background: #ffffff; }

.dashboard-popup-card { min-width: 280px; max-width: 320px; background: #ffffff; }
.dashboard-popup-head {
    padding: 12px 14px;
    border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(130deg, #f8fbff 0%, #eef5ff 100%);
}
.dashboard-popup-title { font-size: 13px; font-weight: 800; color: #0f172a; margin: 0; }
.dashboard-popup-subtitle { font-size: 11px; color: #64748b; margin-top: 2px; }
.dashboard-popup-badges { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
.dashboard-popup-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    border-radius: 999px;
    padding: 4px 10px;
    font-size: 10px;
    font-weight: 700;
    line-height: 1;
}
.dashboard-popup-badge.severity-critical { background: #fee2e2; color: #b91c1c; }
.dashboard-popup-badge.severity-high { background: #ffedd5; color: #c2410c; }
.dashboard-popup-badge.severity-medium { background: #fef3c7; color: #a16207; }
.dashboard-popup-badge.severity-low { background: #dbeafe; color: #1d4ed8; }
.dashboard-popup-badge.status-approved { background: #dcfce7; color: #166534; }
.dashboard-popup-badge.status-review { background: #fef3c7; color: #a16207; }
.dashboard-popup-badge.status-rejected { background: #fee2e2; color: #b91c1c; }
.dashboard-popup-badge.status-submitted { background: #dbeafe; color: #1d4ed8; }
.dashboard-popup-badge.status-draft { background: #e2e8f0; color: #334155; }
.dashboard-popup-body { padding: 12px 14px; font-size: 12px; color: #334155; }
.dashboard-popup-meta-row { display: flex; justify-content: space-between; gap: 8px; margin-bottom: 6px; }
.dashboard-popup-meta-label { color: #64748b; font-weight: 600; white-space: nowrap; }
.dashboard-popup-meta-value { color: #0f172a; text-align: right; font-weight: 600; }
.dashboard-popup-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    width: 100%;
    margin-top: 10px;
    border-radius: 12px;
    border: 1px solid #005BBB;
    background: #005BBB;
    color: #ffffff !important;
    padding: 10px 12px;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.2s ease;
}
.dashboard-popup-btn:hover {
    background: #004ea3;
    border-color: #004ea3;
    color: #ffffff !important;
    box-shadow: 0 6px 16px rgba(0, 91, 187, 0.2);
    transform: translateY(-1px);
    text-decoration: none;
}
</style>

<div class="dashboard-shell">
    <section class="dashboard-hero">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="mb-1">Bienvenue sur SyDRA, <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="text-secondary mb-0"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= htmlspecialchars($heroPrimaryHref, ENT_QUOTES, 'UTF-8'); ?>"
                   class="btn btn-primary rounded-pill px-4 shadow-sm<?= !$isDecisionRole ? ' js-create-alert-link' : ''; ?>"
                   <?= !$isDecisionRole ? 'data-check-draft="1"' : ''; ?>>
                    <i class="<?= htmlspecialchars($heroPrimaryIcon, ENT_QUOTES, 'UTF-8'); ?> me-2"></i><?= htmlspecialchars($heroPrimaryLabel, ENT_QUOTES, 'UTF-8'); ?>
                </a>
                <a href="<?= htmlspecialchars($heroSecondaryHref, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-primary rounded-pill px-4 shadow-sm">
                    <i class="<?= htmlspecialchars($heroSecondaryIcon, ENT_QUOTES, 'UTF-8'); ?> me-2"></i><?= htmlspecialchars($heroSecondaryLabel, ENT_QUOTES, 'UTF-8'); ?>
                </a>
                <?php if ($isDecisionRole): ?>
                <a href="<?= htmlspecialchars($heroTertiaryHref, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                    <i class="<?= htmlspecialchars($heroTertiaryIcon, ENT_QUOTES, 'UTF-8'); ?> me-2"></i><?= htmlspecialchars($heroTertiaryLabel, ENT_QUOTES, 'UTF-8'); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="row g-3">
        <?php 
        foreach (($dashboardKpis ?? []) as $index => $kpi): 
            // Mappage de couleur par KPI
            $kpiColors = [
                0 => ['bg' => '#f1f5f9', 'text' => '#475569'], // Dossier / Calendrier
                1 => ['bg' => '#fef3c7', 'text' => '#d97706'], // En attente (Warning)
                2 => ['bg' => '#d1fae5', 'text' => '#059669'], // Validés (Success)
                3 => ['bg' => '#f3e8ff', 'text' => '#7e22ce'], // IA (Purple)
            ];
            $color = $kpiColors[$index] ?? ['bg' => '#f1f5f9', 'text' => '#475569'];
        ?>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="dashboard-kpi-card h-100 p-4 border-0">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background-color: <?= $color['bg']; ?>; color: <?= $color['text']; ?>;">
                            <i class="fa-solid <?= htmlspecialchars((string) ($kpi['icon'] ?? 'fa-chart-simple'), ENT_QUOTES, 'UTF-8'); ?> fs-5"></i>
                        </span>
                        <strong class="fs-2 fw-bold text-dark mb-0" style="font-family: 'Inter', sans-serif;"><?= htmlspecialchars((string) ($kpi['value'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>
                    <p class="mb-0 text-secondary fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.75rem;"><?= htmlspecialchars((string) ($kpi['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="row g-3">
        <div class="col-lg-5">
            <div class="dashboard-tip-card p-4 mb-3 border-0">
                <h2 class="h5 mb-2 fw-bold text-warning-emphasis"><i class="fa-solid fa-lightbulb me-2"></i>Conseil du jour</h2>
                <p class="mb-0 text-dark-emphasis small" style="line-height: 1.5; font-weight: 500;"><?= htmlspecialchars($tipOfDay, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="dashboard-activity-card p-4 border-0">
                <h2 class="h5 mb-3 fw-bold text-dark"><i class="fa-solid fa-map-location-dot me-2 text-primary"></i>Impact territorial</h2>
                <div class="dashboard-territory-shell">
                    <canvas id="dashboard-territory-impact-chart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="dashboard-activity-card h-100 p-4 border-0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0 fw-bold text-dark"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Activités récentes</h2>
                    <span class="badge rounded-pill bg-light text-secondary border px-3 py-2" style="font-size: 0.75rem;">5 derniers rapports</span>
                </div>

                <?php if (($dashboardRecentReports ?? []) === []): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fa-solid fa-folder-open fs-2 mb-2"></i>
                        <p class="mb-0 small">Aucune activité récente disponible.</p>
                    </div>
                <?php else: ?>
                    <ul class="dashboard-activity-list">
                        <?php foreach ($dashboardRecentReports as $report): ?>
                            <?php
                            $reportId = (int) ($report['id'] ?? 0);
                            $status = (string) ($report['workflow_status'] ?? 'Brouillon');
                            $normalizedStatus = $statusNormalized($status);
                            $isDraft = in_array($normalizedStatus, ['brouillon', 'draft'], true);
                            $ownerUserId = (int) ($report['owner_user_id'] ?? 0);
                            $currentUserId = (int) ($authUser['id'] ?? 0);
                            $isOwner = $ownerUserId > 0 && $ownerUserId === $currentUserId;
                            $canViewDetails = $isDecisionRole || $isOwner;
                            $detailHref = $isDraft
                                ? ('?page=rapportage-creer-wizar&id_brouillon=' . $reportId)
                                : ('?page=rapportage-details&id=' . $reportId);
                            $onclick = $isDraft
                                ? 'return confirmDraftResume(event);'
                                : '';
                            ?>
                            <li>
                                <a href="<?= htmlspecialchars($detailHref, ENT_QUOTES, 'UTF-8'); ?>"
                                   class="text-decoration-none text-reset d-block js-guard-report-access"
                                   data-can-view-details="<?= $canViewDetails ? '1' : '0'; ?>"
                                   <?= $onclick !== '' ? ('onclick="' . htmlspecialchars($onclick, ENT_QUOTES, 'UTF-8') . '"') : ''; ?>>
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-1 mb-1">
                                        <strong class="text-dark">#<?= $reportId; ?> • <?= htmlspecialchars((string) ($report['report_type'] ?? 'FLASH'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <small class="text-muted small"><?= htmlspecialchars((string) ($report['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
                                    </div>
                                    <div class="small text-secondary d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <span>
                                            <i class="fa-solid fa-building me-1" style="font-size: 0.8rem;"></i> <?= htmlspecialchars((string) ($report['organization_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                            • <i class="fa-solid fa-location-dot me-1" style="font-size: 0.8rem;"></i> <?= htmlspecialchars((string) ($report['location_text'] ?? 'Non précisée'), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                        <?php if ($isDraft): ?>
                                            <span class="badge rounded-pill" style="background-color: #f1f5f9; color: #64748b; font-weight: 600; padding: 4px 8px;">Brouillon</span>
                                        <?php else: ?>
                                            <?php 
                                            // Badges de statut
                                            $stName = strtolower(str_replace(['é', 'è', 'ê'], 'e', trim($status)));
                                            $badgeStyle = "background-color: #f1f5f9; color: #64748b;";
                                            if ($stName === 'soumis' || $stName === 'submitted' || $stName === 'en revision') {
                                                $badgeStyle = "background-color: #fef3c7; color: #d97706;";
                                            } elseif ($stName === 'valide' || $stName === 'approuve' || $stName === 'publie') {
                                                $badgeStyle = "background-color: #d1fae5; color: #059669;";
                                            } elseif ($stName === 'rejete' || $stName === 'rejected') {
                                                $badgeStyle = "background-color: #fee2e2; color: #dc2626;";
                                            }
                                            ?>
                                            <span class="badge rounded-pill" style="<?= $badgeStyle; ?> font-weight: 600; padding: 4px 8px;"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                                <?php if ($isDraft && $isOwner): ?>
                                    <div class="mt-2">
                                        <a href="?page=rapportage-creer-wizar&id_brouillon=<?= $reportId; ?>"
                                           class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 small"
                                           onclick="return confirmDraftResume(event);"><i class="fa-solid fa-play me-1"></i> Continuer la saisie</a>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="dashboard-ops-card p-4 border-0">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0 fw-bold text-dark"><i class="fa-solid fa-map-location-dot me-2 text-primary"></i>Aperçu Opérationnel</h2>
            <span class="badge rounded-pill bg-light text-secondary border px-3 py-2" style="font-size: 0.75rem;"><?= count($dashboardMapAlerts ?? []); ?> incident(s) géolocalisé(s)</span>
        </div>
        <div class="row g-3">
            <div class="col-xl-8">
                <div class="position-relative">
                    <div id="dashboard-operational-map"
                         class="dashboard-map-shell"
                         data-alerts='<?= htmlspecialchars($mapPayload, ENT_QUOTES, 'UTF-8'); ?>'></div>
                    <div class="dashboard-map-empty" id="dashboard-map-empty">Aucun incident géolocalisable sur la période.</div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="dashboard-activity-card h-100 p-4 border-0 shadow-none bg-light" style="border-radius: 14px;">
                    <h3 class="h6 mb-3 fw-bold text-dark"><i class="fa-solid fa-chart-pie me-2 text-primary"></i>Gravité des incidents</h3>
                    <div class="dashboard-chart-shell">
                        <canvas id="dashboard-severity-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
(function () {
    var USER_DRAFT_SUMMARY = <?= $draftSummaryJson; ?>;
    var CSRF_TOKEN = '<?= $csrfTokenDashboard; ?>';

    // Confirmation UX dédiée à la reprise des brouillons incomplets.
    window.confirmDraftResume = function (event) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            event.preventDefault();
            var link = event.currentTarget;
            window.Swal.fire({
                icon: 'question',
                title: 'Continuer ce brouillon ?',
                text: 'Voulez-vous continuer la saisie de ce brouillon ?',
                showCancelButton: true,
                confirmButtonText: 'Oui, continuer',
                cancelButtonText: 'Annuler',
                confirmButtonColor: '#005BBB'
            }).then(function (result) {
                if (result.isConfirmed && link && link.href) {
                    window.location.href = link.href;
                }
            });
            return false;
        }
        return window.confirm('Voulez-vous continuer la saisie de ce brouillon ?');
    };

    // Initialisation unique des composants carte/charts du dashboard.
    function boot() {
        var mapEl = document.getElementById('dashboard-operational-map');
        var mapEmptyEl = document.getElementById('dashboard-map-empty');
        var autoRejectEnabled = <?= $autoRejectEnabled ? 'true' : 'false'; ?>;
        var currentUserId = <?= (int) ($authUser['id'] ?? 0); ?>;
        var isDecisionRoleClient = <?= $isDecisionRole ? 'true' : 'false'; ?>;

        function showDeniedDetailsAccess() {
            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    icon: 'warning',
                    title: 'Accès limité',
                    text: 'Vous n\'etes pas autorise a voir plus d\'informations.',
                    confirmButtonColor: '#005BBB'
                });
                return;
            }
            window.alert('Vous n\'etes pas autorise a voir plus d\'informations.');
        }

        function postDraftAction(action, extraData) {
            var formData = new FormData();
            formData.append('action', action);
            formData.append('csrf', CSRF_TOKEN);
            var payload = extraData || {};
            Object.keys(payload).forEach(function (key) {
                formData.append(key, String(payload[key]));
            });
            return fetch('?page=tableau_de_bord', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (res) { return res.json(); });
        }

        function formatDraftDate(rawDate) {
            var date = new Date(String(rawDate || '').replace(' ', 'T'));
            if (Number.isNaN(date.getTime())) {
                return String(rawDate || 'date inconnue');
            }
            return date.toLocaleString('fr-FR');
        }

        function bindDraftCollisionLinks() {
            if (isDecisionRoleClient) {
                return;
            }
            document.querySelectorAll('.js-create-alert-link[data-check-draft="1"]').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    event.preventDefault();

                    postDraftAction('check_user_draft_collision', {})
                        .then(function (data) {
                            if (!data || data.ok !== true || !data.has_draft || !data.draft) {
                                window.location.href = link.getAttribute('href') || '?page=rapportage-creer-AI';
                                return;
                            }

                            var draft = data.draft || {};
                            var draftId = Number(draft.id || 0);
                            var draftIncident = String(draft.incident_type || 'Incident');
                            var draftCreatedAt = formatDraftDate(draft.created_at || '');

                            if (!(window.Swal && typeof window.Swal.fire === 'function')) {
                                window.location.href = '?page=rapportage-creer-wizar&id_brouillon=' + draftId;
                                return;
                            }

                            window.Swal.fire({
                                icon: 'warning',
                                title: 'Vous avez déjà un brouillon en cours',
                                text: 'Vous ne pouvez avoir qu\'un seul rapport en brouillon. Veuillez terminer ou supprimer le brouillon existant intitulé "' + draftIncident + '" du ' + draftCreatedAt + '.',
                                showCancelButton: true,
                                showDenyButton: true,
                                confirmButtonText: 'Continuer mon brouillon',
                                denyButtonText: 'Supprimer l\'ancien',
                                cancelButtonText: 'Annuler',
                                confirmButtonColor: '#005BBB',
                                denyButtonColor: '#dc2626'
                            }).then(function (result) {
                                if (result.isConfirmed) {
                                    window.location.href = '?page=rapportage-creer-wizar&id_brouillon=' + draftId;
                                    return;
                                }
                                if (result.isDenied) {
                                    window.Swal.fire({
                                        icon: 'question',
                                        title: 'Confirmation',
                                        text: 'Attention, l\'ancien brouillon sera définitivement perdu. Continuer ?',
                                        showCancelButton: true,
                                        confirmButtonText: 'Oui, supprimer',
                                        cancelButtonText: 'Annuler',
                                        confirmButtonColor: '#dc2626'
                                    }).then(function (confirmDelete) {
                                        if (!confirmDelete.isConfirmed) {
                                            return;
                                        }
                                        postDraftAction('delete_existing_draft', { draft_id: draftId })
                                            .then(function (deleteResult) {
                                                if (!deleteResult || deleteResult.ok !== true) {
                                                    throw new Error((deleteResult && deleteResult.message) ? deleteResult.message : 'Suppression impossible.');
                                                }
                                                window.location.href = link.getAttribute('href') || '?page=rapportage-creer-AI';
                                            })
                                            .catch(function (err) {
                                                window.Swal.fire({
                                                    icon: 'error',
                                                    title: 'Suppression impossible',
                                                    text: err.message || 'Impossible de supprimer le brouillon.'
                                                });
                                            });
                                    });
                                }
                            });
                        })
                        .catch(function () {
                            window.location.href = link.getAttribute('href') || '?page=rapportage-creer-AI';
                        });
                });
            });
        }

        function remindDraftIfNeeded() {
            if (isDecisionRoleClient || !USER_DRAFT_SUMMARY || !USER_DRAFT_SUMMARY.id) {
                return;
            }
            if (!(window.Swal && typeof window.Swal.fire === 'function')) {
                return;
            }

            var reminderKey = 'sydraDashboardDraftReminderShown';
            try {
                if (window.sessionStorage.getItem(reminderKey) === '1') {
                    return;
                }
                window.sessionStorage.setItem(reminderKey, '1');
            } catch (e) {
                // stockage indisponible
            }

            window.Swal.fire({
                icon: 'info',
                title: 'Brouillon en attente',
                text: 'Vous avez un rapportage en brouillon non soumis. Voulez-vous le continuer maintenant ?',
                showCancelButton: true,
                confirmButtonText: 'Oui',
                cancelButtonText: 'Plus tard',
                confirmButtonColor: '#005BBB'
            }).then(function (result) {
                if (result.isConfirmed) {
                    window.location.href = '?page=rapportage-creer-wizar&id_brouillon=' + Number(USER_DRAFT_SUMMARY.id || 0);
                }
            });
        }

        document.addEventListener('click', function (event) {
            var target = event.target;
            if (!(target instanceof Element)) {
                return;
            }
            var link = target.closest('a.js-guard-report-access[data-can-view-details]');
            if (!link) {
                return;
            }
            if (String(link.getAttribute('data-can-view-details') || '0') === '1') {
                return;
            }
            event.preventDefault();
            showDeniedDetailsAccess();
        });

        bindDraftCollisionLinks();
        remindDraftIfNeeded();

        if (!mapEl) {
            return;
        }
        // Empêche toute double-initialisation du moteur cartographique.
        if (mapEl.dataset.dashboardMapReady === '1') {
            return;
        }
        if (!window.L) {
            window.setTimeout(boot, 80);
            return;
        }
        mapEl.dataset.dashboardMapReady = '1';

    // Normalise les libellés pour un matching robuste (accents/casse).
    function normalizeText(value) {
        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function resolveLocationFromText(rawLocation) {
        var cityCoords = {
            bukavu: [-2.5099, 28.8428],
            uvira: [-3.4067, 29.1458],
            goma: [-1.6792, 29.2228],
            minova: [-2.1547, 28.9891],
            kalehe: [-2.2581, 28.6765],
            idjwi: [-2.1198, 28.9961],
            walungu: [-2.7082, 28.6133],
            kabare: [-2.4741, 28.7619],
            shabunda: [-2.6978, 27.3358],
            fizi: [-4.3014, 28.9448],
            baraka: [-4.0976, 29.0958],
            kamituga: [-3.0509, 28.1858],
            kindu: [-2.9508, 25.9464],
            butembo: [-0.1408, 29.2903],
            kalima: [-2.6147, 26.5622]
        };

        var location = normalizeText(rawLocation);
        if (location === '') {
            return null;
        }
        for (var city in cityCoords) {
            if (Object.prototype.hasOwnProperty.call(cityCoords, city) && location.indexOf(city) >= 0) {
                return cityCoords[city];
            }
        }
        return null;
    }

    // Convertit la gravité brute (id/texte) en niveau métier unifié.
    function severityBucket(item) {
        var n = Number(item && item.severity_id ? item.severity_id : 0);
        if (n >= 4) { return 'Critique'; }
        if (n === 3) { return 'Élevée'; }
        if (n === 2) { return 'Moyenne'; }

        var urgency = normalizeText(item && item.urgency_level ? item.urgency_level : '');
        if (urgency.indexOf('crit') >= 0) { return 'Critique'; }
        if (urgency.indexOf('ele') >= 0 || urgency.indexOf('high') >= 0) { return 'Élevée'; }
        if (urgency.indexOf('moy') >= 0 || urgency.indexOf('medium') >= 0) { return 'Moyenne'; }
        return 'Faible';
    }

    function severityColor(item) {
        var bucket = severityBucket(item);
        if (bucket === 'Critique') { return '#dc2626'; }
        if (bucket === 'Élevée') { return '#ea580c'; }
        if (bucket === 'Moyenne') { return '#ca8a04'; }
        return '#005BBB';
    }

    function severityMeta(item) {
        var bucket = severityBucket(item);
        if (bucket === 'Critique') {
            return { label: 'Critique', klass: 'severity-critical', icon: 'fa-solid fa-triangle-exclamation' };
        }
        if (bucket === 'Élevée') {
            return { label: 'Élevée', klass: 'severity-high', icon: 'fa-solid fa-arrow-trend-up' };
        }
        if (bucket === 'Moyenne') {
            return { label: 'Moyenne', klass: 'severity-medium', icon: 'fa-solid fa-chart-line' };
        }
        return { label: 'Faible', klass: 'severity-low', icon: 'fa-solid fa-circle-info' };
    }

    // Mappe les statuts vers badges/icônes lisibles dans les popups carte.
    function statusMeta(status) {
        var n = normalizeText(status);
        if (n.indexOf('approuve') >= 0 || n.indexOf('valide') >= 0 || n.indexOf('publie') >= 0) {
            return { label: 'Approuvé', klass: 'status-approved', icon: 'fa-solid fa-circle-check' };
        }
        if (n.indexOf('revision') >= 0 || n.indexOf('revue') >= 0 || n.indexOf('demande') >= 0) {
            return { label: 'En revue', klass: 'status-review', icon: 'fa-solid fa-hourglass-half' };
        }
        if (n.indexOf('rejet') >= 0) {
            return { label: 'Rejeté', klass: 'status-rejected', icon: 'fa-solid fa-circle-xmark' };
        }
        if (n.indexOf('soumis') >= 0) {
            return { label: 'Soumis', klass: 'status-submitted', icon: 'fa-solid fa-paper-plane' };
        }
        return { label: 'Brouillon', klass: 'status-draft', icon: 'fa-solid fa-pen-to-square' };
    }

    var map = window.L.map(mapEl, {
        zoomControl: false,
        dragging: false,
        scrollWheelZoom: false,
        doubleClickZoom: false,
        boxZoom: false,
        touchZoom: false,
        keyboard: false,
        minZoom: 6,
        maxZoom: 8,
        maxBounds: [[-5.0, 25.0], [0.0, 29.5]],
        maxBoundsViscosity: 1.0
    }).setView([-3.0, 27.5], 7);

    window.L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        maxZoom: 18,
        attribution: '&copy; OpenStreetMap &copy; CARTO'
    }).addTo(map);

    var markersLayer = window.L.layerGroup().addTo(map);
    var severityChart = null;
    var territoryImpactChart = null;
    var dashboardOverviewView = {
        center: map.getCenter(),
        zoom: map.getZoom()
    };

    map._sydraOpeningPopup = false;
    map.on('popupclose', function () {
        if (map._sydraOpeningPopup || !dashboardOverviewView.center) {
            return;
        }

        var targetCenter = dashboardOverviewView.center;
        var targetZoom = dashboardOverviewView.zoom;

        if (targetCenter && typeof targetZoom === 'number') {
            map.flyTo(targetCenter, targetZoom, {
                animate: true,
                duration: 0.45
            });
        }
    });

    var alerts = [];
    try {
        alerts = JSON.parse(mapEl.getAttribute('data-alerts') || '[]');
    } catch (e) {
        alerts = [];
    }

    // Rend de bout en bout la vue opérationnelle (carte + graphiques).
    function renderOperational(rawAlerts) {
        var list = Array.isArray(rawAlerts) ? rawAlerts : [];
        markersLayer.clearLayers();

        var points = [];
        var severityCounts = { 'Critique': 0, 'Élevée': 0, 'Moyenne': 0, 'Faible': 0 };
        var territoryCounts = {};
        list.forEach(function (item) {
            var bucket = severityBucket(item);
            severityCounts[bucket] = (severityCounts[bucket] || 0) + 1;

            var territoryLabel = String(item.territory || item.location_text || item.locality || item.province || 'Non précisée').trim();
            if (territoryLabel === '') {
                territoryLabel = 'Non précisée';
            }
            territoryCounts[territoryLabel] = (territoryCounts[territoryLabel] || 0) + 1;

            var lat = Number(item.gps_lat || 0);
            var lng = Number(item.gps_lng || 0);
            var coords = null;
            if (!Number.isNaN(lat) && !Number.isNaN(lng) && lat !== 0 && lng !== 0) {
                coords = [lat, lng];
            } else {
                coords = resolveLocationFromText(item.location_text || item.locality || item.province || '');
            }
            if (!coords) {
                return;
            }

            var color = severityColor(item);
            var reportId = Number(item.id || 0);
            var severity = severityMeta(item);
            var status = statusMeta(item.workflow_status || 'Brouillon');
            var typeLabel = String(item.report_type || 'FLASH');
            var orgName = String(item.organization_name || 'Organisation');
            var dateLabel = String(item.created_at || 'Date non précisée');
            var locationLabel = String(item.location_text || item.locality || item.province || 'Non précisée');
            var ownerUserId = Number(item.owner_user_id || 0);
            var canViewDetails = isDecisionRoleClient || (ownerUserId > 0 && ownerUserId === currentUserId);
            var marker = window.L.circleMarker(coords, {
                radius: 8,
                color: color,
                weight: 2,
                fillColor: color,
                fillOpacity: 0.78
            }).addTo(markersLayer);

            marker.bindPopup(
                '<div class="dashboard-popup-card">'
                + '<div class="dashboard-popup-head">'
                + '<p class="dashboard-popup-title">Incident #' + reportId + ' - ' + escapeHtml(typeLabel) + '</p>'
                + '<div class="dashboard-popup-subtitle">' + escapeHtml(orgName) + '</div>'
                + '<div class="dashboard-popup-badges">'
                + '<span class="dashboard-popup-badge ' + severity.klass + '"><i class="' + severity.icon + '"></i>' + severity.label + '</span>'
                + '<span class="dashboard-popup-badge ' + status.klass + '"><i class="' + status.icon + '"></i>' + status.label + '</span>'
                + '</div>'
                + '</div>'
                + '<div class="dashboard-popup-body">'
                + '<div class="dashboard-popup-meta-row"><span class="dashboard-popup-meta-label">Date</span><span class="dashboard-popup-meta-value">' + escapeHtml(dateLabel) + '</span></div>'
                + '<div class="dashboard-popup-meta-row"><span class="dashboard-popup-meta-label">Localisation</span><span class="dashboard-popup-meta-value">' + escapeHtml(locationLabel) + '</span></div>'
                + '<a class="dashboard-popup-btn js-guard-report-access" data-can-view-details="' + (canViewDetails ? '1' : '0') + '" href="?page=rapportage-voir&id=' + reportId + '"><i class="fa-solid fa-eye"></i>Voir l\'incident</a>'
                + '</div>'
                + '</div>',
                {
                    autoPan: true,
                    autoPanPaddingTopLeft: [18, 90],
                    autoPanPaddingBottomRight: [18, 24],
                    keepInView: true
                }
            );

            marker.on('click', function () {
                map._sydraOpeningPopup = true;
                window.setTimeout(function () {
                    map._sydraOpeningPopup = false;
                }, 650);

                var flyZoom = 8;
                if (typeof map.getMaxZoom === 'function') {
                    flyZoom = Math.min(flyZoom, Number(map.getMaxZoom() || flyZoom));
                }

                map.flyTo(marker.getLatLng(), flyZoom, {
                    animate: true,
                    duration: 0.5
                });
            });

            points.push(coords);
        });

        if (mapEmptyEl) {
            mapEmptyEl.style.display = points.length === 0 ? 'flex' : 'none';
        }

        if (points.length > 0) {
            map.fitBounds(window.L.latLngBounds(points).pad(0.18));
            dashboardOverviewView = {
                center: map.getCenter(),
                zoom: map.getZoom()
            };
        } else {
            map.setView([-3.0, 27.5], 7);
            dashboardOverviewView = {
                center: map.getCenter(),
                zoom: map.getZoom()
            };
        }

        var chartCtx = document.getElementById('dashboard-severity-chart');
        if (window.Chart && chartCtx) {
            var chartData = [
                severityCounts['Critique'] || 0,
                severityCounts['Élevée'] || 0,
                severityCounts['Moyenne'] || 0,
                severityCounts['Faible'] || 0
            ];

            if (severityChart) {
                severityChart.data.datasets[0].data = chartData;
                severityChart.update();
            } else {
                severityChart = new window.Chart(chartCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Critique', 'Élevée', 'Moyenne', 'Faible'],
                        datasets: [{
                            data: chartData,
                            backgroundColor: ['#dc2626', '#ea580c', '#ca8a04', '#2563eb']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '62%',
                        plugins: { legend: { position: 'bottom' } }
                    }
                });
            }
        }

        var territoryCtx = document.getElementById('dashboard-territory-impact-chart');
        if (window.Chart && territoryCtx) {
            var rankedTerritories = Object.keys(territoryCounts)
                .map(function (name) {
                    return { name: name, total: territoryCounts[name] || 0 };
                })
                .sort(function (a, b) { return b.total - a.total; })
                .slice(0, 7);

            var territoryLabels = rankedTerritories.map(function (item) { return item.name; });
            var territoryValues = rankedTerritories.map(function (item) { return item.total; });

            if (territoryImpactChart) {
                territoryImpactChart.data.labels = territoryLabels;
                territoryImpactChart.data.datasets[0].data = territoryValues;
                territoryImpactChart.update();
            } else {
                territoryImpactChart = new window.Chart(territoryCtx, {
                    type: 'bar',
                    data: {
                        labels: territoryLabels,
                        datasets: [{
                            label: 'Incidents',
                            data: territoryValues,
                            backgroundColor: 'rgba(0, 91, 187, 0.82)',
                            borderRadius: 6,
                            maxBarThickness: 34
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { beginAtZero: true, grid: { color: '#e5edf7' } },
                            y: { grid: { display: false } }
                        }
                    }
                });
            }
        }
    }

        renderOperational(alerts);

        // Hook discret d'auto-rejet: non bloquant, silencieux et cadencé.
        if (autoRejectEnabled) {
            try {
                var throttleKey = 'sydraAutoRejectLastRunAt';
                var now = Date.now();
                var lastRunAt = Number(window.sessionStorage.getItem(throttleKey) || 0);
                // Anti-spam navigateur: maximum 1 exécution toutes les 5 minutes.
                var shouldRun = !Number.isFinite(lastRunAt) || (now - lastRunAt) > (5 * 60 * 1000);

                if (shouldRun) {
                    window.sessionStorage.setItem(throttleKey, String(now));
                    fetch('api/auto_reject_expired.php', {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        keepalive: true
                    })
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error('HTTP ' + response.status);
                            }
                            return response.json();
                        })
                        .then(function (data) {
                            // Si des statuts ont changé, on rafraîchit immédiatement la vue.
                            if (data && data.ok === true && Number(data.rejected || 0) > 0) {
                                // Rafraichit les tuiles seulement si des rejets automatiques ont été appliqués.
                                return fetch('api/get_dashboard_filtered.php', {
                                    method: 'GET',
                                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                                })
                                    .then(function (res) { return res.ok ? res.json() : null; })
                                    .then(function (payload) {
                                        if (payload && payload.ok === true && Array.isArray(payload.markers)) {
                                            renderOperational(payload.markers);
                                        }
                                    });
                            }
                            return null;
                        })
                        .catch(function () {
                            // Echec silencieux pour ne pas impacter l'UX du dashboard.
                        });
                }
            } catch (e) {
                // Le stockage navigateur peut être indisponible: on ignore sans bloquer.
            }
        }

        fetch('api/get_dashboard_filtered.php', {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function (data) {
                if (!data || data.ok !== true || !Array.isArray(data.markers)) {
                    return;
                }
                renderOperational(data.markers);
            })
            .catch(function () {
                // Fallback silencieux: les données initiales restent affichées.
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
