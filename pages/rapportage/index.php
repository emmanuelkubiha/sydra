<?php
/**
 * Hub d'accueil du module Rapportage SyDRA.
 * UI premium + carte Leaflet verrouillee sur Sud-Kivu / Maniema.
 */

/** @var array<int, array<string, mixed>> $rapportageMapAlerts */
$alertsPayload = json_encode($rapportageMapAlerts ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
if (!is_string($alertsPayload)) {
    $alertsPayload = '[]';
}
?>

<div class="card shadow-sm rounded-4 report-hub-hero border-0">
    <div class="report-hub-bg"></div>
    <div class="report-hub-content">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
            <div>
                <h1 class="mb-2">Bienvenue sur le Hub de Rapportage SyDRA</h1>
                <p class="mb-0 text-muted">Soumettez vos alertes rapides (Flash) ou vos notes de monitoring structurées.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="?page=rapportage-mes-alertes" class="btn btn-light btn-sm shadow-sm">Mes alertes</a>
                <a href="?page=rapportage-coordination" class="btn btn-light btn-sm shadow-sm">Coordination</a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-6">
                <a href="?page=rapportage-creer-AI" class="hub-action-card hub-action-ai text-decoration-none">
                    <div class="hub-action-icon"><i class="fa-solid fa-robot"></i></div>
                    <div>
                        <h2>Assistant IA SyDRA</h2>
                        <p class="mb-0">Discutez avec notre IA, elle structurera l'alerte pour vous.</p>
                    </div>
                </a>
            </div>
            <div class="col-lg-6">
                <a href="?page=rapportage-creer-wizar" class="hub-action-card hub-action-manual text-decoration-none">
                    <div class="hub-action-icon"><i class="fa-solid fa-list-check"></i></div>
                    <div>
                        <h2>Formulaire classique (Wizard)</h2>
                        <p class="mb-0">Remplissez le rapport étape par étape.</p>
                    </div>
                </a>
            </div>
        </div>

        <div class="row g-2 stats-grid">
            <div class="col-6 col-lg-3">
                <div class="kpi-card kpi-blue shadow-sm rounded-4">
                    <small>Total Alertes (Mois)</small>
                    <strong>128</strong>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="kpi-card kpi-red shadow-sm rounded-4">
                    <small>Alertes Critiques</small>
                    <strong>17</strong>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="kpi-card kpi-orange shadow-sm rounded-4">
                    <small>En attente de validation</small>
                    <strong>24</strong>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="kpi-card kpi-green shadow-sm rounded-4">
                    <small>Rapports Validés</small>
                    <strong>87</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm rounded-4 border-0 mt-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <div>
            <h2 class="mb-1">Cartographie des incidents récents</h2>
            <p class="text-muted mb-0">Zone verrouillée sur Sud-Kivu et Maniema pour un suivi opérationnel ciblé.</p>
        </div>
        <span class="badge text-bg-light border">Vue terrain sécurisée</span>
    </div>

    <div id="rapportage-hub-map" class="hub-map rounded-4" data-alerts='<?= htmlspecialchars($alertsPayload, ENT_QUOTES, 'UTF-8'); ?>'></div>
</div>

<style>
.report-hub-hero {
    position: relative;
    overflow: hidden;
    border: 1px solid #dbeafe;
}
.report-hub-bg {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 15% 20%, rgba(0, 91, 187, 0.20), transparent 42%),
        radial-gradient(circle at 85% 10%, rgba(14, 165, 233, 0.14), transparent 38%),
        linear-gradient(140deg, #f8fbff 0%, #eef6ff 100%);
}
.report-hub-content {
    position: relative;
    z-index: 1;
}
.hub-action-card {
    display: flex;
    gap: 12px;
    align-items: center;
    border: 1px solid #dbeafe;
    border-radius: 16px;
    padding: 16px;
    min-height: 124px;
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}
.hub-action-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 18px 30px rgba(2, 6, 23, 0.09);
    border-color: #9cc5ff;
}
.hub-action-card h2 {
    font-size: 17px;
    margin: 0 0 5px;
    color: #0f172a;
}
.hub-action-card p {
    color: #334155;
}
.hub-action-icon {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #ffffff;
    flex: 0 0 auto;
}
.hub-action-ai {
    background: linear-gradient(140deg, #005bbb 0%, #3a86ff 65%, #7b61ff 100%);
}
.hub-action-ai h2,
.hub-action-ai p {
    color: #ffffff;
}
.hub-action-ai .hub-action-icon {
    background: rgba(255, 255, 255, 0.24);
}
.hub-action-manual {
    background: #ffffff;
}
.hub-action-manual .hub-action-icon {
    background: #005bbb;
}
.kpi-card {
    padding: 12px;
    display: grid;
    gap: 3px;
    border: 1px solid rgba(255, 255, 255, 0.25);
}
.kpi-card small {
    color: rgba(255, 255, 255, 0.88);
    font-weight: 600;
}
.kpi-card strong {
    color: #ffffff;
    font-size: 22px;
    line-height: 1;
}
.kpi-blue { background: #005BBB; }
.kpi-red { background: #E53E3E; }
.kpi-orange { background: #f97316; }
.kpi-green { background: #059669; }
.hub-map {
    width: 100%;
    height: 500px;
    border: 1px solid #dbeafe;
    overflow: hidden;
}
.leaflet-popup-content-wrapper {
    border-radius: 12px;
}
.leaflet-popup-content {
    margin: 10px 12px;
}
.map-alert-title {
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 4px;
}
.map-alert-meta {
    color: #475569;
    font-size: 12px;
}
</style>

<script>
(function () {
    function initMapWhenReady() {
        if (!window.L) {
            window.setTimeout(initMapWhenReady, 90);
            return;
        }

        var mapElement = document.getElementById('rapportage-hub-map');
        if (!mapElement || mapElement.dataset.ready === '1') {
            return;
        }
        mapElement.dataset.ready = '1';

        var southWest = window.L.latLng(-5.0, 25.0);
        var northEast = window.L.latLng(0.0, 29.5);
        var bounds = window.L.latLngBounds(southWest, northEast);

        var map = window.L.map(mapElement, {
            minZoom: 6,
            maxZoom: 12,
            maxBounds: bounds,
            maxBoundsViscosity: 1.0,
            zoomControl: true
        });

        map.setView([-3.0, 27.5], 7);

        window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        function normalize(value) {
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
                kindu: [-2.9508, 25.9464],
                kalima: [-2.6147, 26.5622]
            };

            var location = normalize(rawLocation);
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

        function urgencyColor(level) {
            var n = normalize(level);
            if (n.indexOf('crit') >= 0) {
                return '#E53E3E';
            }
            if (n.indexOf('ele') >= 0) {
                return '#f97316';
            }
            if (n.indexOf('moy') >= 0) {
                return '#f59e0b';
            }
            return '#005BBB';
        }

        var raw = mapElement.getAttribute('data-alerts') || '[]';
        var alerts = [];
        try {
            alerts = JSON.parse(raw);
        } catch (e) {
            alerts = [];
        }

        var markers = [];
        alerts.forEach(function (item) {
            var lat = Number(item.gps_lat || 0);
            var lng = Number(item.gps_lng || 0);
            var coords = null;
            if (!Number.isNaN(lat) && !Number.isNaN(lng) && lat !== 0 && lng !== 0) {
                coords = [lat, lng];
            } else {
                coords = resolveLocationFromText(item.location_text || item.province || '');
            }

            if (!coords) {
                return;
            }

            var color = urgencyColor(item.urgency_level || 'Moyenne');
            var reportId = Number(item.id || 0);
            var detailHref = '?page=rapportage-voir&id=' + reportId;
            var title = String(item.report_type || 'FLASH') + ' - ' + String(item.organization_name || 'Organisation');
            var meta = String(item.location_text || item.province || 'Localisation non précisée')
                + ' • '
                + String(item.workflow_status || 'Soumis');

            var marker = window.L.circleMarker(coords, {
                radius: 10,
                color: color,
                weight: 2,
                fillColor: color,
                fillOpacity: 0.8
            }).addTo(map);

            marker.bindPopup(
                '<div class="map-alert-title">' + title + '</div>'
                + '<div class="map-alert-meta">' + meta + '</div>'
                + '<div class="mt-2"><a class="btn btn-sm btn-primary" href="' + detailHref + '">Voir l\'alerte</a></div>'
            );

            markers.push(marker);
        });

        if (markers.length > 0) {
            var group = window.L.featureGroup(markers);
            map.fitBounds(group.getBounds().pad(0.18));
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMapWhenReady);
    } else {
        initMapWhenReady();
    }
})();
</script>
