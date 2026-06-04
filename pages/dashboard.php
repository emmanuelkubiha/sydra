<?php
/** @var array|null $authUser */
/** @var array<int, array<string, mixed>> $dashboardKpis */
/** @var array<int, array<string, mixed>> $dashboardRecentReports */
/** @var array<int, array<string, mixed>> $dashboardMapAlerts */

$role = strtoupper((string) ($authUser['role'] ?? $authUser['role_code'] ?? 'REPORTER'));
$isDecisionRole = in_array($role, ['ADMIN', 'CLUSTER_LEADER', 'LEAD_GTMP', 'GTMP_LEAD', 'CLUSTER_PROTECTION'], true);
$displayName = trim((string) ($authUser['organization_name'] ?? $authUser['full_name'] ?? 'Organisation'));
$subtitle = $isDecisionRole
    ? 'Espace de coordination GTMP'
    : 'Votre espace de rapportage';

$tips = [
    'Un rapport Flash doit rester concis, factuel et daté.',
    'Utilisez le mode IA pour structurer rapidement vos notes longues.',
    'Vérifiez vos coordonnées GPS avant soumission.',
    'Ajoutez un commentaire explicite lors d\'une demande de complément.',
    'Mettez à jour votre profil organisationnel pour améliorer le suivi.',
];
$tipOfDay = $tips[random_int(0, count($tips) - 1)];

$mapPayload = json_encode($dashboardMapAlerts ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
if (!is_string($mapPayload)) {
    $mapPayload = '[]';
}

$statusNormalized = static function (string $status): string {
    $s = strtolower(trim($status));
    return str_replace(['é', 'è', 'ê'], 'e', $s);
};
?>

<style>
.dashboard-shell {
    display: grid;
    gap: 1rem;
}

.dashboard-hero {
    border: 1px solid #d8e6f7;
    border-radius: 18px;
    padding: 1.1rem 1.2rem;
    background:
        radial-gradient(circle at 95% -15%, rgba(0, 91, 187, 0.16), transparent 45%),
        linear-gradient(140deg, #ffffff 0%, #eef5ff 100%);
}

.dashboard-kpi-card {
    border: 1px solid #d9e6f6;
    border-radius: 14px;
    background: #ffffff;
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.04);
}

.dashboard-kpi-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: inline-grid;
    place-items: center;
    background: rgba(0, 91, 187, 0.1);
    color: #005BBB;
}

.dashboard-tip-card {
    border: 1px solid #d7e6f7;
    border-radius: 14px;
    background: linear-gradient(160deg, #ffffff 0%, #edf4ff 100%);
    box-shadow: 0 10px 20px rgba(0, 91, 187, 0.07);
}

.dashboard-activity-card {
    border: 1px solid #dde8f6;
    border-radius: 14px;
    background: #ffffff;
}

.dashboard-activity-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 0.65rem;
}

.dashboard-activity-list li {
    border: 1px solid #e5edf8;
    border-radius: 10px;
    padding: 0.7rem 0.75rem;
    background: #fbfdff;
}

.dashboard-ops-card {
    border: 1px solid #dbe8f5;
    border-radius: 14px;
    background: linear-gradient(170deg, #ffffff 0%, #f5f9ff 100%);
}

.dashboard-map-shell {
    height: 360px;
    border-radius: 12px;
    border: 1px solid #dbe8f5;
    overflow: hidden;
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
    border-radius: 12px;
}
</style>

<div class="dashboard-shell">
    <section class="dashboard-hero">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h1 class="mb-1">Bienvenue sur SyDRA, <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="text-muted mb-0"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <?php if ($isDecisionRole): ?>
                <a href="?page=stats" class="btn btn-primary"><i class="fa-solid fa-chart-line me-1"></i>Statistiques avancées</a>
            <?php else: ?>
                <a href="?page=rapportage-creer-wizar" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>Nouveau rapport</a>
            <?php endif; ?>
        </div>
    </section>

    <section class="row g-3">
        <?php foreach (($dashboardKpis ?? []) as $kpi): ?>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="dashboard-kpi-card h-100 p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="dashboard-kpi-icon"><i class="fa-solid <?= htmlspecialchars((string) ($kpi['icon'] ?? 'fa-chart-simple'), ENT_QUOTES, 'UTF-8'); ?>"></i></span>
                        <strong class="h4 mb-0"><?= (int) ($kpi['value'] ?? 0); ?></strong>
                    </div>
                    <p class="mb-0 text-muted small"><?= htmlspecialchars((string) ($kpi['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="row g-3">
        <div class="col-lg-5">
            <div class="dashboard-tip-card p-3 mb-3">
                <h2 class="h5 mb-2"><i class="fa-solid fa-lightbulb me-1 text-warning"></i>Conseil du jour</h2>
                <p class="mb-0"><?= htmlspecialchars($tipOfDay, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="dashboard-activity-card p-3">
                <h2 class="h5 mb-2">Impact territorial</h2>
                <div class="dashboard-territory-shell">
                    <canvas id="dashboard-territory-impact-chart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="dashboard-activity-card h-100 p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h5 mb-0">Activités récentes</h2>
                    <span class="badge text-bg-light border">5 derniers rapports</span>
                </div>

                <?php if (($dashboardRecentReports ?? []) === []): ?>
                    <p class="text-muted mb-0">Aucune activité récente disponible.</p>
                <?php else: ?>
                    <ul class="dashboard-activity-list">
                        <?php foreach ($dashboardRecentReports as $report): ?>
                            <?php
                            $reportId = (int) ($report['id'] ?? 0);
                            $status = (string) ($report['workflow_status'] ?? 'Brouillon');
                            $normalizedStatus = $statusNormalized($status);
                            $isDraft = in_array($normalizedStatus, ['brouillon', 'draft'], true);
                            $detailHref = $isDraft
                                ? ('?page=rapportage-creer-wizar&id_brouillon=' . $reportId)
                                : ('?page=rapportage-details&id=' . $reportId);
                            $onclick = $isDraft
                                ? 'return confirmDraftResume(event);'
                                : '';
                            ?>
                            <li>
                                <a href="<?= htmlspecialchars($detailHref, ENT_QUOTES, 'UTF-8'); ?>"
                                   class="text-decoration-none text-reset d-block"
                                   <?= $onclick !== '' ? ('onclick="' . htmlspecialchars($onclick, ENT_QUOTES, 'UTF-8') . '"') : ''; ?>>
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                                        <strong>#<?= $reportId; ?> • <?= htmlspecialchars((string) ($report['report_type'] ?? 'FLASH'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <small class="text-muted"><?= htmlspecialchars((string) ($report['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
                                    </div>
                                    <div class="small text-muted">
                                        <?= htmlspecialchars((string) ($report['organization_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                        • <?= htmlspecialchars((string) ($report['location_text'] ?? 'Non précisée'), ENT_QUOTES, 'UTF-8'); ?>
                                        • <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="dashboard-ops-card p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h5 mb-0">Aperçu Opérationnel</h2>
            <span class="badge text-bg-light border"><?= count($dashboardMapAlerts ?? []); ?> incident(s)</span>
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
                <div class="dashboard-activity-card h-100 p-3">
                    <h3 class="h6 mb-2">Répartition des incidents</h3>
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

    function boot() {
        var mapEl = document.getElementById('dashboard-operational-map');
        var mapEmptyEl = document.getElementById('dashboard-map-empty');
        if (!mapEl) {
            return;
        }
        if (mapEl.dataset.dashboardMapReady === '1') {
            return;
        }
        if (!window.L) {
            window.setTimeout(boot, 80);
            return;
        }
        mapEl.dataset.dashboardMapReady = '1';

    function normalizeText(value) {
        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
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

    var alerts = [];
    try {
        alerts = JSON.parse(mapEl.getAttribute('data-alerts') || '[]');
    } catch (e) {
        alerts = [];
    }

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
            var marker = window.L.circleMarker(coords, {
                radius: 8,
                color: color,
                weight: 2,
                fillColor: color,
                fillOpacity: 0.78
            }).addTo(markersLayer);

            marker.bindPopup(
                '<div class="map-popup">'
                + '<strong>' + String(item.report_type || 'FLASH') + '</strong><br>'
                + '<span><b>Organisation:</b> ' + String(item.organization_name || 'Organisation') + '</span><br>'
                + '<span><b>Lieu:</b> ' + String(item.location_text || item.locality || item.province || 'Non précisée') + '</span><br>'
                + '<span><b>Statut:</b> ' + String(item.workflow_status || 'Brouillon') + '</span><br>'
                + '<a class="btn btn-sm btn-primary mt-1" href="?page=rapportage-voir&id=' + reportId + '">Voir l\'alerte</a>'
                + '</div>'
            );

            points.push(coords);
        });

        if (mapEmptyEl) {
            mapEmptyEl.style.display = points.length === 0 ? 'flex' : 'none';
        }

        if (points.length > 0) {
            map.fitBounds(window.L.latLngBounds(points).pad(0.18));
        } else {
            map.setView([-3.0, 27.5], 7);
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
