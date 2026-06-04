<?php
$csrf = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bs-stepper/dist/css/bs-stepper.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.css">

<div class="card shadow-sm rounded-4 wizard-shell">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h1 class="mb-1">Saisie manuelle d'un rapport</h1>
            <p class="text-muted mb-0">Assistant guidé en 4 étapes pour documenter proprement un incident terrain.</p>
        </div>
        <a href="?page=rapportage" class="btn btn-small">Retour au choix</a>
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
                        <input type="text" class="form-control" name="gps_lat" id="gps_lat" placeholder="Ex: -2.5130000">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Longitude GPS</label>
                        <input type="text" class="form-control" name="gps_lng" id="gps_lng" placeholder="Ex: 28.8460000">
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
</style>

<script src="https://cdn.jsdelivr.net/npm/bs-stepper/dist/js/bs-stepper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.js"></script>
<script>
(function () {
    var form = document.getElementById('report-wizard-form');
    var stepperEl = document.getElementById('report-stepper');
    if (!form || !stepperEl || typeof window.Stepper === 'undefined') {
        return;
    }

    var stepper = new window.Stepper(stepperEl, { linear: true, animation: true });
    var statusInput = document.getElementById('status_action');

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
                window.Swal && window.Swal.fire('Indisponible', 'La géolocalisation n est pas disponible.', 'warning');
                return;
            }

            navigator.geolocation.getCurrentPosition(function (position) {
                document.getElementById('gps_lat').value = String(position.coords.latitude.toFixed(7));
                document.getElementById('gps_lng').value = String(position.coords.longitude.toFixed(7));
            }, function () {
                window.Swal && window.Swal.fire('Erreur', 'Impossible de récupérer votre position.', 'error');
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            });
        });
    }

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
        if (!validateStep(currentStepPane())) {
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
                        title: 'Rapport enregistré',
                        text: String(data.message || 'Enregistrement réussi.'),
                        confirmButtonColor: '#005BBB'
                    }).then(function () {
                        window.location.href = '?page=rapportage-liste-user';
                    });
                } else {
                    window.location.href = '?page=rapportage-liste-user';
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
    if (btnSubmit) {
        btnSubmit.addEventListener('click', function () {
            submitWizard('Soumis');
        });
    }
})();
</script>
