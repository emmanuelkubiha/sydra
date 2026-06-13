<?php
/**
 * Hub Rapportage SyDRA
 * - Filtres AJAX sécurisés
 * - Carte Leaflet + légende
 * - KPI + graphiques dynamiques Chart.js
 */

/** @var array<int, array<string, mixed>> $rapportageMapAlerts */
/** @var array<string, int> $rapportageStats */
/** @var array<int, array<string, mixed>> $rapportageOrganizations */
/** @var array<string, mixed>|null $authUser */

$alertsPayload = json_encode($rapportageMapAlerts ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
if (!is_string($alertsPayload)) {
    $alertsPayload = '[]';
}

$userRole = strtoupper((string) ($authUser['role'] ?? 'ORG_REPORTER'));
$isLeadOrAdmin = in_array($userRole, ['ADMIN', 'CLUSTER_LEADER', 'GTMP_LEAD', 'GTMP_COLEAD', 'CLUSTER_PROTECTION', 'LEAD_GTMP'], true);
$userOrgId = (int) ($authUser['organization_id'] ?? 0);
$userOrgName = htmlspecialchars((string) ($authUser['organization_name'] ?? 'Mon organisation'), ENT_QUOTES, 'UTF-8');
$stats = is_array($rapportageStats ?? null) ? $rapportageStats : ['total' => 0, 'critiques' => 0, 'attente' => 0, 'valides' => 0];
$brandLogoPath = 'assets/img/sydra-logo/BLEU-PRIMARY-SYDRA-LOGO.png';
$currentUserId = (int) ($authUser['id'] ?? 0);
$draftSummaryJson = json_encode($userDraftSummary ?? null, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
if (!is_string($draftSummaryJson)) {
    $draftSummaryJson = 'null';
}
$csrfTokenJs = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
$initialAlerts = is_array($rapportageMapAlerts ?? null) ? $rapportageMapAlerts : [];
$initialAlertsCount = count($initialAlerts);
$initialSeverity = ['Critique' => 0, 'Élevée' => 0, 'Moyenne' => 0, 'Faible' => 0];
foreach ($initialAlerts as $alertItem) {
    $level = strtolower(trim((string) ($alertItem['urgency_level'] ?? '')));
    if (strpos($level, 'crit') !== false) {
        $initialSeverity['Critique']++;
    } elseif (strpos($level, 'ele') !== false || strpos($level, 'high') !== false) {
        $initialSeverity['Élevée']++;
    } elseif (strpos($level, 'moy') !== false || strpos($level, 'medium') !== false) {
        $initialSeverity['Moyenne']++;
    } else {
        $initialSeverity['Faible']++;
    }
}
$initialSeverityParts = [];
foreach ($initialSeverity as $label => $value) {
    if ($value > 0) {
        $initialSeverityParts[] = $label . ': ' . $value;
    }
}
$initialSeverityText = $initialSeverityParts !== [] ? implode(' | ', $initialSeverityParts) : 'Aucune donnée de gravité disponible.';
$selectedDateDebut = trim((string) ($_GET['date_debut'] ?? ''));
$selectedDateFin = trim((string) ($_GET['date_fin'] ?? ''));
$selectedOrgId = trim((string) ($_GET['organisation_id'] ?? ''));

if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDateDebut) !== 1) {
    $selectedDateDebut = '';
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDateFin) !== 1) {
    $selectedDateFin = '';
}
if ($selectedOrgId !== '' && ctype_digit($selectedOrgId) === false) {
    $selectedOrgId = '';
}
?>

<!-- html2canvas and jsPDF libraries for PDF export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<style>
/* Appliquer Poppins/Inter globalement sur cette page */
.report-hub-container {
    font-family: 'Poppins', 'Inter', sans-serif;
}
.hero-premium {
    background: linear-gradient(135deg, #f8faff 0%, #e6f0ff 100%);
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    border: none;
    padding: 3rem 2rem;
    text-align: center;
}
.hero-premium h1 {
    font-weight: 800;
    color: #0f172a;
    font-size: 2.2rem;
}
.hero-premium p {
    color: #475569;
    font-size: 1.1rem;
    max-width: 600px;
    margin: 0 auto;
}
.btn-ai-premium {
    position: relative;
    border-radius: 50px !important;
    font-size: 1.1rem;
    padding: 14px 40px;
    font-weight: 700;
    background: linear-gradient(135deg, #005BBB 0%, #6366f1 100%);
    color: #ffffff !important;
    border: none !important;
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.btn-ai-premium:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 25px rgba(99, 102, 241, 0.45);
    background: linear-gradient(135deg, #004ea3 0%, #4f46e5 100%);
}
.btn-manual-premium {
    border-radius: 50px !important;
    font-size: 1.1rem;
    padding: 14px 40px;
    font-weight: 700;
    background-color: #ffffff !important;
    color: #334155 !important;
    border: 1.5px solid #cbd5e1 !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
}
.btn-manual-premium:hover {
    transform: translateY(-2px);
    background-color: #f8fafc !important;
    border-color: #94a3b8 !important;
    color: #0f172a !important;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
}
.badge-recommended {
    position: absolute;
    top: -12px;
    right: -10px;
    background-color: #ff3366;
    color: white;
    font-size: 0.7rem;
    padding: 4px 8px;
    border-radius: 12px;
    font-weight: bold;
    box-shadow: 0 4px 10px rgba(255,51,102,0.3);
    z-index: 10;
}
.premium-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.04);
}
.table-premium th {
    background-color: #f8fafc;
    color: #64748b;
    font-weight: 600;
    border-bottom: 1px solid #e2e8f0;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.table-premium td {
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    color: #334155;
    font-size: 0.95rem;
}
.typing-cursor {
    display: inline-block;
    width: 3px;
    height: 1.1em;
    background-color: #005BBB;
    vertical-align: text-bottom;
    animation: blink 1s step-end infinite;
    margin-left: 4px;
}
@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0; }
}
</style>

<div class="report-hub-container">
    <div class="card hero-premium mb-4 text-start">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <span class="badge bg-primary text-white mb-3 px-3 py-2 rounded-pill fw-bold shadow-sm" style="background-color: #005BBB !important;"><i class="fa-solid fa-sparkles me-1"></i> Nouveau : IA Intégrée</span>
                    <h1 class="mb-3 display-5 fw-bold text-dark" style="letter-spacing: -0.5px; min-height: 1.2em;">
                        <span id="hero-typing-text"></span><span class="typing-cursor"></span>
                    </h1>
                    <p class="mb-4 text-secondary fs-5" style="max-width: 100%;">
                        L'Assistant IA de SyDRA analyse vos notes brutes, structure les informations et sécurise votre rapport d'incident en quelques secondes. 
                        Ne perdez plus de temps sur le formatage.
                    </p>
                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            const phrases = [
                                "Que s'est-il passé sur le terrain ?",
                                "Une urgence humanitaire à signaler ?",
                                "Partagez votre rapport d'incident.",
                                "L'IA rédige pour vous."
                            ];
                            let currentPhrase = 0;
                            let currentChar = 0;
                            let isDeleting = false;
                            const textElement = document.getElementById("hero-typing-text");
                            
                            function type() {
                                const fullText = phrases[currentPhrase];
                                
                                if (isDeleting) {
                                    textElement.textContent = fullText.substring(0, currentChar - 1);
                                    currentChar--;
                                } else {
                                    textElement.textContent = fullText.substring(0, currentChar + 1);
                                    currentChar++;
                                }
                                
                                let typeSpeed = isDeleting ? 30 : 70;
                                
                                if (!isDeleting && currentChar === fullText.length) {
                                    typeSpeed = 2000; // Wait at end
                                    isDeleting = true;
                                } else if (isDeleting && currentChar === 0) {
                                    isDeleting = false;
                                    currentPhrase = (currentPhrase + 1) % phrases.length;
                                    typeSpeed = 500; // Wait before next
                                }
                                
                                setTimeout(type, typeSpeed);
                            }
                            
                            setTimeout(type, 1000); // Initial delay
                        });
                    </script>
                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        <a href="?page=rapportage-creer-AI" class="btn btn-ai-premium btn-lg">
                            <span class="badge-recommended">RECOMMANDÉ</span>
                            <i class="fa-solid fa-wand-magic-sparkles me-2"></i> Lancer l'Assistant IA
                        </a>
                        <a href="?page=rapportage-creer-wizar" class="btn btn-manual-premium btn-lg">
                            <i class="fa-solid fa-pen me-2"></i>Saisie manuelle (Wizard)
                        </a>
                    </div>
                    <div class="mt-4 pt-3 border-top d-flex gap-2">
                        <a href="?page=rapportage-mes-alertes" class="btn btn-light btn-sm shadow-sm rounded-pill px-3">Gérer toutes les alertes</a>
<?php if (isset($_SESSION['role_code']) && in_array($_SESSION['role_code'], ['ADMIN', 'GTMP_LEAD', 'GTMP_COLEAD', 'CLUSTER_LEADER', 'CLUSTER_PROTECTION'])): ?>
                        <a href="?page=rapportage-coordination" class="btn btn-light btn-sm shadow-sm rounded-pill px-3">Coordination</a>
<?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-5 text-center mt-4 mt-lg-0 d-none d-lg-block position-relative">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 220px; height: 220px; background: radial-gradient(circle, rgba(0,91,187,0.1) 0%, rgba(0,91,187,0) 70%);">
                        <i class="fa-solid fa-robot text-primary" style="font-size: 7rem; filter: drop-shadow(0 15px 20px rgba(0,91,187,0.25));"></i>
                    </div>
                    <!-- Particules décoratives -->
                    <i class="fa-solid fa-bolt text-warning position-absolute" style="font-size: 2.5rem; top: 10%; right: 25%; transform: rotate(15deg); filter: drop-shadow(0 4px 6px rgba(245,158,11,0.3));"></i>
                    <i class="fa-solid fa-shield-halved text-success position-absolute" style="font-size: 2rem; bottom: 20%; left: 20%; transform: rotate(-10deg); filter: drop-shadow(0 4px 6px rgba(16,185,129,0.3));"></i>
                    <i class="fa-solid fa-location-dot text-danger position-absolute" style="font-size: 1.5rem; top: 30%; left: 25%; filter: drop-shadow(0 4px 6px rgba(239,68,68,0.3));"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="card shadow-sm premium-card bg-white mb-4 p-3" id="hub-filter-bar">
        <form id="filterForm" method="get" action="?page=rapportage" class="row g-2 align-items-end" autocomplete="off" novalidate>
            <input type="hidden" name="page" value="rapportage">
            <div class="col-12 col-sm-6 col-lg-3">
                <label for="filter-date-debut" class="form-label mb-1 small fw-semibold text-secondary">
                    <i class="bi bi-calendar-event me-1"></i>Du
                </label>
                <input type="date" id="filter-date-debut" name="date_debut" class="form-control form-control-sm rounded-pill px-3" value="<?= htmlspecialchars($selectedDateDebut, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <label for="filter-date-fin" class="form-label mb-1 small fw-semibold text-secondary">
                    <i class="bi bi-calendar-check me-1"></i>Au
                </label>
                <input type="date" id="filter-date-fin" name="date_fin" class="form-control form-control-sm rounded-pill px-3" value="<?= htmlspecialchars($selectedDateFin, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <?php if ($isLeadOrAdmin): ?>
            <div class="col-12 col-sm-8 col-lg-4">
                <label for="filter-org" class="form-label mb-1 small fw-semibold text-secondary">
                    <i class="bi bi-building me-1"></i>Organisation
                </label>
                <select id="filter-org" name="organisation_id" class="form-select form-select-sm rounded-pill px-3">
                    <option value="">Toutes les organisations</option>
                    <?php foreach ($rapportageOrganizations as $org): ?>
                        <?php $orgId = (string) ((int) ($org['id'] ?? 0)); ?>
                        <option value="<?= (int) ($org['id'] ?? 0); ?>" <?= ($selectedOrgId !== '' && $selectedOrgId === $orgId) ? 'selected' : ''; ?>><?= htmlspecialchars((string) ($org['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php else: ?>
            <div class="col-12 col-sm-8 col-lg-4">
                <label class="form-label mb-1 small fw-semibold text-secondary">
                    <i class="bi bi-building me-1"></i>Organisation
                </label>
                <input type="text" class="form-control form-control-sm bg-light rounded-pill px-3" value="<?= $userOrgName; ?>" readonly>
                <input type="hidden" name="organisation_id" value="<?= $userOrgId; ?>">
            </div>
            <?php endif; ?>

            <div class="col-12 col-sm-4 col-lg-2 d-flex align-items-end">
                <button type="submit" id="btn-filtrer" class="btn btn-primary btn-sm w-100 rounded-pill">
                    <i class="bi bi-search me-1"></i>Filtrer
                </button>
                <button type="button" id="btn-reset-filter" class="btn btn-sm btn-outline-secondary ms-2 rounded-pill" title="Réinitialiser">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </form>
        <div id="filter-status" class="d-none mt-2">
            <span class="badge text-bg-info fs-7 rounded-pill" id="filter-status-text"></span>
        </div>
    </div>

    <!-- STATS GRID -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card premium-card p-3"><small class="text-secondary fw-semibold">Total Alertes</small><strong class="fs-4 text-primary" id="stat-total"><?= (int) $stats['total']; ?></strong></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card premium-card p-3"><small class="text-secondary fw-semibold">Alertes Critiques</small><strong class="fs-4 text-danger" id="stat-critiques"><?= (int) $stats['critiques']; ?></strong></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card premium-card p-3"><small class="text-secondary fw-semibold">En attente de validation</small><strong class="fs-4 text-warning" id="stat-attente"><?= (int) $stats['attente']; ?></strong></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card premium-card p-3"><small class="text-secondary fw-semibold">Rapports Validés</small><strong class="fs-4 text-success" id="stat-valides"><?= (int) $stats['valides']; ?></strong></div>
        </div>
    </div>

    <!-- ZONE EXPORT PDF (CARTE + ALERTES + GRAPHICS) -->
    <div id="pdf-export-area" class="p-3 bg-white mb-4" style="border-radius: 16px;">
        <div id="pdf-header" style="display: none; text-align: center; margin-bottom: 30px; padding-top: 20px;">
            <!-- Utilisation du logo bleu officiel de SyDRA -->
            <img src="assets/img/sydra-logo/BLEU-PRIMARY-SYDRA-LOGO.png" alt="Logo SyDRA" style="height: 60px; object-fit: contain;">
            <h2 style="color: #0d6efd; margin-top: 15px; font-weight: bold; font-family: 'Inter', sans-serif;">Rapport Global de Monitoring des Incidents</h2>
            <p style="color: #6c757d; font-size: 14px; font-family: 'Inter', sans-serif;">Généré le : <?php echo date('d/m/Y à H:i'); ?></p>
            <hr style="border-top: 2px solid #0d6efd; width: 50px; margin: 15px auto;">
        </div>
    <!-- CARTE -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card premium-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 fw-bold mb-0">Cartographie des Incidents</h2>
                    <span class="badge bg-light text-dark border rounded-pill"><?= count($initialAlerts); ?> point(s)</span>
                </div>
                <div id="rapportage-hub-map" class="hub-map rounded-4 w-100 shadow-sm" style="height: 550px; border: 1px solid #e2e8f0;" data-alerts='<?= htmlspecialchars($alertsPayload, ENT_QUOTES, 'UTF-8'); ?>'></div>
                <div class="mt-3 text-center">
                    <button id="btnExportDashboard" class="btn btn-primary rounded-pill shadow-sm px-4">
                        <span class="btn-text"><i class="fa-solid fa-file-pdf me-2"></i> Exporter le Rapport PDF</span>
                        <span class="btn-loader d-none"><i class="fa-solid fa-circle-notch fa-spin me-2"></i> Génération en cours (Veuillez patienter)...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MES ALERTES RÉCENTES -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card premium-card p-0 overflow-hidden">
                <div class="d-flex justify-content-between align-items-center p-4 border-bottom bg-white">
                    <h2 class="h5 fw-bold mb-0">Mes Alertes Récentes</h2>
                    <a href="?page=rapportage-mes-alertes" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm">Voir toutes les alertes</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-premium mb-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Réf</th>
                                <th>Localisation</th>
                                <th>Type d'Incident</th>
                                <th>Statut</th>
                                <th class="pe-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $recentCount = 0;
                            foreach ($initialAlerts as $alertItem): 
                                if ($recentCount >= 5) break;
                                $recentCount++;
                                $status = strtolower($alertItem['workflow_status'] ?? '');
                                $badgeClass = 'bg-secondary';
                                if (strpos($status, 'brouillon') !== false) $badgeClass = 'bg-warning text-dark';
                                if (strpos($status, 'soumis') !== false) $badgeClass = 'bg-primary';
                                if (strpos($status, 'valide') !== false || strpos($status, 'approuve') !== false) $badgeClass = 'bg-success';
                            ?>
                            <tr style="cursor: pointer;" onclick="window.location.href='?page=rapportage-mes-alertes&id=<?= (int)($alertItem['id'] ?? 0); ?>'">
                                <td class="ps-4 fw-bold text-primary">#<?= (int)($alertItem['id'] ?? 0); ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                            <i class="fa-solid fa-location-dot text-danger"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($alertItem['location_text'] ?? 'Non précisée', ENT_QUOTES, 'UTF-8'); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark">
                                        <?= htmlspecialchars($alertItem['incident_type'] ?? 'Incident', ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <?php if (isset($alertItem['is_ai_generated']) && $alertItem['is_ai_generated'] == 1): ?>
                                        <span class="badge rounded-pill mt-1" style="background-color: #f3e8ff; color: #7e22ce; border: 1px solid #d8b4fe; font-size: 0.65rem;"><i class="fa-solid fa-wand-magic-sparkles me-1"></i>Généré par l'IA</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge rounded-pill <?= $badgeClass; ?> px-3 py-2 fw-semibold" style="font-size: 0.75rem;">
                                        <i class="fa-solid fa-circle me-1" style="font-size: 0.5rem; vertical-align: middle;"></i>
                                        <?= htmlspecialchars($alertItem['workflow_status'] ?? 'Brouillon', ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-end">
                                    <button class="btn btn-sm btn-light rounded-circle shadow-sm" style="width: 32px; height: 32px;">
                                        <i class="fa-solid fa-chevron-right text-secondary"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if ($recentCount === 0): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted mb-2"><i class="fa-solid fa-folder-open fs-1"></i></div>
                                    <h6 class="fw-bold text-dark">Aucune alerte récente</h6>
                                    <p class="text-secondary small">Créez votre première alerte avec l'Assistant IA.</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<div class="row g-3 mt-1">
    <div class="col-xl-7">
        <div class="card shadow-sm rounded-4 border-0 chart-card h-100">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <h3 class="h6 mb-0">Évolution des incidents (période filtrée)</h3>
                <span class="badge text-bg-light border">Tendance</span>
            </div>
            <canvas id="incidentsTrendChart" class="hub-chart-canvas" aria-label="Évolution incidents"></canvas>
            <div id="trend-alt" class="chart-alt compact"></div>
            <div id="trend-static" class="small text-secondary mt-2">Global: <?= (int) $initialAlertsCount; ?> incident(s) chargé(s).</div>
            <div id="trend-summary" class="small text-secondary mt-2"></div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card shadow-sm rounded-4 border-0 chart-card h-100">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <h3 class="h6 mb-0">Répartition par gravité</h3>
                <span class="badge text-bg-light border">Critique à faible</span>
            </div>
            <canvas id="severityPieChart" class="hub-chart-canvas" aria-label="Répartition gravité"></canvas>
            <div id="severity-alt" class="chart-alt compact"></div>
            <div id="severity-static" class="small text-secondary mt-2"><?= htmlspecialchars($initialSeverityText, ENT_QUOTES, 'UTF-8'); ?></div>
            <div id="severity-summary" class="small text-secondary mt-2"></div>
        </div>
    </div>
</div>
</div><!-- /pdf-export-area -->

<style>
/* Autres styles conservés mais adaptés pour le conteneur */
#hub-filter-bar { border: 1px solid #e2e8f0; }
.hub-btn-filter { background: #005BBB; color: #fff; border: none; font-weight: 600; }
.hub-btn-filter:hover { background: #0047a0; color: #fff; }

.kpi-card { padding: 12px; display: grid; gap: 3px; border: 1px solid rgba(255,255,255,0.25); transition: transform .15s ease; }
.kpi-card:hover { transform: translateY(-2px); }
.kpi-card small { color: rgba(255,255,255,0.88); font-weight: 600; }
.kpi-card strong { color: #fff; font-size: 22px; line-height: 1; }
.kpi-blue { background: #005BBB; }
.kpi-red { background: #E53E3E; }
.kpi-orange { background: #f97316; }
.kpi-green { background: #059669; }
.kpi-loading strong { opacity: 0.4; }

.hub-map { width: 100%; height: 500px; border: 1px solid #dbeafe; overflow: hidden; }
#rapportage-hub-map .leaflet-popup-content-wrapper {
    border-radius: 16px;
    padding: 0;
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.2);
    overflow: hidden;
}
#rapportage-hub-map .leaflet-popup-content { margin: 0; }
#rapportage-hub-map .leaflet-popup-tip { background: #ffffff; }
.hub-marker {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    border: 2px solid #ffffff;
    box-shadow: 0 3px 10px rgba(15, 23, 42, 0.35);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 11px;
}
.hub-popup-card { min-width: 280px; max-width: 320px; background: #ffffff; }
.hub-popup-head {
    padding: 10px 12px;
    border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(130deg, #f8fbff 0%, #eef5ff 100%);
}
.hub-popup-title { font-size: 13px; font-weight: 800; color: #0f172a; margin: 0; }
.hub-popup-subtitle { font-size: 11px; color: #64748b; margin-top: 2px; }
.hub-popup-badges { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
.hub-popup-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    border-radius: 999px;
    padding: 3px 8px;
    font-size: 11px;
    font-weight: 700;
    line-height: 1;
}
.hub-popup-badge.severity-critical { background: #fee2e2; color: #b91c1c; }
.hub-popup-badge.severity-high { background: #ffedd5; color: #c2410c; }
.hub-popup-badge.severity-medium { background: #fef3c7; color: #a16207; }
.hub-popup-badge.severity-low { background: #dbeafe; color: #1d4ed8; }
.hub-popup-badge.status-approved { background: #dcfce7; color: #166534; }
.hub-popup-badge.status-review { background: #fef3c7; color: #a16207; }
.hub-popup-badge.status-rejected { background: #fee2e2; color: #b91c1c; }
.hub-popup-badge.status-submitted { background: #dbeafe; color: #1d4ed8; }
.hub-popup-badge.status-draft { background: #e2e8f0; color: #334155; }
.hub-popup-body { padding: 10px 12px 12px; font-size: 12px; color: #334155; }
.hub-popup-meta-row { display: flex; justify-content: space-between; gap: 8px; margin-bottom: 5px; }
.hub-popup-meta-label { color: #64748b; font-weight: 600; white-space: nowrap; }
.hub-popup-meta-value { color: #0f172a; text-align: right; font-weight: 600; }
.hub-popup-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    width: 100%;
    margin-top: 8px;
    border-radius: 10px;
    border: 1px solid #005BBB;
    background: #005BBB;
    color: #ffffff !important;
    padding: 8px 10px;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease;
}
.hub-popup-btn:hover,
.hub-popup-btn:focus,
.hub-popup-btn:active {
    background: #004ea3;
    border-color: #004ea3;
    color: #ffffff !important;
    box-shadow: 0 10px 18px rgba(0, 91, 187, 0.28);
    transform: translateY(-1px);
    text-decoration: none;
}

.hub-map-legend {
    background: #fff; padding: 8px 12px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    font-size: 12px; line-height: 1.7; min-width: 160px;
}
.hub-map-legend strong { display: block; font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 4px; }
.hub-map-legend-item { display: flex; align-items: center; gap: 7px; color: #1e293b; }
.hub-map-legend-dot { width: 12px; height: 12px; border-radius: 50%; flex: 0 0 auto; border: 2px solid rgba(0,0,0,0.18); }

.chart-card { border: 1px solid #dbeafe; padding: 14px; background: linear-gradient(170deg, #ffffff 0%, #f8fbff 100%); }
#incidentsTrendChart, #severityPieChart {
    display: block;
    width: 100%;
    height: 180px !important;
    max-height: 180px;
}
#severity-alt {
    display: none;
}
.chart-alt {
    border: 1px dashed #bfdbfe;
    border-radius: 12px;
    padding: 8px;
    min-height: 72px;
    max-height: 120px;
    overflow-y: auto;
    background: #ffffff;
}
.chart-alt.compact {
    min-height: 64px;
    max-height: 100px;
}
.chart-alt-empty {
    color: #64748b;
    font-size: 13px;
}
.chart-row {
    display: grid;
    grid-template-columns: 90px 1fr 32px;
    align-items: center;
    gap: 6px;
    margin-bottom: 6px;
}
.chart-row-label {
    color: #334155;
    font-size: 11px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.chart-row-bar {
    height: 8px;
    border-radius: 999px;
    background: #e2e8f0;
    overflow: hidden;
}
.chart-row-fill {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, #005bbb 0%, #38bdf8 100%);
}
.chart-row-value {
    color: #0f172a;
    font-size: 11px;
    font-weight: 700;
    text-align: right;
}
.cloud-dots {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    max-height: 70px;
    overflow-y: auto;
}
.cloud-dot {
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 9px;
    font-weight: 700;
    flex: 0 0 auto;
}

.severity-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.severity-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border-radius: 999px;
    padding: 4px 8px;
    font-size: 11px;
    font-weight: 700;
    color: #0f172a;
    background: #eef2ff;
}

.severity-chip-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex: 0 0 auto;
}

@media (max-width: 768px) {
    .chart-alt {
        max-height: 100px;
    }
    .chart-alt.compact {
        max-height: 92px;
    }
    .chart-row {
        grid-template-columns: 72px 1fr 28px;
        gap: 5px;
    }
    .chart-row-label,
    .chart-row-value {
        font-size: 11px;
    }
    .cloud-dots {
        max-height: 62px;
        gap: 4px;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    var BRAND_LOGO_PATH = <?= json_encode($brandLogoPath, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    var CURRENT_USER_ID = <?= (int) $currentUserId; ?>;
    var IS_DECISION_ROLE = <?= $isLeadOrAdmin ? 'true' : 'false'; ?>;
    var USER_DRAFT_SUMMARY = <?= $draftSummaryJson; ?>;
    var CSRF_TOKEN = '<?= $csrfTokenJs; ?>';

    function appBasePath() {
        var pathname = String(window.location.pathname || '/');
        if (pathname.slice(-1) === '/') {
            return pathname;
        }
        return pathname.slice(0, pathname.lastIndexOf('/') + 1);
    }

    var API_ENDPOINT = appBasePath() + 'api/get_dashboard_filtered.php';
    // Garde anti-boucle : empêche les appels AJAX simultanés qui créaient la boucle infinie.
    var _filterInProgress = false;

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
        return fetch('?page=rapportage', {
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
        document.querySelectorAll('.js-create-alert-link[data-check-draft="1"]').forEach(function (link) {
            link.addEventListener('click', function (event) {
                event.preventDefault();

                postDraftAction('check_user_draft_collision', {})
                    .then(function (data) {
                        if (!data || data.ok !== true || !data.has_draft || !data.draft) {
                            window.location.href = link.getAttribute('href') || '?page=rapportage-creer-wizar';
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
                                            window.location.href = link.getAttribute('href') || '?page=rapportage-creer-wizar';
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
                        window.location.href = link.getAttribute('href') || '?page=rapportage-creer-wizar';
                    });
            });
        });
    }

    function normalize(value) {
        return String(value || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
    }

    function urgencyColor(level) {
        var n = normalize(level);
        if (n.indexOf('crit') >= 0) return '#dc3545'; // Rouge
        if (n.indexOf('urg') >= 0 || n.indexOf('ele') >= 0) return '#fd7e14'; // Orange
        if (n.indexOf('norm') >= 0 || n.indexOf('moy') >= 0) return '#ffc107'; // Jaune
        return '#0d6efd'; // Bleu par défaut
    }

    function severityMeta(level) {
        var n = normalize(level);
        if (n.indexOf('crit') >= 0) {
            return { label: 'Critique', klass: 'severity-critical', icon: 'fa-solid fa-triangle-exclamation' };
        }
        if (n.indexOf('ele') >= 0 || n.indexOf('high') >= 0) {
            return { label: 'Élevée', klass: 'severity-high', icon: 'fa-solid fa-arrow-trend-up' };
        }
        if (n.indexOf('moy') >= 0 || n.indexOf('medium') >= 0) {
            return { label: 'Moyenne', klass: 'severity-medium', icon: 'fa-solid fa-chart-line' };
        }
        return { label: 'Faible', klass: 'severity-low', icon: 'fa-solid fa-circle-info' };
    }

    function statusMeta(status) {
        var n = normalize(status);
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

    function resolveLocationFromText(raw) {
        var cityCoords = {
            bukavu: [-2.5099, 28.8428], uvira: [-3.4067, 29.1458], goma: [-1.6792, 29.2228],
            minova: [-2.1547, 28.9891], kalehe: [-2.2581, 28.6765], idjwi: [-2.1198, 28.9961],
            walungu: [-2.7082, 28.6133], kabare: [-2.4741, 28.7619], shabunda: [-2.6978, 27.3358],
            fizi: [-4.3014, 28.9448], baraka: [-4.0976, 29.0958], kindu: [-2.9508, 25.9464],
            butembo: [-0.1408, 29.2903], kalima: [-2.6147, 26.5622]
        };
        var loc = normalize(raw);
        if (!loc) return null;
        for (var city in cityCoords) {
            if (Object.prototype.hasOwnProperty.call(cityCoords, city) && loc.indexOf(city) >= 0) {
                return cityCoords[city];
            }
        }
        return null;
    }

    var hubMap = null;
    var markersLayer = null;
    var trendChart = null;
    var severityChart = null;
    var cloudChart = null;
    var currentAlerts = [];
    var staticChartPayload = null;
    var staticCloudMarkers = [];
    var hubOverviewView = {
        center: null,
        zoom: 7
    };

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function buildStaticChartsFromMarkers(markers) {
        var list = Array.isArray(markers) ? markers : [];
        var severityMap = { 'Critique': 0, 'Élevée': 0, 'Moyenne': 0, 'Faible': 0 };

        list.forEach(function (m) {
            var sev = severityScore(m.urgency_level || 'Faible').label;
            if (!Object.prototype.hasOwnProperty.call(severityMap, sev)) {
                severityMap[sev] = 0;
            }
            severityMap[sev] += 1;
        });

        var labels = [];
        var values = [];
        ['Critique', 'Élevée', 'Moyenne', 'Faible'].forEach(function (label) {
            if (severityMap[label] > 0) {
                labels.push(label);
                values.push(severityMap[label]);
            }
        });

        return {
            trend: {
                labels: ['Global'],
                values: [list.length]
            },
            severity: {
                labels: labels,
                values: values
            }
        };
    }

    function buildMarkers(alerts) {
        if (!markersLayer) return;
        markersLayer.clearLayers();
        var added = [];
        currentAlerts = Array.isArray(alerts) ? alerts.slice() : [];

        alerts.forEach(function (item) {
            var lat = Number(item.gps_lat || 0);
            var lng = Number(item.gps_lng || 0);
            var coords = null;

            if (!Number.isNaN(lat) && !Number.isNaN(lng) && lat !== 0 && lng !== 0) {
                coords = [lat, lng];
            } else {
                coords = resolveLocationFromText(item.location_text || item.locality || item.province || '');
            }
            if (!coords) return;

            var color = urgencyColor(item.urgency_level || 'Moyenne');
            var reportId = Number(item.id || 0);
            var typeLabel = String(item.report_type || 'FLASH');
            var orgName = String(item.organization_name || 'Organisation inconnue');
            var dateRaw = String(item.created_at || '');
            var dateLabel = dateRaw ? dateRaw.replace('T', ' ') : 'Date non précisée';
            var severity = severityMeta(item.urgency_level || 'Faible');
            var status = statusMeta(item.workflow_status || 'Brouillon');
            var locationLabel = String(item.location_text || item.locality || item.province || 'Non précisée');
            var ownerUserId = Number(item.owner_user_id || 0);
            var canViewDetails = IS_DECISION_ROLE || (ownerUserId > 0 && ownerUserId === CURRENT_USER_ID);

            var icon = window.L.divIcon({
                className: 'hub-div-icon',
                html: '<span class="hub-marker" style="background:' + color + ';"><i class="fa-solid fa-triangle-exclamation"></i></span>',
                iconSize: [26, 26],
                iconAnchor: [13, 13],
                popupAnchor: [0, -8]
            });

            var marker = window.L.marker(coords, { icon: icon });

            marker.bindPopup(
                '<div class="hub-popup-card">'
                + '<div class="hub-popup-head">'
                + '<p class="hub-popup-title">Incident #' + reportId + ' - ' + escapeHtml(typeLabel) + '</p>'
                + '<div class="hub-popup-subtitle">' + escapeHtml(orgName) + '</div>'
                + '<div class="hub-popup-badges">'
                + '<span class="hub-popup-badge ' + severity.klass + '"><i class="' + severity.icon + '"></i>' + severity.label + '</span>'
                + '<span class="hub-popup-badge ' + status.klass + '"><i class="' + status.icon + '"></i>' + status.label + '</span>'
                + '</div>'
                + '</div>'
                + '<div class="hub-popup-body">'
                + '<div class="hub-popup-meta-row"><span class="hub-popup-meta-label">Date</span><span class="hub-popup-meta-value">' + escapeHtml(dateLabel) + '</span></div>'
                + '<div class="hub-popup-meta-row"><span class="hub-popup-meta-label">Localisation</span><span class="hub-popup-meta-value">' + escapeHtml(locationLabel) + '</span></div>'
                + '<a class="hub-popup-btn js-guard-report-access" data-can-view-details="' + (canViewDetails ? '1' : '0') + '" href="?page=rapportage-voir&id=' + reportId + '"><i class="fa-solid fa-eye"></i>Consulter l\'incident</a>'
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
                if (!hubMap) {
                    return;
                }
                hubMap._sydraOpeningPopup = true;
                window.setTimeout(function () {
                    if (hubMap) {
                        hubMap._sydraOpeningPopup = false;
                    }
                }, 650);

                var flyZoom = 10;
                if (typeof hubMap.getMaxZoom === 'function') {
                    flyZoom = Math.min(flyZoom, Number(hubMap.getMaxZoom() || flyZoom));
                }

                hubMap.flyTo(marker.getLatLng(), flyZoom, {
                    animate: true,
                    duration: 0.5
                });
            });

            markersLayer.addLayer(marker);
            added.push(coords);
        });

        var counter = document.getElementById('map-counter');
        if (counter) counter.textContent = added.length + ' point' + (added.length > 1 ? 's' : '');

        if (added.length > 0 && hubMap) {
            var bounds = window.L.latLngBounds(added);
            hubMap.fitBounds(bounds.pad(0.18));
            hubOverviewView = {
                center: hubMap.getCenter(),
                zoom: hubMap.getZoom()
            };
        } else if (hubMap) {
            hubMap.setView([-3.0, 27.5], 7);
            hubOverviewView = {
                center: hubMap.getCenter(),
                zoom: hubMap.getZoom()
            };
        }
    }

    function addLegend(map) {
        var Legend = window.L.Control.extend({
            options: { position: 'bottomright' },
            onAdd: function () {
                var div = window.L.DomUtil.create('div', 'hub-map-legend');
                div.innerHTML =
                    '<strong>Niveau de gravité</strong>'
                    + '<div class="hub-map-legend-item"><span class="hub-map-legend-dot" style="background:#E53E3E"></span>Critique</div>'
                    + '<div class="hub-map-legend-item"><span class="hub-map-legend-dot" style="background:#f97316"></span>Élevée</div>'
                    + '<div class="hub-map-legend-item"><span class="hub-map-legend-dot" style="background:#f59e0b"></span>Moyenne</div>'
                    + '<div class="hub-map-legend-item"><span class="hub-map-legend-dot" style="background:#005BBB"></span>Faible</div>';
                return div;
            }
        });
        new Legend().addTo(map);
    }

    function initMap() {
        if (!window.L) {
            setTimeout(initMap, 90);
            return;
        }

        var mapEl = document.getElementById('rapportage-hub-map');
        if (!mapEl || mapEl.dataset.ready === '1') return;
        mapEl.dataset.ready = '1';

        var bounds = window.L.latLngBounds(window.L.latLng(-5.0, 25.0), window.L.latLng(0.0, 29.5));

        hubMap = window.L.map(mapEl, {
            minZoom: 6,
            maxZoom: 12,
            maxBounds: bounds,
            maxBoundsViscosity: 1.0,
            zoomControl: true
        });
        hubMap.setView([-3.0, 27.5], 7);
        hubOverviewView = {
            center: hubMap.getCenter(),
            zoom: hubMap.getZoom()
        };
        hubMap._sydraOpeningPopup = false;

        hubMap.on('popupclose', function () {
            if (hubMap._sydraOpeningPopup || !hubOverviewView.center) {
                return;
            }

            var targetCenter = hubOverviewView.center;
            var targetZoom = hubOverviewView.zoom;

            if (targetCenter && typeof targetZoom === 'number') {
                hubMap.flyTo(targetCenter, targetZoom, {
                    animate: true,
                    duration: 0.45
                });
            }
        });

        window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(hubMap);

        markersLayer = window.L.featureGroup().addTo(hubMap);
        addLegend(hubMap);

        var raw = mapEl.getAttribute('data-alerts') || '[]';
        var initialAlerts = [];
        try {
            initialAlerts = JSON.parse(raw);
        } catch (e) {
            initialAlerts = [];
        }
        staticCloudMarkers = Array.isArray(initialAlerts) ? initialAlerts.slice() : [];
        if (staticChartPayload === null) {
            staticChartPayload = buildStaticChartsFromMarkers(staticCloudMarkers);
        }
        buildMarkers(initialAlerts);
    }

    function initCharts() {
        if (!window.Chart) {
            renderChartsFallback(staticChartPayload || { trend: { labels: [], values: [] }, severity: { labels: [], values: [] } });
            return;
        }

        var trendCtx = document.getElementById('incidentsTrendChart');
        var severityCtx = document.getElementById('severityPieChart');
        var cloudCtx = document.getElementById('incidentsCloudChart');

        if (trendCtx && !trendChart) {
            trendChart = new window.Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Incidents',
                        data: [],
                        borderColor: '#005BBB',
                        backgroundColor: 'rgba(0, 91, 187, 0.18)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            });
        }

        if (severityCtx && !severityChart) {
            severityChart = new window.Chart(severityCtx, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: ['#E53E3E', '#f97316', '#f59e0b', '#005BBB'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }

        if (cloudCtx && !cloudChart) {
            cloudChart = new window.Chart(cloudCtx, {
                type: 'bubble',
                data: {
                    datasets: [{
                        label: 'Incidents filtrés',
                        data: [],
                        backgroundColor: 'rgba(0, 91, 187, 0.38)',
                        borderColor: '#005BBB',
                        borderWidth: 1.2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    var raw = ctx.raw || {};
                                    var org = raw.org || 'Organisation';
                                    var sev = raw.severity_label || 'Faible';
                                    return org + ' - ' + sev;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: { display: true, text: 'Index incident filtré' },
                            ticks: { precision: 0 }
                        },
                        y: {
                            min: 0.5,
                            max: 4.5,
                            ticks: {
                                stepSize: 1,
                                callback: function (value) {
                                    if (value === 4) return 'Critique';
                                    if (value === 3) return 'Élevée';
                                    if (value === 2) return 'Moyenne';
                                    if (value === 1) return 'Faible';
                                    return '';
                                }
                            },
                            title: { display: true, text: 'Niveau de gravité' }
                        }
                    }
                }
            });
        }
    }

    function severityScore(level) {
        var n = normalize(level);
        if (n.indexOf('crit') >= 0) return { score: 4, label: 'Critique', radius: 9 };
        if (n.indexOf('ele') >= 0 || n.indexOf('high') >= 0) return { score: 3, label: 'Élevée', radius: 8 };
        if (n.indexOf('moy') >= 0 || n.indexOf('medium') >= 0) return { score: 2, label: 'Moyenne', radius: 7 };
        return { score: 1, label: 'Faible', radius: 6 };
    }

    function updateCloudChart(markers) {
        if (!window.Chart || !cloudChart) {
            renderCloudAlt(markers);
            return;
        }

        var cloudStatic = document.getElementById('cloud-static');
        if (cloudStatic) {
            cloudStatic.style.display = 'none';
        }

        var list = Array.isArray(markers) ? markers : [];
        var points = list.slice(0, 180).map(function (m, idx) {
            var sev = severityScore(m.urgency_level || 'Faible');
            return {
                x: idx + 1,
                y: sev.score,
                r: sev.radius,
                org: String(m.organization_name || 'Organisation'),
                severity_label: sev.label
            };
        });

        cloudChart.data.datasets[0].data = points;
        cloudChart.data.datasets[0].backgroundColor = points.map(function (pt) {
            if (pt.y === 4) return 'rgba(229, 62, 62, 0.45)';
            if (pt.y === 3) return 'rgba(249, 115, 22, 0.45)';
            if (pt.y === 2) return 'rgba(245, 158, 11, 0.45)';
            return 'rgba(0, 91, 187, 0.35)';
        });
        cloudChart.update();
        renderCloudAlt(markers);
    }

    function updateCharts(chartsPayload) {
        var trendCanvas = document.getElementById('incidentsTrendChart');
        var severityCanvas = document.getElementById('severityPieChart');
        var trendAlt = document.getElementById('trend-alt');
        var severityAlt = document.getElementById('severity-alt');
        var trendStatic = document.getElementById('trend-static');
        var severityStatic = document.getElementById('severity-static');
        var trendFallback = document.getElementById('trend-fallback');
        var severityFallback = document.getElementById('severity-fallback');

        var trend = chartsPayload && chartsPayload.trend ? chartsPayload.trend : { labels: [], values: [] };
        var severity = chartsPayload && chartsPayload.severity ? chartsPayload.severity : { labels: [], values: [] };

        if (!window.Chart) {
            if (trendCanvas) trendCanvas.style.display = 'none';
            if (severityCanvas) severityCanvas.style.display = 'none';
            if (trendAlt) trendAlt.style.display = 'block';
            if (severityAlt) severityAlt.style.display = 'block';
            if (trendStatic) trendStatic.style.display = 'block';
            if (severityStatic) severityStatic.style.display = 'block';

            renderChartsFallback(chartsPayload || {});
            renderTrendAlt(trend);
            renderSeverityAlt(severity);
            return;
        }

        if (trendStatic) trendStatic.style.display = 'none';
        if (severityStatic) severityStatic.style.display = 'none';
        if (trendFallback) trendFallback.style.display = 'none';
        if (severityFallback) severityFallback.style.display = 'none';

        if (trendCanvas) trendCanvas.style.display = 'block';
        if (severityCanvas) severityCanvas.style.display = 'block';
        if (trendAlt) trendAlt.style.display = 'none';
        if (severityAlt) severityAlt.style.display = 'none';

        if (trendChart) {
            trendChart.data.labels = Array.isArray(trend.labels) ? trend.labels : [];
            trendChart.data.datasets[0].data = Array.isArray(trend.values) ? trend.values : [];
            trendChart.update();
        }

        if (severityChart) {
            severityChart.data.labels = Array.isArray(severity.labels) ? severity.labels : [];
            severityChart.data.datasets[0].data = Array.isArray(severity.values) ? severity.values : [];
            severityChart.update();
        }
    }

    function renderTrendAlt(trend) {
        var host = document.getElementById('trend-alt');
        if (!host) return;

        var labels = Array.isArray(trend && trend.labels) ? trend.labels : [];
        var values = Array.isArray(trend && trend.values) ? trend.values : [];
        if (labels.length === 0 || values.length === 0) {
            host.innerHTML = '<div class="chart-alt-empty">Aucune donnée de tendance.</div>';
            return;
        }

        var max = 1;
        for (var i = 0; i < values.length; i += 1) {
            if (Number(values[i]) > max) max = Number(values[i]);
        }

        var rows = [];
        var maxRows = 3;
        var limit = Math.min(labels.length, maxRows);
        for (var j = 0; j < limit; j += 1) {
            var val = Number(values[j] || 0);
            var pct = Math.max(4, Math.round((val / max) * 100));
            rows.push(
                '<div class="chart-row">'
                + '<div class="chart-row-label">' + escapeHtml(labels[j]) + '</div>'
                + '<div class="chart-row-bar"><div class="chart-row-fill" style="width:' + pct + '%"></div></div>'
                + '<div class="chart-row-value">' + val + '</div>'
                + '</div>'
            );
        }
        if (labels.length > limit) {
            rows.push('<div class="chart-alt-empty">+' + (labels.length - limit) + ' periode(s) supplementaire(s)</div>');
        }
        host.innerHTML = rows.join('');
    }

    function renderSeverityAlt(severity) {
        var host = document.getElementById('severity-alt');
        if (!host) return;

        var labels = Array.isArray(severity && severity.labels) ? severity.labels : [];
        var values = Array.isArray(severity && severity.values) ? severity.values : [];
        if (labels.length === 0 || values.length === 0) {
            host.innerHTML = '<div class="chart-alt-empty">Aucune donnée de gravité.</div>';
            return;
        }

        var colors = {
            'Critique': '#E53E3E',
            'Élevée': '#f97316',
            'Moyenne': '#f59e0b',
            'Faible': '#005BBB'
        };

        var max = 1;
        for (var i = 0; i < values.length; i += 1) {
            if (Number(values[i]) > max) max = Number(values[i]);
        }

        var rows = [];
        var maxRows = 4;
        var limit = Math.min(labels.length, maxRows);
        rows.push('<div class="severity-chips">');
        for (var j = 0; j < limit; j += 1) {
            var label = String(labels[j]);
            var val = Number(values[j] || 0);
            var color = colors[label] || '#64748b';
            rows.push(
                '<span class="severity-chip">'
                + '<span class="severity-chip-dot" style="background:' + color + '"></span>'
                + escapeHtml(label) + ': ' + val
                + '</span>'
            );
        }
        rows.push('</div>');
        if (labels.length > limit) {
            rows.push('<div class="chart-alt-empty">+' + (labels.length - limit) + ' categorie(s) supplementaire(s)</div>');
        }
        host.innerHTML = rows.join('');
    }

    function renderCloudAlt(markers) {
        var host = document.getElementById('cloud-alt');
        if (!host) return;

        var list = Array.isArray(markers) ? markers : [];
        if (list.length === 0) {
            host.innerHTML = '<div class="chart-alt-empty">Aucun point global disponible.</div>';
            return;
        }

        var dots = [];
        for (var i = 0; i < list.length && i < 8; i += 1) {
            var sev = severityScore(list[i].urgency_level || 'Faible');
            var size = sev.radius * 2;
            var color = '#005BBB';
            if (sev.score === 4) color = '#E53E3E';
            else if (sev.score === 3) color = '#f97316';
            else if (sev.score === 2) color = '#f59e0b';

            dots.push(
                '<span class="cloud-dot" style="width:' + size + 'px;height:' + size + 'px;background:' + color + '" title="'
                + escapeHtml(String(list[i].organization_name || 'Organisation') + ' - ' + sev.label)
                + '">' + sev.score + '</span>'
            );
        }

        var more = '';
        if (list.length > 8) {
            more = '<div class="chart-alt-empty mt-2">+' + (list.length - 8) + ' point(s) non affiches</div>';
        }

        host.innerHTML = '<div class="cloud-dots">' + dots.join('') + '</div>' + more;
    }

    function renderChartsFallback(chartsPayload) {
        var trend = chartsPayload && chartsPayload.trend ? chartsPayload.trend : { labels: [], values: [] };
        var severity = chartsPayload && chartsPayload.severity ? chartsPayload.severity : { labels: [], values: [] };

        var trendCanvas = document.getElementById('incidentsTrendChart');
        var severityCanvas = document.getElementById('severityPieChart');
        if (!trendCanvas || !severityCanvas) {
            return;
        }

        var trendParent = trendCanvas.parentElement;
        var severityParent = severityCanvas.parentElement;
        if (!trendParent || !severityParent) {
            return;
        }

        trendCanvas.style.display = 'none';
        severityCanvas.style.display = 'none';

        var trendFallback = document.getElementById('trend-fallback');
        if (!trendFallback) {
            trendFallback = document.createElement('div');
            trendFallback.id = 'trend-fallback';
            trendFallback.className = 'small text-muted';
            trendParent.appendChild(trendFallback);
        }

        var trendRows = [];
        for (var i = 0; i < (trend.labels || []).length; i += 1) {
            trendRows.push('<li>' + String(trend.labels[i]) + ': <strong>' + String((trend.values || [])[i] || 0) + '</strong></li>');
        }
        trendFallback.innerHTML = trendRows.length > 0 ? '<ul class="mb-0 ps-3">' + trendRows.join('') + '</ul>' : '<p class="mb-0">Aucune donnée de tendance.</p>';

        var severityFallback = document.getElementById('severity-fallback');
        if (!severityFallback) {
            severityFallback = document.createElement('div');
            severityFallback.id = 'severity-fallback';
            severityFallback.className = 'small text-muted';
            severityParent.appendChild(severityFallback);
        }

        var sevRows = [];
        for (var j = 0; j < (severity.labels || []).length; j += 1) {
            sevRows.push('<li>' + String(severity.labels[j]) + ': <strong>' + String((severity.values || [])[j] || 0) + '</strong></li>');
        }
        severityFallback.innerHTML = sevRows.length > 0 ? '<ul class="mb-0 ps-3">' + sevRows.join('') + '</ul>' : '<p class="mb-0">Aucune donnée de gravité.</p>';
    }

    function exportMapReport() {
        var popup = window.open('', '_blank');

        var logoUrl = String(window.location.origin || '') + appBasePath() + BRAND_LOGO_PATH;
        var alertsJson = JSON.stringify(currentAlerts || []);

        var html = ''
            + '<!doctype html><html lang="fr"><head><meta charset="utf-8">'
            + '<title>Export carte incidents SyDRA</title>'
            + '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">'
            + '<style>body{font-family:Arial,sans-serif;padding:20px;color:#0f172a} .head{display:flex;align-items:center;gap:12px;margin-bottom:12px;border-bottom:1px solid #dbeafe;padding-bottom:8px} .logo{height:42px} #print-map{height:460px;border:1px solid #cbd5e1;border-radius:10px} table{width:100%;border-collapse:collapse;margin-top:14px} th,td{border:1px solid #e2e8f0;padding:6px;text-align:left;font-size:12px} th{background:#f8fafc}</style>'
            + '</head><body>'
            + '<div class="head"><img class="logo" src="' + logoUrl + '" alt="SyDRA"><div><strong>SyDRA - Export Carte des Incidents</strong><div style="font-size:12px;color:#475569">Document décisionnel</div></div></div>'
            + '<div id="print-map"></div>'
            + '<table><thead><tr><th>ID</th><th>Organisation</th><th>Localisation</th><th>Gravité</th><th>Statut</th><th>Date soumission</th></tr></thead><tbody id="rows"></tbody></table>'
            + '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></' + 'script>'
            + '<script>(function(){var alerts=' + alertsJson + '; var map=L.map("print-map",{zoomControl:false}).setView([-3.0,27.5],7); L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",{attribution:"&copy; OpenStreetMap contributors"}).addTo(map); var city={bukavu:[-2.5099,28.8428],uvira:[-3.4067,29.1458],goma:[-1.6792,29.2228],minova:[-2.1547,28.9891],kalehe:[-2.2581,28.6765],idjwi:[-2.1198,28.9961],walungu:[-2.7082,28.6133],kabare:[-2.4741,28.7619],shabunda:[-2.6978,27.3358],fizi:[-4.3014,28.9448],baraka:[-4.0976,29.0958],kindu:[-2.9508,25.9464],butembo:[-0.1408,29.2903],kalima:[-2.6147,26.5622]}; var rows=[]; var pts=[]; alerts.forEach(function(a){var lat=Number(a.gps_lat||0),lng=Number(a.gps_lng||0),p=null; if(!Number.isNaN(lat)&&!Number.isNaN(lng)&&lat!==0&&lng!==0){p=[lat,lng];} else {var raw=String(a.location_text||a.locality||a.province||"").toLowerCase(); Object.keys(city).forEach(function(k){ if(!p && raw.indexOf(k)>=0){p=city[k];} });} if(p){L.circleMarker(p,{radius:7,color:"#005BBB",fillColor:"#005BBB",fillOpacity:0.8}).addTo(map); pts.push(p);} var d=String(a.created_at||"").replace("T"," "); rows.push("<tr><td>"+String(a.id||"")+"</td><td>"+String(a.organization_name||"")+"</td><td>"+String(a.location_text||a.province||"")+"</td><td>"+String(a.urgency_level||"")+"</td><td>"+String(a.workflow_status||"")+"</td><td>"+d+"</td></tr>");}); document.getElementById("rows").innerHTML=rows.join(""); if(pts.length>0){map.fitBounds(L.latLngBounds(pts).pad(0.25));} else {map.fitBounds(L.latLngBounds([[-5.0,25.0],[0.0,29.5]]));} setTimeout(function(){map.invalidateSize(); window.print();},1400);})();</' + 'script>'
            + '</body></html>';

        if (!popup) {
            var blob = new Blob([html], { type: 'text/html;charset=utf-8' });
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'sydra-carte-incidents.html';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            setTimeout(function () {
                URL.revokeObjectURL(link.href);
            }, 1000);
            return;
        }

        popup.document.open();
        popup.document.write(html);
        popup.document.close();
    }

    function activeFilterLabel() {
        var form = document.getElementById('filterForm');
        if (!form) return '';

        var parts = [];
        var fromEl = form.querySelector('[name="date_debut"]');
        var toEl = form.querySelector('[name="date_fin"]');
        var orgEl = form.querySelector('[name="organisation_id"]');

        var fromValue = fromEl ? String(fromEl.value || '').trim() : '';
        var toValue = toEl ? String(toEl.value || '').trim() : '';
        if (fromValue || toValue) {
            parts.push('Période: ' + (fromValue || '...') + ' -> ' + (toValue || '...'));
        }

        if (orgEl) {
            var orgValue = String(orgEl.value || '').trim();
            if (orgValue !== '') {
                if (orgEl.tagName === 'SELECT') {
                    var orgText = orgEl.options[orgEl.selectedIndex] ? String(orgEl.options[orgEl.selectedIndex].text || '').trim() : orgValue;
                    parts.push('Organisation: ' + orgText);
                } else {
                    parts.push('Organisation: ' + orgValue);
                }
            }
        }

        return parts.join(' | ');
    }

    function updateSummaries(chartsPayload, markers) {
        var trendSummary = document.getElementById('trend-summary');
        var severitySummary = document.getElementById('severity-summary');

        var trend = chartsPayload && chartsPayload.trend ? chartsPayload.trend : { labels: [], values: [] };
        var severity = chartsPayload && chartsPayload.severity ? chartsPayload.severity : { labels: [], values: [] };

        if (trendSummary) {
            if (Array.isArray(trend.labels) && trend.labels.length > 0) {
                trendSummary.textContent = 'Périodes: ' + trend.labels.length + ' | Dernière période: ' + String(trend.labels[trend.labels.length - 1]) + ' (' + String((trend.values || [])[trend.values.length - 1] || 0) + ' incident(s))';
            } else {
                trendSummary.textContent = 'Aucune donnée de tendance pour le filtre courant.';
            }
        }

        if (severitySummary) {
            if (Array.isArray(severity.labels) && severity.labels.length > 0) {
                var sevParts = severity.labels.map(function (label, idx) {
                    return String(label) + ': ' + String((severity.values || [])[idx] || 0);
                });
                severitySummary.textContent = sevParts.join(' | ');
            } else {
                severitySummary.textContent = 'Aucune donnée de gravité pour le filtre courant.';
            }
        }

    }

    function applyFilter(params, isReset) {
        // Verrou anti-boucle : si un appel est déjà en cours, on ignore le suivant.
        if (_filterInProgress) {
            console.warn('[SyDRA] applyFilter ignoré : un filtre est déjà en cours.');
            return;
        }
        _filterInProgress = true;

        var kpiCards = ['stat-total', 'stat-critiques', 'stat-attente', 'stat-valides'];
        kpiCards.forEach(function (id) {
            var el = document.getElementById(id);
            if (el && el.closest('.kpi-card')) {
                el.closest('.kpi-card').classList.add('kpi-loading');
            }
        });

        var statusBox = document.getElementById('filter-status');
        var statusText = document.getElementById('filter-status-text');

        fetch(API_ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: params
        })
        .then(function (resp) {
            // ✅ DEBUG AVANCÉ : Si le serveur retourne une erreur HTTP (ex: PHP 500),
            // on lit le corps brut de la réponse pour voir l'erreur PHP réelle dans la console.
            if (!resp.ok) {
                var statusCode = resp.status;
                return resp.text().then(function (rawBody) {
                    console.error(
                        '[SyDRA][applyFilter] ❌ Erreur HTTP ' + statusCode +
                        ' depuis ' + API_ENDPOINT + '\n' +
                        '--- Réponse serveur brute (cherchez l\'erreur PHP ci-dessous) ---\n' +
                        rawBody
                    );
                    throw new Error('HTTP ' + statusCode + ' — Erreur serveur (voir Console pour détails PHP)');
                });
            }
            return resp.json();
        })
        .then(function (data) {
            if (!data || data.success === false) {
                throw new Error((data && data.error) ? String(data.error) : 'Réponse API invalide');
            }

            // Mission 4: Mettre à jour le texte des cartes KPI
            var kpi = data.kpi || {};
            var statMap = {
                'stat-total': kpi.total || 0,
                'stat-critiques': kpi.critiques || 0,
                'stat-attente': kpi.en_attente || 0,
                'stat-valides': kpi.valides || 0
            };

            Object.keys(statMap).forEach(function (id) {
                var el = document.getElementById(id);
                if (el) {
                    el.textContent = statMap[id];
                    if (el.closest('.kpi-card')) el.closest('.kpi-card').classList.remove('kpi-loading');
                }
            });

            // Mission 4: Marqueurs Leaflet
            var markers = Array.isArray(data.map_markers) ? data.map_markers : [];
            if (hubMap && markersLayer) {
                // Vide les anciens marqueurs de la carte
                markersLayer.clearLayers();
                buildMarkers(markers);
            }

            // Mise à jour des graphiques
            var chartData = data.chart_data || [];
            var markers = Array.isArray(data.map_markers) ? data.map_markers : [];
            var visualsCharts = { trend: { labels: [], values: [] }, severity: { labels: [], values: [] } };
            // Transformation de chartData (period => total) vers le format attendu par les graphiques
            chartData.forEach(function(row) {
                visualsCharts.trend.labels.push(row.period);
                visualsCharts.trend.values.push(row.total);
            });

            // Calcul de la répartition par gravité à partir des marqueurs filtrés
            var severityMap = { 'Critique': 0, 'Élevée': 0, 'Moyenne': 0, 'Faible': 0 };
            markers.forEach(function (m) {
                var sev = severityScore(m.urgency_level || 'Faible').label;
                if (!Object.prototype.hasOwnProperty.call(severityMap, sev)) {
                    severityMap[sev] = 0;
                }
                severityMap[sev] += 1;
            });
            ['Critique', 'Élevée', 'Moyenne', 'Faible'].forEach(function (label) {
                if (severityMap[label] > 0) {
                    visualsCharts.severity.labels.push(label);
                    visualsCharts.severity.values.push(severityMap[label]);
                }
            });

            updateCharts(visualsCharts);
            
            // Mise à jour du résumé textuel (statut)
            if (statusBox && statusText) {
                if (isReset) {
                    statusBox.classList.add('d-none');
                } else {
                    var details = activeFilterLabel();
                    statusText.textContent = markers.length + ' alerte(s) filtrée(s)' + (details ? ' | ' + details : '');
                    statusBox.classList.remove('d-none');
                }
            }
        })
        .catch(function (err) {
            // ✅ FIX BOUCLE INFINIE : On n'effectue PLUS de window.location.assign().
            // L'ancienne redirection vers '?page=rapportage' en cas d'erreur AJAX causait
            // un rechargement qui déclenchait la même requête erreur, créant une boucle infinie.
            // On affiche l'erreur dans l'UI sans recharger la page.
            kpiCards.forEach(function (id) {
                var el = document.getElementById(id);
                if (el && el.closest('.kpi-card')) el.closest('.kpi-card').classList.remove('kpi-loading');
            });

            // ✅ MISSION 3 SPRINT 4 : Afficher l'erreur PHP/SQL réelle dans le popup
            var errMessage = (err && err.message) ? String(err.message) : 'Erreur inconnue.';

            if (statusBox && statusText) {
                statusText.textContent = '⚠️ ' + errMessage;
                statusBox.classList.remove('d-none');
                statusBox.classList.add('text-warning');
            }

            if (window.Swal && typeof window.Swal.fire === 'function') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Erreur de filtre',
                    html: '<p>Impossible de charger les données filtrées.</p>' +
                          '<pre style="text-align:left;font-size:0.75rem;max-height:200px;overflow:auto;background:#f1f5f9;padding:10px;border-radius:6px;margin-top:8px;">' +
                          errMessage.replace(/</g, '&lt;').replace(/>/g, '&gt;') +
                          '</pre>',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#e74c3c'
                });
            }

            console.error('[SyDRA] Filtre hub erreur (sans rechargement):', err);
        })
        .finally(function () {
            // Libération du verrou anti-boucle après chaque appel (succès ou échec).
            _filterInProgress = false;
        });
    }

    function bindFilterForm() {
        var form = document.getElementById('filterForm');
        var reset = document.getElementById('btn-reset-filter');
        var exportBtn = document.getElementById('btnExportDashboard');
        if (!form) return;

        function syncUrl(paramsString) {
            var url = new URL(window.location.href);
            var params = new URLSearchParams(paramsString || '');

            url.searchParams.set('page', 'rapportage');
            ['date_debut', 'date_fin', 'organisation_id'].forEach(function (key) {
                var value = (params.get(key) || '').trim();
                if (value) {
                    url.searchParams.set(key, value);
                } else {
                    url.searchParams.delete(key);
                }
            });

            window.history.replaceState({}, '', url.toString());
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var params = new URLSearchParams(new FormData(form));
            var paramsString = params.toString();
            syncUrl(paramsString);
            applyFilter(paramsString, false);
        });

        if (reset) {
            reset.addEventListener('click', function () {
                form.reset();

                var fromEl = form.querySelector('[name="date_debut"]');
                var toEl = form.querySelector('[name="date_fin"]');
                var orgEl = form.querySelector('[name="organisation_id"]');
                if (fromEl) fromEl.value = '';
                if (toEl) toEl.value = '';
                if (orgEl && orgEl.tagName === 'SELECT') orgEl.value = '';

                syncUrl('');
                applyFilter('', true);
            });
        }

        if (exportBtn) {
            exportBtn.addEventListener('click', function (e) {
                e.preventDefault();
                
                let btn = this;
                let btnText = btn.querySelector('.btn-text');
                let btnLoader = btn.querySelector('.btn-loader');
                
                // 1. Bloquer l'UI
                if (btnText && btnLoader) {
                    btn.classList.add('disabled');
                    btnText.classList.add('d-none');
                    btnLoader.classList.remove('d-none');
                }

                // 2. Préparer la carte Leaflet
                let map = typeof hubMap !== 'undefined' ? hubMap : undefined;
                let markerGroup = typeof markersLayer !== 'undefined' ? markersLayer : undefined;
                if (typeof map !== 'undefined' && typeof markerGroup !== 'undefined' && markerGroup.getLayers().length > 0) {
                    if (map.dragging) map.dragging.disable();
                    map.fitBounds(markerGroup.getBounds(), { padding: [2, 2], animate: false });
                    map.invalidateSize();
                }

                // 3. Afficher l'en-tête fantôme pour le PDF
                const pdfHeader = document.getElementById('pdf-header');
                if (pdfHeader) pdfHeader.style.display = 'block';

                // 4. Attendre le rendu de la carte et des graphiques
                setTimeout(function() {
                    const exportArea = document.getElementById('pdf-export-area');
                    if (!exportArea) {
                        console.error("Export area not found");
                        if (pdfHeader) pdfHeader.style.display = 'none';
                        if (btnText && btnLoader) {
                            btn.classList.remove('disabled');
                            btnText.classList.remove('d-none');
                            btnLoader.classList.add('d-none');
                        }
                        return;
                    }
                    
                    // Configuration Haute Qualité (Netteté maximale)
                    const html2canvasOptions = {
                        scale: 2, // Échelle 2 = Résolution Retina/DPI doublé pour des textes et graphiques ultra-nets
                        useCORS: true, 
                        allowTaint: false,
                        backgroundColor: '#ffffff',
                        logging: false,
                        // LE FILTRE MAGIQUE : Ignore tous les boutons et liens d'action
                        ignoreElements: function(element) {
                            if (element.tagName && element.tagName.toLowerCase() === 'button') return true;
                            if (element.classList && element.classList.contains('btn')) return true;
                            return false;
                        }
                    };

                    html2canvas(exportArea, html2canvasOptions).then(function(canvas) {
                        // Cacher immédiatement l'en-tête fantôme
                        if (pdfHeader) pdfHeader.style.display = 'none';

                        // Image compressée à 95% pour une qualité optimale sans perte visible
                        const imgData = canvas.toDataURL('image/jpeg', 0.95); 
                        
                        const { jsPDF } = window.jspdf;
                        const pdf = new jsPDF({ orientation: 'p', unit: 'mm', format: 'a4', compress: true });

                        const pdfWidth = pdf.internal.pageSize.getWidth();
                        const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
                        
                        // On insère l'image tout en haut (y=0) puisque le titre est DANS l'image désormais
                        pdf.addImage(imgData, 'JPEG', 0, 0, pdfWidth, pdfHeight);
                        
                        pdf.save('SyDRA_Rapport_Global.pdf');

                        // Restaurer l'UI
                        if (typeof map !== 'undefined' && map.dragging) map.dragging.enable();
                        if (btnText && btnLoader) {
                            btn.classList.remove('disabled');
                            btnText.classList.remove('d-none');
                            btnLoader.classList.add('d-none');
                        }
                        
                        if (window.toastr) {
                            toastr.success('Rapport PDF généré avec succès !');
                        }

                    }).catch(function(error) {
                        console.error('Erreur PDF:', error);
                        if (pdfHeader) pdfHeader.style.display = 'none'; // Sécurité
                        if (typeof map !== 'undefined' && map.dragging) map.dragging.enable();
                        if (btnText && btnLoader) {
                            btn.classList.remove('disabled');
                            btnText.classList.remove('d-none');
                            btnLoader.classList.add('d-none');
                        }
                        if (window.toastr) {
                            toastr.error('Erreur lors de la génération.');
                        }
                    });

                }, 2000); 
            });
        }
    }

    function bindRestrictedDetailLinks() {
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
    }

    function remindDraftIfNeeded() {
        if (IS_DECISION_ROLE || !USER_DRAFT_SUMMARY || !USER_DRAFT_SUMMARY.id) {
            return;
        }
        if (!(window.Swal && typeof window.Swal.fire === 'function')) {
            return;
        }

        var reminderKey = 'sydraDraftReminderShown';
        try {
            if (window.sessionStorage.getItem(reminderKey) === '1') {
                return;
            }
            window.sessionStorage.setItem(reminderKey, '1');
        } catch (e) {
            // stockage indisponible: on continue sans blocage
        }

        window.Swal.fire({
            icon: 'info',
            title: 'Brouillon en attente',
            text: 'Vous avez un rapportage en brouillon non soumis. Voulez-vous le continuer maintenant ?',
            showCancelButton: true,
            confirmButtonText: 'Oui',
            cancelButtonText: 'Plus tard',
            confirmButtonColor: '#005BBB',
            toast: false
        }).then(function (result) {
            if (result.isConfirmed) {
                window.location.href = '?page=rapportage-creer-wizar&id_brouillon=' + Number(USER_DRAFT_SUMMARY.id || 0);
            }
        });
    }

    function boot() {
        initMap();
        initCharts();
        bindFilterForm();
        bindDraftCollisionLinks();
        bindRestrictedDetailLinks();
        remindDraftIfNeeded();

        var initialCharts = staticChartPayload || { trend: { labels: ['Global'], values: [0] }, severity: { labels: ['Faible'], values: [0] } };
        var initialMarkers = staticCloudMarkers || [];
        updateCharts(initialCharts);
        updateSummaries(initialCharts, initialMarkers);

        var form = document.getElementById('filterForm');
        var initialParams = form ? new URLSearchParams(new FormData(form)).toString() : '';
        var hasActiveFilters = false;
        if (form) {
            ['date_debut', 'date_fin', 'organisation_id'].forEach(function (name) {
                var field = form.querySelector('[name="' + name + '"]');
                if (field && String(field.value || '').trim() !== '') {
                    hasActiveFilters = true;
                }
            });
        }

        if (initialParams) {
            applyFilter(initialParams, !hasActiveFilters);
        } else {
            applyFilter('', true);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
</div>
<!-- /report-hub-container -->
