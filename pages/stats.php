<?php
/** @var array|null $authUser */

$sydraLogoPath = 'assets/img/sydra-logo/BLEU-PRIMARY-SYDRA-LOGO.png';
$leaderLogoPath = trim((string) ($authUser['logo_path'] ?? $authUser['avatar_path'] ?? ''));
$leaderDisplayName = trim((string) ($authUser['organization_name'] ?? $authUser['full_name'] ?? 'Lead GTMP'));
?>

<style>
    .advanced-stats-page {
        --sydra-primary: #005bbb;
        --sydra-primary-soft: rgba(0, 91, 187, 0.12);
    }

    .advanced-stats-page .premium-card {
        background: #f8fbff;
        border: 1px solid #e7eff8;
    }

    .advanced-stats-page .kpi-card {
        background: linear-gradient(130deg, #ffffff 0%, #f3f8ff 100%);
        border: 1px solid #dbe8f5;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .advanced-stats-page .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 18px rgba(0, 35, 70, 0.09);
    }

    .advanced-stats-page .kpi-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: var(--sydra-primary-soft);
        color: var(--sydra-primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }

    .advanced-stats-page .kpi-label {
        font-size: 0.82rem;
        color: #64748b;
        margin-bottom: 0;
    }

    .advanced-stats-page .kpi-value {
        font-size: 1.45rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }

    .advanced-stats-page .chart-wrapper {
        min-height: 340px;
        position: relative;
    }

    .advanced-stats-page .spinner-overlay {
        position: absolute;
        inset: 0;
        background: rgba(248, 251, 255, 0.8);
        display: none;
        align-items: center;
        justify-content: center;
        border-radius: 1rem;
        z-index: 10;
    }

    .advanced-stats-page.is-loading .spinner-overlay {
        display: flex;
    }
</style>

<div class="advanced-stats-page" id="advanced-stats-page">
    <div class="card bg-light shadow-sm rounded-4 border-0 premium-card mb-4">
        <div class="card-body p-3 p-lg-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                <div>
                    <h1 class="h4 mb-1">Statistiques avancées et décisionnelles</h1>
                    <p class="text-muted mb-0">Pilotage stratégique SyDRA pour la coordination humanitaire et la prise de décision.</p>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <a class="btn btn-outline-success btn-sm" href="api/export_advanced_stats_xlsx.php" id="btn-export-xlsx">
                        <i class="fa-regular fa-file-excel me-1"></i>Données brutes (Excel)
                    </a>
                </div>
            </div>

            <div class="row g-3 align-items-end">
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small mb-1" for="stats-date-from">Date de début</label>
                    <input type="date" class="form-control" id="stats-date-from">
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small mb-1" for="stats-date-to">Date de fin</label>
                    <input type="date" class="form-control" id="stats-date-to">
                </div>
                <div class="col-lg-3 col-md-4">
                    <label class="form-label small mb-1" for="stats-organization">Organisation</label>
                    <select class="form-select" id="stats-organization">
                        <option value="">Toutes les organisations</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small mb-1" for="stats-territory">Territoire</label>
                    <select class="form-select" id="stats-territory">
                        <option value="">Tous les territoires</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small mb-1" for="stats-severity">Niveau de gravité</label>
                    <select class="form-select" id="stats-severity">
                        <option value="">Toutes les gravités</option>
                        <option value="critique">Critique</option>
                        <option value="elevee">Élevée</option>
                        <option value="moyenne">Moyenne</option>
                        <option value="faible">Faible</option>
                    </select>
                </div>
                <div class="col-lg-1 col-md-4 d-grid">
                    <button class="btn btn-primary" type="button" id="btn-refresh-stats">
                        <i class="fa-solid fa-rotate me-1"></i>Actualiser
                    </button>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-3 pt-2 border-top">
                <button class="btn btn-outline-danger btn-sm" type="button" id="btn-export-pdf">
                    <i class="fa-regular fa-file-pdf me-1"></i>Exporter le Rapport (PDF)
                </button>
            </div>
        </div>
    </div>

    <div id="stats-report-content">
        <div class="row g-3 mb-3">
            <div class="col-xl-3 col-md-6">
                <div class="card bg-light shadow-sm rounded-4 border-0 kpi-card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="kpi-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                        <div>
                            <p class="kpi-label">Total des Incidents</p>
                            <p class="kpi-value" id="kpi-incidents">0</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-light shadow-sm rounded-4 border-0 kpi-card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="kpi-icon"><i class="fa-solid fa-user-injured"></i></span>
                        <div>
                            <p class="kpi-label">Total des Victimes</p>
                            <p class="kpi-value" id="kpi-victims">0</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-light shadow-sm rounded-4 border-0 kpi-card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="kpi-icon"><i class="fa-solid fa-house-crack"></i></span>
                        <div>
                            <p class="kpi-label">Ménages Déplacés</p>
                            <p class="kpi-value" id="kpi-displaced">0</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-light shadow-sm rounded-4 border-0 kpi-card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="kpi-icon"><i class="fa-solid fa-map-location-dot"></i></span>
                        <div>
                            <p class="kpi-label">Territoire le plus touché</p>
                            <p class="kpi-value" id="kpi-territory">-</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-6">
                <div class="card bg-light shadow-sm rounded-4 border-0 premium-card h-100">
                    <div class="card-body position-relative">
                        <div class="spinner-overlay"><div class="spinner-border text-primary" role="status"></div></div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h2 class="h6 mb-0">Évolution Temporelle des Incidents</h2>
                            <span class="badge text-bg-light border" id="evolution-granularity">Jour</span>
                        </div>
                        <div class="chart-wrapper"><canvas id="chart-evolution"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card bg-light shadow-sm rounded-4 border-0 premium-card h-100">
                    <div class="card-body position-relative">
                        <div class="spinner-overlay"><div class="spinner-border text-primary" role="status"></div></div>
                        <h2 class="h6 mb-2">Répartition par Gravité</h2>
                        <div class="chart-wrapper"><canvas id="chart-severity"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card bg-light shadow-sm rounded-4 border-0 premium-card h-100">
                    <div class="card-body position-relative">
                        <div class="spinner-overlay"><div class="spinner-border text-primary" role="status"></div></div>
                        <h2 class="h6 mb-2">Top 5 des Organisations Rapportantes</h2>
                        <div class="chart-wrapper"><canvas id="chart-top-organizations"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card bg-light shadow-sm rounded-4 border-0 premium-card h-100">
                    <div class="card-body position-relative">
                        <div class="spinner-overlay"><div class="spinner-border text-primary" role="status"></div></div>
                        <h2 class="h6 mb-2">Impact par Territoire</h2>
                        <div class="chart-wrapper"><canvas id="chart-territory-impact"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
(function () {
    function boot() {
        var pageEl = document.getElementById('advanced-stats-page');
        if (!pageEl) {
            return;
        }
        if (pageEl.dataset.statsReady === '1') {
            return;
        }
        if (!window.Chart) {
            window.setTimeout(boot, 80);
            return;
        }
        pageEl.dataset.statsReady = '1';

        var charts = {
            evolution: null,
            severity: null,
            topOrganizations: null,
            territoryImpact: null
        };

    var dateFromInput = document.getElementById('stats-date-from');
    var dateToInput = document.getElementById('stats-date-to');
    var organizationSelect = document.getElementById('stats-organization');
    var territorySelect = document.getElementById('stats-territory');
    var severitySelect = document.getElementById('stats-severity');
    var refreshBtn = document.getElementById('btn-refresh-stats');
    var exportPdfBtn = document.getElementById('btn-export-pdf');
    var exportXlsxBtn = document.getElementById('btn-export-xlsx');
    var evolutionGranularityBadge = document.getElementById('evolution-granularity');

    var kpiIncidents = document.getElementById('kpi-incidents');
    var kpiVictims = document.getElementById('kpi-victims');
    var kpiDisplaced = document.getElementById('kpi-displaced');
    var kpiTerritory = document.getElementById('kpi-territory');
    var sydraLogoPath = <?= json_encode($sydraLogoPath, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var leaderLogoPath = <?= json_encode($leaderLogoPath, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var leaderDisplayName = <?= json_encode($leaderDisplayName, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    function formatNumber(value) {
        return Number(value || 0).toLocaleString('fr-FR');
    }

    function showLoading(isLoading) {
        if (isLoading) {
            pageEl.classList.add('is-loading');
            refreshBtn.setAttribute('disabled', 'disabled');
            return;
        }
        pageEl.classList.remove('is-loading');
        refreshBtn.removeAttribute('disabled');
    }

    function getFilters() {
        return {
            date_from: String(dateFromInput.value || '').trim(),
            date_to: String(dateToInput.value || '').trim(),
            organization_id: String(organizationSelect.value || '').trim(),
            territory: String(territorySelect.value || '').trim(),
            severity: String(severitySelect.value || '').trim()
        };
    }

    function toQueryString(params) {
        var search = new URLSearchParams();
        Object.keys(params).forEach(function (key) {
            var value = params[key];
            if (value !== null && value !== undefined && String(value).trim() !== '') {
                search.set(key, String(value));
            }
        });
        return search.toString();
    }

    function updateExcelLink(filters) {
        var qs = toQueryString(filters);
        exportXlsxBtn.setAttribute('href', 'api/export_advanced_stats_xlsx.php' + (qs ? ('?' + qs) : ''));
    }

    function updateOptions(selectEl, values, selected, getValue, getLabel, emptyLabel) {
        var currentSelected = selected || '';
        selectEl.innerHTML = '';

        var defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = emptyLabel;
        selectEl.appendChild(defaultOption);

        values.forEach(function (item) {
            var option = document.createElement('option');
            option.value = getValue(item);
            option.textContent = getLabel(item);
            if (String(option.value) === String(currentSelected)) {
                option.selected = true;
            }
            selectEl.appendChild(option);
        });
    }

    function ensureChart(instance, canvasId, config) {
        var ctx = document.getElementById(canvasId);
        if (!ctx) {
            return instance;
        }

        if (instance) {
            instance.data = config.data;
            instance.options = config.options;
            instance.update();
            return instance;
        }

        return new window.Chart(ctx, config);
    }

    function updateKpis(kpis) {
        kpiIncidents.textContent = formatNumber(kpis.total_incidents || 0);
        kpiVictims.textContent = formatNumber(kpis.total_victims || 0);
        kpiDisplaced.textContent = formatNumber(kpis.total_displaced_households || 0);
        kpiTerritory.textContent = String(kpis.most_affected_territory || '-');
    }

    function updateCharts(payload) {
        var chartsData = payload.charts || {};
        var meta = payload.meta || {};

        evolutionGranularityBadge.textContent = meta.bucket === 'week' ? 'Semaine' : 'Jour';

        charts.evolution = ensureChart(charts.evolution, 'chart-evolution', {
            type: 'line',
            data: {
                labels: chartsData.evolution && chartsData.evolution.labels ? chartsData.evolution.labels : [],
                datasets: [{
                    label: 'Incidents',
                    data: chartsData.evolution && chartsData.evolution.values ? chartsData.evolution.values : [],
                    borderColor: '#005bbb',
                    backgroundColor: 'rgba(0, 91, 187, 0.2)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#e5edf7' } },
                    x: { grid: { color: '#f0f4fb' } }
                }
            }
        });

        charts.severity = ensureChart(charts.severity, 'chart-severity', {
            type: 'doughnut',
            data: {
                labels: chartsData.severity && chartsData.severity.labels ? chartsData.severity.labels : [],
                datasets: [{
                    data: chartsData.severity && chartsData.severity.values ? chartsData.severity.values : [],
                    backgroundColor: ['#dc2626', '#ea580c', '#f59e0b', '#2563eb']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: { legend: { position: 'bottom' } }
            }
        });

        charts.topOrganizations = ensureChart(charts.topOrganizations, 'chart-top-organizations', {
            type: 'bar',
            data: {
                labels: chartsData.top_organizations && chartsData.top_organizations.labels ? chartsData.top_organizations.labels : [],
                datasets: [{
                    label: 'Rapports',
                    data: chartsData.top_organizations && chartsData.top_organizations.values ? chartsData.top_organizations.values : [],
                    backgroundColor: '#005bbb'
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

        charts.territoryImpact = ensureChart(charts.territoryImpact, 'chart-territory-impact', {
            type: 'bar',
            data: {
                labels: chartsData.territory_impact && chartsData.territory_impact.labels ? chartsData.territory_impact.labels : [],
                datasets: [{
                    label: 'Victimes',
                    data: chartsData.territory_impact && chartsData.territory_impact.victims ? chartsData.territory_impact.victims : [],
                    backgroundColor: 'rgba(220, 38, 38, 0.8)'
                }, {
                    label: 'Ménages déplacés',
                    data: chartsData.territory_impact && chartsData.territory_impact.displaced_households ? chartsData.territory_impact.displaced_households : [],
                    backgroundColor: 'rgba(0, 91, 187, 0.8)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: { stacked: true, beginAtZero: true, grid: { color: '#e5edf7' } }
                }
            }
        });
    }

    function updateFilterOptions(payload, activeFilters) {
        var filterData = payload.filters || {};
        var organizations = filterData.organizations || [];
        var territories = filterData.territories || [];

        updateOptions(
            organizationSelect,
            organizations,
            activeFilters.organization_id,
            function (item) { return String(item.id || ''); },
            function (item) { return String(item.name || 'Organisation') + ' (' + formatNumber(item.total || 0) + ')'; },
            'Toutes les organisations'
        );

        updateOptions(
            territorySelect,
            territories,
            activeFilters.territory,
            function (item) { return String(item.name || ''); },
            function (item) { return String(item.name || 'Territoire') + ' (' + formatNumber(item.total || 0) + ')'; },
            'Tous les territoires'
        );
    }

    function showError(message) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: message,
                confirmButtonColor: '#005bbb'
            });
        }
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatDateTimeNow() {
        return new Date().toLocaleString('fr-FR', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function fetchRowsForPdf(filters) {
        var query = toQueryString(filters);
        return fetch('api/get_advanced_stats_rows.php' + (query ? ('?' + query) : ''), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function (payload) {
                if (!payload || payload.ok !== true || !Array.isArray(payload.rows)) {
                    throw new Error(payload && payload.message ? payload.message : 'Réponse de lignes invalide.');
                }
                return payload.rows;
            });
    }

    function buildPdfFileName() {
        var now = new Date();
        var pad = function (v) { return String(v).padStart(2, '0'); };
        var stamp = now.getFullYear()
            + pad(now.getMonth() + 1)
            + pad(now.getDate())
            + '_'
            + pad(now.getHours())
            + pad(now.getMinutes())
            + pad(now.getSeconds());
        return 'sydra_rapport_decisionnel_' + stamp + '.pdf';
    }

    function buildReportSnapshotHtml() {
        var source = document.getElementById('stats-report-content');
        if (!source) {
            return '';
        }

        var clone = source.cloneNode(true);
        var canvasIds = ['chart-evolution', 'chart-severity', 'chart-top-organizations', 'chart-territory-impact'];

        canvasIds.forEach(function (id) {
            var originalCanvas = document.getElementById(id);
            var clonedCanvas = clone.querySelector('#' + id);
            if (!originalCanvas || !clonedCanvas || typeof originalCanvas.toDataURL !== 'function') {
                return;
            }

            var image = document.createElement('img');
            image.src = originalCanvas.toDataURL('image/png');
            image.alt = id;
            image.style.width = '100%';
            image.style.maxWidth = '100%';
            image.style.height = 'auto';
            image.style.display = 'block';
            image.style.border = '1px solid #e2e8f0';
            image.style.borderRadius = '8px';
            clonedCanvas.parentNode.replaceChild(image, clonedCanvas);
        });

        return clone.innerHTML;
    }

    function buildPdfDocument(rows, filters) {
        var root = document.createElement('div');
        root.style.padding = '14px';
        root.style.background = '#ffffff';
        root.style.color = '#0f172a';
        root.style.fontFamily = 'Arial, sans-serif';

        var filterSummary = [
            'Date début: ' + (filters.date_from || '-'),
            'Date fin: ' + (filters.date_to || '-'),
            'Organisation: ' + (organizationSelect && organizationSelect.selectedOptions[0] ? organizationSelect.selectedOptions[0].textContent : 'Toutes les organisations'),
            'Territoire: ' + (filters.territory || 'Tous les territoires'),
            'Gravité: ' + (filters.severity || 'Toutes')
        ].join(' | ');

        var logoRightHtml = leaderLogoPath
            ? '<img src="' + escapeHtml(leaderLogoPath) + '" style="height:44px;width:44px;border-radius:999px;object-fit:cover;border:1px solid #dbe8f5;">'
            : '<div style="height:44px;width:44px;border-radius:999px;background:#e2e8f0;display:inline-flex;align-items:center;justify-content:center;font-weight:700;color:#334155;">L</div>';

        var rowsHtml = rows.map(function (row) {
            return '<tr>'
                + '<td style="border:1px solid #e2e8f0;padding:5px;">' + escapeHtml(row.id || '') + '</td>'
                + '<td style="border:1px solid #e2e8f0;padding:5px;">' + escapeHtml(row.created_at || '') + '</td>'
                + '<td style="border:1px solid #e2e8f0;padding:5px;">' + escapeHtml(row.organization_name || '') + '</td>'
                + '<td style="border:1px solid #e2e8f0;padding:5px;">' + escapeHtml(row.territory || '') + '</td>'
                + '<td style="border:1px solid #e2e8f0;padding:5px;">' + escapeHtml(row.incident_type || '') + '</td>'
                + '<td style="border:1px solid #e2e8f0;padding:5px;">' + escapeHtml(row.severity || '') + '</td>'
                + '<td style="border:1px solid #e2e8f0;padding:5px;">' + escapeHtml(row.workflow_status || '') + '</td>'
                + '<td style="border:1px solid #e2e8f0;padding:5px;text-align:right;">' + escapeHtml(row.victims_count || 0) + '</td>'
                + '<td style="border:1px solid #e2e8f0;padding:5px;text-align:right;">' + escapeHtml(row.displaced_households || 0) + '</td>'
                + '</tr>';
        }).join('');

        root.innerHTML = ''
            + '<div style="display:flex;justify-content:space-between;align-items:center;border-bottom:2px solid #005bbb;padding-bottom:8px;margin-bottom:10px;">'
            + '<div style="display:flex;align-items:center;gap:10px;">'
            + '<img src="' + escapeHtml(sydraLogoPath) + '" style="height:42px;object-fit:contain;">'
            + '<div>'
            + '<div style="font-size:18px;font-weight:700;color:#005bbb;">Rapport Statistique SyDRA</div>'
            + '<div style="font-size:12px;color:#64748b;">Généré le ' + escapeHtml(formatDateTimeNow()) + '</div>'
            + '</div>'
            + '</div>'
            + '<div style="display:flex;align-items:center;gap:8px;">'
            + '<div style="text-align:right;">'
            + '<div style="font-size:12px;color:#64748b;">Responsable</div>'
            + '<div style="font-size:13px;font-weight:600;">' + escapeHtml(leaderDisplayName || 'Lead GTMP') + '</div>'
            + '</div>'
            + logoRightHtml
            + '</div>'
            + '</div>'
            + '<div style="font-size:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:8px;margin-bottom:10px;">'
            + '<strong>Filtres appliqués:</strong> ' + escapeHtml(filterSummary)
            + '</div>'
            + '<div>' + buildReportSnapshotHtml() + '</div>'
            + '<h3 style="margin-top:12px;margin-bottom:8px;color:#0f172a;">Liste des données filtrées</h3>'
            + '<table style="width:100%;border-collapse:collapse;font-size:11px;">'
            + '<thead>'
            + '<tr style="background:#eef5ff;color:#0f172a;">'
            + '<th style="border:1px solid #dbe8f5;padding:5px;">ID</th>'
            + '<th style="border:1px solid #dbe8f5;padding:5px;">Date</th>'
            + '<th style="border:1px solid #dbe8f5;padding:5px;">Organisation</th>'
            + '<th style="border:1px solid #dbe8f5;padding:5px;">Territoire</th>'
            + '<th style="border:1px solid #dbe8f5;padding:5px;">Incident</th>'
            + '<th style="border:1px solid #dbe8f5;padding:5px;">Gravité</th>'
            + '<th style="border:1px solid #dbe8f5;padding:5px;">Statut</th>'
            + '<th style="border:1px solid #dbe8f5;padding:5px;">Victimes</th>'
            + '<th style="border:1px solid #dbe8f5;padding:5px;">Ménages</th>'
            + '</tr>'
            + '</thead>'
            + '<tbody>' + rowsHtml + '</tbody>'
            + '</table>';

        return root;
    }

    function loadAdvancedStats() {
        var filters = getFilters();
        var query = toQueryString(filters);

        showLoading(true);
        updateExcelLink(filters);

        fetch('api/get_advanced_stats.php' + (query ? ('?' + query) : ''), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Réponse HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function (data) {
                if (!data || data.ok !== true || !data.data) {
                    throw new Error((data && data.message) ? data.message : 'Format de réponse invalide.');
                }

                updateFilterOptions(data.data, filters);
                updateKpis(data.data.kpis || {});
                updateCharts(data.data);
            })
            .catch(function (error) {
                showError(error.message || 'Impossible de charger les statistiques avancées.');
            })
            .finally(function () {
                showLoading(false);
            });
    }

    refreshBtn.addEventListener('click', loadAdvancedStats);

    [dateFromInput, dateToInput, organizationSelect, territorySelect, severitySelect].forEach(function (el) {
        el.addEventListener('change', function () {
            updateExcelLink(getFilters());
        });
    });

    exportPdfBtn.addEventListener('click', function () {
        if (typeof window.html2pdf === 'undefined') {
            showError('Export PDF indisponible sur ce navigateur.');
            return;
        }

        var filters = getFilters();
        fetchRowsForPdf(filters)
            .then(function (rows) {
                var pdfContainer = buildPdfDocument(rows, filters);
                document.body.appendChild(pdfContainer);

                var options = {
                    margin: 6,
                    filename: buildPdfFileName(),
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2, useCORS: true },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' },
                    pagebreak: { mode: ['css', 'legacy'] }
                };

                return window.html2pdf().set(options).from(pdfContainer).save().then(function () {
                    pdfContainer.remove();
                }).catch(function (err) {
                    pdfContainer.remove();
                    throw err;
                });
            })
            .catch(function (error) {
                showError(error && error.message ? error.message : 'Échec export PDF.');
            });
    });

    var today = new Date();
    var thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
    dateToInput.value = today.toISOString().slice(0, 10);
    dateFromInput.value = thirtyDaysAgo.toISOString().slice(0, 10);

        loadAdvancedStats();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
