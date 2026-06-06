<?php
$csrf = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
$wizardDraftPayload = json_encode($wizardDraftData ?? null, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
if (!is_string($wizardDraftPayload)) {
    $wizardDraftPayload = 'null';
}
$wizardDraftIdValue = (int) ($wizardDraftId ?? 0);
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bs-stepper/dist/css/bs-stepper.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">

<div class="card shadow-sm rounded-4 wizard-shell">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h1 class="mb-1">Saisie manuelle d'un rapport</h1>
            <p class="text-muted mb-0">Assistant guidé en 4 étapes pour documenter proprement un incident terrain.</p>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <button type="button" class="btn btn-outline-secondary" id="btn-save-draft-sticky">
                <i class="fa-regular fa-floppy-disk me-1"></i>Enregistrer le brouillon
            </button>
            <a href="?page=rapportage" class="btn btn-small">Retour au choix</a>
        </div>
    </div>

    <div id="report-stepper" class="bs-stepper">
        <div class="bs-stepper-header" role="tablist">
            <div class="step" data-target="#step-1">
                <button type="button" class="step-trigger" role="tab" aria-controls="step-1" id="step-1-trigger">
                    <span class="bs-stepper-circle">1</span>
                    <span class="bs-stepper-label">Localisation</span>
                </button>
            </div>
            <div class="line"></div>
            <div class="step" data-target="#step-2">
                <button type="button" class="step-trigger" role="tab" aria-controls="step-2" id="step-2-trigger">
                    <span class="bs-stepper-circle">2</span>
                    <span class="bs-stepper-label">Faits & Bilan</span>
                </button>
            </div>
            <div class="line"></div>
            <div class="step" data-target="#step-3">
                <button type="button" class="step-trigger" role="tab" aria-controls="step-3" id="step-3-trigger">
                    <span class="bs-stepper-circle">3</span>
                    <span class="bs-stepper-label">Analyse & Action</span>
                </button>
            </div>
            <div class="line"></div>
            <div class="step" data-target="#step-4">
                <button type="button" class="step-trigger" role="tab" aria-controls="step-4" id="step-4-trigger">
                    <span class="bs-stepper-circle">4</span>
                    <span class="bs-stepper-label">Pièces jointes</span>
                </button>
            </div>
        </div>

        <form id="report-wizard-form" class="bs-stepper-content" novalidate>
            <input type="hidden" name="csrf" value="<?= $csrf; ?>">
            <input type="hidden" name="status_action" id="status_action" value="Brouillon">
            <input type="hidden" name="draft_id" id="draft_id" value="<?= $wizardDraftIdValue; ?>">

            <div id="step-1" class="content" role="tabpanel" aria-labelledby="step-1-trigger">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Province *</label>
                        <input type="text" class="form-control" name="province" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Territoire *</label>
                        <input type="text" class="form-control" name="territory" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Zone de santé *</label>
                        <input type="text" class="form-control" name="health_zone" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Groupement *</label>
                        <input type="text" class="form-control" name="groupement" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Village *</label>
                        <input type="text" class="form-control" name="village" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Latitude GPS</label>
                        <input type="text" class="form-control gps-readonly" name="gps_lat" id="gps_lat" placeholder="Cliquez sur la carte" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Longitude GPS</label>
                        <input type="text" class="form-control gps-readonly" name="gps_lng" id="gps_lng" placeholder="Cliquez sur la carte" readonly>
                    </div>
                    <div class="col-12">
                        <label for="location-search-input" class="form-label">Rechercher un lieu (Ex: Minova)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="location-search-input" placeholder="Ex: Minova, Bukavu, Uvira...">
                            <button type="button" class="btn btn-outline-primary" id="btn-search-place">Rechercher</button>
                        </div>
                    </div>
                    <div class="col-12">
                        <div id="wizard-location-map" class="wizard-location-map" aria-label="Carte de localisation interactive"></div>
                        <small class="text-muted">Astuce: cliquez directement sur la carte pour remplir automatiquement Province, Territoire/Ville, Village et coordonnées GPS.</small>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-3">
                    <span></span>
                    <button type="button" class="btn btn-outline-primary" id="btn-geoloc">Obtenir ma position</button>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button type="button" class="btn wizard-next">Continuer</button>
                </div>
            </div>

            <div id="step-2" class="content" role="tabpanel" aria-labelledby="step-2-trigger">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Type d'incident *</label>
                        <input type="text" class="form-control" name="incident_type" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Niveau de gravité *</label>
                        <select class="form-select" name="urgency_level" required>
                            <option value="">Choisir...</option>
                            <option value="Faible">Faible</option>
                            <option value="Moyenne" selected>Moyenne</option>
                            <option value="Elevee">Elevée</option>
                            <option value="Critique">Critique</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nombre de victimes</label>
                        <input type="number" min="0" class="form-control" name="victims_count" value="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nombre de ménages déplacés</label>
                        <input type="number" min="0" class="form-control" name="displaced_households" value="0">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Description détaillée *</label>
                        <textarea class="form-control" name="description" rows="6" required></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-3">
                    <button type="button" class="btn btn-light wizard-prev">Retour</button>
                    <button type="button" class="btn wizard-next">Continuer</button>
                </div>
            </div>

            <div id="step-3" class="content" role="tabpanel" aria-labelledby="step-3-trigger">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Constats / Analyse *</label>
                        <textarea class="form-control" name="analyse" rows="5" required></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Besoins prioritaires *</label>
                        <textarea class="form-control" name="priority_needs" rows="5" required></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Recommandations *</label>
                        <textarea class="form-control" name="recommandations" rows="5" required></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-3">
                    <button type="button" class="btn btn-light wizard-prev">Retour</button>
                    <button type="button" class="btn wizard-next">Continuer</button>
                </div>
            </div>

            <div id="step-4" class="content" role="tabpanel" aria-labelledby="step-4-trigger">
                <label class="form-label">Pièces jointes</label>
                <div id="wizard-dropzone" class="dropzone rounded-4 border border-2"></div>
                <small class="text-muted d-block mt-2">Formats autorisés: jpg, jpeg, png, webp, pdf, doc, docx, xls, xlsx, txt.</small>

                <div class="d-flex justify-content-between mt-4 flex-wrap gap-2">
                    <button type="button" class="btn btn-light wizard-prev">Retour</button>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-outline-secondary" id="btn-save-draft">Enregistrer comme Brouillon</button>
                        <button type="button" class="btn btn-primary" id="btn-submit-cluster">Soumettre au Cluster</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.wizard-shell {
    border: 1px solid #dbeafe;
}
.wizard-shell .btn-primary,
.wizard-shell .wizard-next {
    background: #005BBB;
    border-color: #005BBB;
    color: #fff;
}
.wizard-shell .wizard-next:hover,
.wizard-shell .btn-primary:hover {
    background: #004b99;
    border-color: #004b99;
}
.bs-stepper .step-trigger {
    background: transparent;
}
.bs-stepper .bs-stepper-circle {
    background-color: #0f172a;
}
.bs-stepper .active .bs-stepper-circle {
    background-color: #005BBB;
}
.gps-readonly {
    background: #f1f5f9;
    color: #334155;
    font-weight: 600;
}
.wizard-location-map {
    width: 100%;
    min-height: 320px;
    border-radius: 14px;
    border: 1px solid #cbd5e1;
    overflow: hidden;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bs-stepper/dist/js/bs-stepper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function () {
    var form = document.getElementById('report-wizard-form');
    var stepperEl = document.getElementById('report-stepper');
    if (!form || !stepperEl || typeof window.Stepper === 'undefined') {
        return;
    }

    var stepper = new window.Stepper(stepperEl, { linear: true, animation: true });
    var statusInput = document.getElementById('status_action');
    var draftIdInput = document.getElementById('draft_id');
    var btnSaveDraftSticky = document.getElementById('btn-save-draft-sticky');
    var wizardDraftData = <?= $wizardDraftPayload; ?>;
    var gpsLatInput = document.getElementById('gps_lat');
    var gpsLngInput = document.getElementById('gps_lng');
    var provinceInput = form.querySelector('input[name="province"]');
    var territoryInput = form.querySelector('input[name="territory"]');
    var villageInput = form.querySelector('input[name="village"]');
    var searchInput = document.getElementById('location-search-input');
    var searchBtn = document.getElementById('btn-search-place');
    var wizardMap = null;
    var wizardMarker = null;

    function swAlert(icon, title, text) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            return window.Swal.fire({
                icon: icon,
                title: title,
                text: text,
                confirmButtonColor: '#005BBB'
            });
        }
        window.alert(title + ' - ' + text);
        return Promise.resolve();
    }

    function setGps(lat, lng) {
        if (gpsLatInput) {
            gpsLatInput.value = Number(lat).toFixed(7);
        }
        if (gpsLngInput) {
            gpsLngInput.value = Number(lng).toFixed(7);
        }
    }

    function normalizeAddressField(value) {
        return String(value || '').trim();
    }

    function fillAddressFields(address) {
        var adr = address || {};
        var province = normalizeAddressField(adr.state || adr.region || adr.state_district || adr.county);
        var territory = normalizeAddressField(adr.county || adr.city || adr.town || adr.municipality || adr.village || adr.suburb);
        var village = normalizeAddressField(adr.village || adr.hamlet || adr.suburb || adr.neighbourhood || adr.quarter || adr.road);

        if (province && provinceInput) {
            provinceInput.value = province;
        }
        if (territory && territoryInput) {
            territoryInput.value = territory;
        }
        if (village && villageInput) {
            villageInput.value = village;
        }
    }

    function placeMarker(lat, lng) {
        if (!wizardMap || !window.L) {
            return;
        }

        var point = [lat, lng];
        if (!wizardMarker) {
            wizardMarker = window.L.marker(point, { draggable: true }).addTo(wizardMap);
            wizardMarker.on('dragend', function (event) {
                var pos = event.target.getLatLng();
                onMapLocationSelected(pos.lat, pos.lng);
            });
        } else {
            wizardMarker.setLatLng(point);
        }
    }

    function reverseGeocode(lat, lng) {
        var url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + encodeURIComponent(String(lat))
            + '&lon=' + encodeURIComponent(String(lng))
            + '&addressdetails=1&accept-language=fr';

        return fetch(url, {
            headers: {
                'Accept': 'application/json'
            }
        }).then(function (res) {
            if (!res.ok) {
                throw new Error('Reverse geocoding indisponible (HTTP ' + res.status + ')');
            }
            return res.json();
        });
    }

    function searchPlace(query) {
        var url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&addressdetails=1&accept-language=fr&q=' + encodeURIComponent(query);

        return fetch(url, {
            headers: {
                'Accept': 'application/json'
            }
        }).then(function (res) {
            if (!res.ok) {
                throw new Error('Recherche indisponible (HTTP ' + res.status + ')');
            }
            return res.json();
        });
    }

    function onMapLocationSelected(lat, lng) {
        setGps(lat, lng);
        placeMarker(lat, lng);

        reverseGeocode(lat, lng)
            .then(function (data) {
                fillAddressFields(data && data.address ? data.address : {});
            })
            .catch(function () {
                swAlert('warning', 'Localisation partielle', 'Coordonnées trouvées, mais impossible de récupérer automatiquement les champs administratifs.');
            });
    }

    function initWizardMap() {
        var mapEl = document.getElementById('wizard-location-map');
        if (!mapEl || !window.L || wizardMap) {
            return;
        }

        wizardMap = window.L.map(mapEl, {
            zoomControl: true,
            minZoom: 6,
            maxZoom: 17
        }).setView([-2.9, 28.7], 8);

        window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(wizardMap);

        wizardMap.on('click', function (event) {
            onMapLocationSelected(event.latlng.lat, event.latlng.lng);
        });
    }

    function currentStepPane() {
        return form.querySelector('.content.dstepper-block');
    }

    function validateStep(stepPane) {
        if (!stepPane) {
            return true;
        }

        var requiredFields = stepPane.querySelectorAll('[required]');
        for (var i = 0; i < requiredFields.length; i++) {
            if (!requiredFields[i].checkValidity()) {
                requiredFields[i].reportValidity();
                return false;
            }
        }
        return true;
    }

    function hydrateFormFromDraft(draft) {
        if (!draft || typeof draft !== 'object') {
            return;
        }

        var mapping = {
            province: 'province',
            territory: 'territory',
            health_zone: 'health_zone',
            groupement: 'groupement',
            village: 'village',
            incident_type: 'incident_type',
            urgency_level: 'urgency_level',
            victims_count: 'victims_count',
            displaced_households: 'displaced_households',
            description: 'description',
            analyse: 'analyse',
            priority_needs: 'priority_needs',
            recommandations: 'recommandations'
        };

        Object.keys(mapping).forEach(function (sourceKey) {
            var fieldName = mapping[sourceKey];
            var field = form.querySelector('[name="' + fieldName + '"]');
            if (!field) {
                return;
            }
            var value = draft[sourceKey];
            if (value === null || typeof value === 'undefined') {
                return;
            }
            field.value = String(value);
        });

        if (draftIdInput && Number(draft.id || 0) > 0) {
            draftIdInput.value = String(Number(draft.id));
        }

        var lat = Number(draft.gps_lat || 0);
        var lng = Number(draft.gps_lng || 0);
        if (!Number.isNaN(lat) && !Number.isNaN(lng) && lat !== 0 && lng !== 0) {
            setGps(lat, lng);
            placeMarker(lat, lng);
            if (wizardMap) {
                wizardMap.setView([lat, lng], 10);
            }
        }
    }

    function hydrateFormFromAiPrefill() {
        var raw = '';
        try {
            raw = window.sessionStorage.getItem('sydra_ia_prefill') || '';
        } catch (e) {
            raw = '';
        }

        if (raw === '') {
            return;
        }

        var prefill = null;
        try {
            prefill = JSON.parse(raw);
        } catch (e) {
            prefill = null;
        }

        if (!prefill || typeof prefill !== 'object') {
            return;
        }

        var mapping = {
            incident_type: 'incident_type',
            urgency_level: 'urgency_level',
            description: 'description',
            analyse: 'analyse',
            priority_needs: 'priority_needs',
            recommandations: 'recommandations',
            victims_count: 'victims_count',
            displaced_households: 'displaced_households'
        };

        Object.keys(mapping).forEach(function (sourceKey) {
            var field = form.querySelector('[name="' + mapping[sourceKey] + '"]');
            if (!field) {
                return;
            }
            var value = prefill[sourceKey];
            if (value === null || typeof value === 'undefined') {
                return;
            }
            field.value = String(value);
        });

        try {
            window.sessionStorage.removeItem('sydra_ia_prefill');
        } catch (e) {
            // ignore storage errors
        }
    }

    form.querySelectorAll('.wizard-next').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!validateStep(currentStepPane())) {
                return;
            }
            stepper.next();
        });
    });

    form.querySelectorAll('.wizard-prev').forEach(function (btn) {
        btn.addEventListener('click', function () {
            stepper.previous();
        });
    });

    var btnGeoloc = document.getElementById('btn-geoloc');
    if (btnGeoloc) {
        btnGeoloc.addEventListener('click', function () {
            if (!navigator.geolocation) {
                swAlert('warning', 'Indisponible', 'La géolocalisation n est pas disponible.');
                return;
            }

            navigator.geolocation.getCurrentPosition(function (position) {
                var lat = Number(position.coords.latitude || 0);
                var lng = Number(position.coords.longitude || 0);

                if (!wizardMap) {
                    initWizardMap();
                }

                if (wizardMap) {
                    wizardMap.setView([lat, lng], 13);
                }

                onMapLocationSelected(lat, lng);
            }, function (error) {
                var title = 'Erreur GPS';
                var message = 'Impossible de récupérer votre position.';

                if (error && error.code === error.PERMISSION_DENIED) {
                    title = 'Accès refusé';
                    message = 'Vous avez refusé l\'accès. Conseil : Allez dans les paramètres de votre navigateur (le cadenas) pour autoriser la position.';
                } else if (error && error.code === error.POSITION_UNAVAILABLE) {
                    title = 'Signal GPS introuvable';
                    message = 'Signal GPS introuvable. Conseil : Activez le GPS de votre appareil ou mettez-vous près d\'une fenêtre.';
                } else if (error && error.code === error.TIMEOUT) {
                    title = 'Recherche expirée';
                    message = 'Le temps de recherche a expiré. Conseil : Votre connexion est faible, cliquez manuellement sur la carte.';
                }

                swAlert('error', title, message);
            }, {
                enableHighAccuracy: true,
                timeout: 12000,
                maximumAge: 0
            });
        });
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', function () {
            var query = String(searchInput && searchInput.value ? searchInput.value : '').trim();
            if (query === '') {
                swAlert('info', 'Recherche vide', 'Saisissez un lieu avant de lancer la recherche.');
                return;
            }

            if (!wizardMap) {
                initWizardMap();
            }

            searchBtn.disabled = true;
            searchBtn.textContent = 'Recherche...';

            searchPlace(query)
                .then(function (results) {
                    if (!Array.isArray(results) || results.length === 0) {
                        throw new Error('Aucun lieu trouvé pour cette recherche.');
                    }

                    var hit = results[0];
                    var lat = Number(hit.lat || 0);
                    var lng = Number(hit.lon || 0);
                    if (Number.isNaN(lat) || Number.isNaN(lng)) {
                        throw new Error('Coordonnées invalides retournées par la recherche.');
                    }

                    wizardMap.setView([lat, lng], 13);
                    setGps(lat, lng);
                    placeMarker(lat, lng);
                    fillAddressFields(hit.address || {});
                })
                .catch(function (err) {
                    swAlert('warning', 'Recherche impossible', String(err && err.message ? err.message : 'Aucun résultat trouvé.'));
                })
                .finally(function () {
                    searchBtn.disabled = false;
                    searchBtn.textContent = 'Rechercher';
                });
        });
    }

    if (searchInput) {
        searchInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                if (searchBtn) {
                    searchBtn.click();
                }
            }
        });
    }

    initWizardMap();
    stepperEl.addEventListener('shown.bs-stepper', function () {
        if (wizardMap) {
            window.setTimeout(function () {
                wizardMap.invalidateSize();
            }, 80);
        }
    });

    window.Dropzone.autoDiscover = false;
    var dropzone = new window.Dropzone('#wizard-dropzone', {
        url: '/noop',
        autoProcessQueue: false,
        uploadMultiple: true,
        parallelUploads: 10,
        addRemoveLinks: true,
        acceptedFiles: '.jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt',
        dictDefaultMessage: 'Glissez-déposez vos fichiers ici ou cliquez pour sélectionner.'
    });

    function submitWizard(targetStatus) {
        if (targetStatus !== 'Brouillon' && !validateStep(currentStepPane())) {
            return;
        }

        statusInput.value = targetStatus;
        var payload = new FormData(form);
        payload.append('action', 'save_report_wizard');

        dropzone.getAcceptedFiles().forEach(function (file) {
            payload.append('files[]', file, file.name);
        });

        fetch('api/save_report.php', {
            method: 'POST',
            body: payload,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || data.ok !== true) {
                    throw new Error((data && data.message) ? data.message : 'Enregistrement impossible.');
                }

                if (window.Swal && window.Swal.fire) {
                    window.Swal.fire({
                        icon: 'success',
                        title: targetStatus === 'Brouillon' ? 'Brouillon sauvegardé' : 'Rapport enregistré',
                        text: targetStatus === 'Brouillon'
                            ? 'Brouillon sauvegardé. Ce rapport ne sera pas soumis au Cluster, vous pourrez y revenir plus tard.'
                            : String(data.message || 'Enregistrement réussi.'),
                        confirmButtonColor: '#005BBB'
                    }).then(function () {
                        if (targetStatus === 'Brouillon') {
                            if (draftIdInput && Number(data.report_id || 0) > 0) {
                                draftIdInput.value = String(Number(data.report_id));
                            }
                            return;
                        }
                        window.location.href = '?page=rapportage-liste-user';
                    });
                } else {
                    if (targetStatus !== 'Brouillon') {
                        window.location.href = '?page=rapportage-liste-user';
                    }
                }
            })
            .catch(function (err) {
                if (window.Swal && window.Swal.fire) {
                    window.Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: err.message || 'Erreur lors de la sauvegarde.'
                    });
                }
            });
    }

    var btnDraft = document.getElementById('btn-save-draft');
    var btnSubmit = document.getElementById('btn-submit-cluster');
    if (btnDraft) {
        btnDraft.addEventListener('click', function () {
            submitWizard('Brouillon');
        });
    }
    if (btnSaveDraftSticky) {
        btnSaveDraftSticky.addEventListener('click', function () {
            submitWizard('Brouillon');
        });
    }
    if (btnSubmit) {
        btnSubmit.addEventListener('click', function () {
            submitWizard('Soumis');
        });
    }

    hydrateFormFromDraft(wizardDraftData);
    if (!wizardDraftData) {
        hydrateFormFromAiPrefill();
    }
})();
</script>
