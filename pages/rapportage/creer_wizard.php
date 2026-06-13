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

<style>
/* ============================================================
   WIZARD PREMIUM — SYDRA (Mission 2 Sprint 4.2)
   Design : SaaS Premium / Notion / Asana
   ============================================================ */

/* Shell principal */
.wizard-shell {
    border: 1px solid #dbeafe;
    background: #ffffff;
    padding: 28px 28px 24px;
}

/* Header wizard */
.wizard-page-title {
    font-size: 1.35rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 3px;
}
.wizard-page-sub {
    font-size: 0.875rem;
    color: #64748b;
}

/* Sticky top bar actions */
.wizard-topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 24px;
}

/* === BS-STEPPER CUSTOM === */
.bs-stepper .bs-stepper-header {
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 20px;
    margin-bottom: 28px;
    flex-wrap: wrap;
    gap: 4px;
}
.bs-stepper .step-trigger {
    background: transparent;
    border: none;
    padding: 8px 12px;
    border-radius: 10px;
    transition: background .15s ease;
}
.bs-stepper .step-trigger:hover {
    background: #f1f5f9;
}
.bs-stepper .bs-stepper-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: #e2e8f0;
    color: #64748b;
    font-size: 0.85rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background .2s ease, color .2s ease;
}
.bs-stepper .active .bs-stepper-circle {
    background: linear-gradient(135deg, #005bbb, #3a86ff);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(0, 91, 187, .30);
}
.bs-stepper .bs-stepper-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #94a3b8;
    margin-top: 4px;
}
.bs-stepper .active .bs-stepper-label {
    color: #005bbb;
}
.bs-stepper .line {
    flex: 1;
    height: 2px;
    background: #e2e8f0;
    margin: 18px -4px 0;
    min-width: 20px;
}

/* === STEP CONTENT CARDS === */
.step-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 20px;
}
.step-section-title {
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #94a3b8;
    margin-bottom: 14px;
}
.step-ux-hint {
    font-size: 0.9rem;
    color: #64748b;
    font-style: italic;
    margin-bottom: 20px;
}

/* === FORM CONTROLS PREMIUM === */
.wizard-shell .form-label {
    font-size: 0.82rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 5px;
}
.wizard-shell .form-control,
.wizard-shell .form-select {
    border-radius: 10px;
    border: 1.5px solid #e2e8f0;
    padding: 10px 14px;
    font-size: 0.875rem;
    color: #1e293b;
    background: #fff;
    transition: border-color .18s ease, box-shadow .18s ease;
}
.wizard-shell .form-control:focus,
.wizard-shell .form-select:focus {
    border-color: #005bbb;
    box-shadow: 0 0 0 3px rgba(0, 91, 187, .09);
}
.gps-readonly {
    background: #f1f5f9 !important;
    color: #334155;
    font-weight: 600;
    cursor: default;
}

/* === NAVIGATION BUTTONS === */
.wizard-btn-next {
    background: linear-gradient(135deg, #005bbb, #3a86ff);
    color: #fff;
    border: none;
    border-radius: 50px;
    padding: 10px 28px;
    font-size: 0.875rem;
    font-weight: 600;
    box-shadow: 0 4px 14px rgba(0, 91, 187, .25);
    transition: transform .15s ease, box-shadow .15s ease;
}
.wizard-btn-next:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 91, 187, .35);
    color: #fff;
}
.wizard-btn-prev {
    border-radius: 50px;
    padding: 10px 24px;
    font-size: 0.875rem;
    font-weight: 600;
    border: 1.5px solid #e2e8f0;
    color: #64748b;
    background: #fff;
    transition: background .15s ease, border-color .15s ease;
}
.wizard-btn-prev:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}
.btn-save-draft {
    border-radius: 50px;
    padding: 10px 22px;
    font-size: 0.875rem;
    font-weight: 600;
}
.btn-submit-cluster {
    border-radius: 50px;
    padding: 10px 28px;
    font-size: 0.875rem;
    font-weight: 600;
    background: linear-gradient(135deg, #005bbb, #3a86ff);
    border: none;
    color: #fff;
    box-shadow: 0 4px 14px rgba(0, 91, 187, .25);
    transition: transform .15s ease, box-shadow .15s ease;
}
.btn-submit-cluster:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 91, 187, .35);
    color: #fff;
}

/* === GPS BUTTON === */
.btn-gps {
    border-radius: 50px;
    border: 1.5px solid #005bbb;
    color: #005bbb;
    background: #fff;
    padding: 8px 20px;
    font-size: 0.83rem;
    font-weight: 600;
    transition: all .15s ease;
}
.btn-gps:hover {
    background: #005bbb;
    color: #fff;
}

/* === PLACE SEARCH INPUT === */
.place-search-wrap {
    position: relative;
}
.place-search-wrap .place-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.9rem;
    pointer-events: none;
}
#place_search {
    padding-left: 38px;
    border-radius: 50px !important;
    border: 1.5px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    font-size: 0.875rem;
}
#place_search:focus {
    border-color: #005bbb;
    box-shadow: 0 0 0 3px rgba(0, 91, 187, .09), 0 2px 8px rgba(0,0,0,.06);
}

/* === GOOGLE MAPS CONTAINER === */
#wizard-map-container {
    border-radius: 16px;
    overflow: hidden;
    border: 1.5px solid #e2e8f0;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
}
#wizard-google-map {
    width: 100%;
    height: 320px;
}

/* === URGENCY SELECT COLORS === */
option[value="Faible"]  { color: #16a34a; }
option[value="Moyenne"] { color: #d97706; }
option[value="Elevee"]  { color: #ea580c; }
option[value="Critique"]{ color: #dc2626; }

/* === DROPZONE PREMIUM === */
.dz-premium {
    border: 2px dashed #94a3b8;
    border-radius: 16px;
    background: #f8fafc;
    padding: 32px 20px;
    text-align: center;
    transition: border-color .2s ease, background .2s ease;
    cursor: pointer;
}
.dz-premium:hover,
.dz-premium.dz-drag-hover {
    border-color: #005bbb;
    background: #eff6ff;
}
.dz-premium .dz-message {
    font-size: 0.9rem;
    color: #64748b;
    font-weight: 500;
}
.dz-premium .dz-message i {
    font-size: 2rem;
    color: #94a3b8;
    display: block;
    margin-bottom: 10px;
}

/* === STEP DIVIDER === */
.step-nav-divider {
    border-top: 1px solid #e2e8f0;
    margin-top: 24px;
    padding-top: 20px;
}

/* Textarea sizing */
.textarea-facts { min-height: 130px; }
.textarea-analysis { min-height: 100px; }

/* Tooltip icon */
.tooltip-icon {
    cursor: help;
    opacity: .7;
    transition: opacity .15s ease;
}
.tooltip-icon:hover { opacity: 1; }
</style>

<div class="wizard-shell card shadow-sm rounded-4">
    <div class="wizard-topbar">
        <div>
            <h1 class="wizard-page-title"><i class="fa-solid fa-file-pen me-2 text-primary"></i>Créer un rapport d'incident</h1>
            <p class="wizard-page-sub">Assistant guidé en 4 étapes pour documenter un incident terrain avec précision.</p>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <button type="button" class="btn btn-outline-secondary btn-save-draft" id="btn-save-draft-sticky">
                <i class="fa-regular fa-floppy-disk me-1"></i>Sauvegarder le brouillon
            </button>
        </div>
    </div>

    <div id="report-stepper" class="bs-stepper">
        <div class="bs-stepper-header" role="tablist">
            <div class="step" data-target="#step-1">
                <button type="button" class="step-trigger" role="tab" aria-controls="step-1" id="step-1-trigger">
                    <span class="bs-stepper-circle"><i class="fa-solid fa-map-location-dot" style="font-size:.85rem"></i></span>
                    <span class="bs-stepper-label">Localisation</span>
                </button>
            </div>
            <div class="line"></div>
            <div class="step" data-target="#step-2">
                <button type="button" class="step-trigger" role="tab" aria-controls="step-2" id="step-2-trigger">
                    <span class="bs-stepper-circle"><i class="fa-solid fa-triangle-exclamation" style="font-size:.8rem"></i></span>
                    <span class="bs-stepper-label">Faits &amp; Bilan</span>
                </button>
            </div>
            <div class="line"></div>
            <div class="step" data-target="#step-3">
                <button type="button" class="step-trigger" role="tab" aria-controls="step-3" id="step-3-trigger">
                    <span class="bs-stepper-circle"><i class="fa-solid fa-magnifying-glass-chart" style="font-size:.8rem"></i></span>
                    <span class="bs-stepper-label">Analyse</span>
                </button>
            </div>
            <div class="line"></div>
            <div class="step" data-target="#step-4">
                <button type="button" class="step-trigger" role="tab" aria-controls="step-4" id="step-4-trigger">
                    <span class="bs-stepper-circle"><i class="fa-solid fa-paperclip" style="font-size:.85rem"></i></span>
                    <span class="bs-stepper-label">Preuves</span>
                </button>
            </div>
        </div>

        <form id="report-wizard-form" class="bs-stepper-content" novalidate>
            <input type="hidden" name="csrf" value="<?= $csrf; ?>">
            <input type="hidden" name="status_action" id="status_action" value="Brouillon">
            <input type="hidden" name="draft_id" id="draft_id" value="<?= $wizardDraftIdValue; ?>">

            <!-- ═══════════════════════════════════════════════
                 ÉTAPE 1 — LOCALISATION
                 ═══════════════════════════════════════════════ -->
            <div id="step-1" class="content" role="tabpanel" aria-labelledby="step-1-trigger">
                <p class="step-ux-hint"><i class="fa-solid fa-location-crosshairs me-2 text-primary"></i>Commençons par localiser le lieu de l'incident...</p>

                <!-- Recherche OpenStreetMap (Nominatim) -->
                <div class="step-card mb-3">
                    <div class="step-section-title">Recherche rapide de lieu</div>
                    <div class="position-relative" style="z-index: 9999;">
                        <input type="text" id="place_search" class="form-control mb-3" placeholder="Rechercher un village, une ville (ex: Minova)..." autocomplete="off">
                        <ul id="search_results" class="list-group position-absolute w-100 shadow-lg" style="display: none; max-height: 250px; overflow-y: auto; z-index: 99999; background: white; border-radius: 8px;"></ul>
                    </div>
                    <small class="text-muted">La sélection d'un lieu remplit automatiquement les champs ci-dessous.</small>
                </div>

                <!-- Carte Leaflet -->
                <div id="wizard-map-container" class="mb-3">
                    <div id="wizard-leaflet-map" style="height: 350px; border-radius: 12px; z-index: 1;"></div>
                </div>
                <small class="text-muted d-block mb-4"><i class="fa-solid fa-circle-info me-1 text-primary"></i>Astuce : cliquez sur la carte pour placer un marqueur et remplir les coordonnées GPS automatiquement.</small>

                <!-- Champs administratifs -->
                <div class="step-card">
                    <div class="step-section-title">Localisation administrative</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="wiz_province">Province *
                                <i class="fa-solid fa-circle-info tooltip-icon text-primary ms-1"
                                   data-bs-toggle="tooltip"
                                   title="Province administrative de la RDC (ex: Nord-Kivu, Sud-Kivu...)"></i>
                            </label>
                            <input type="text" class="form-control" name="province" id="wiz_province" required placeholder="Ex: Nord-Kivu">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="wiz_territory">Territoire *</label>
                            <input type="text" class="form-control" name="territory" id="wiz_territory" required placeholder="Ex: Masisi, Rutshuru...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="wiz_health_zone">Zone de santé
                                <i class="fa-solid fa-circle-info tooltip-icon text-primary ms-1"
                                   data-bs-toggle="tooltip"
                                   title="Zone de santé de référence pour cet incident"></i>
                            </label>
                            <input type="text" class="form-control" name="health_zone" id="wiz_health_zone" placeholder="Ex: Zone de santé de Masisi">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="wiz_groupement">Groupement</label>
                            <input type="text" class="form-control" name="groupement" id="wiz_groupement" placeholder="Ex: Groupement Bahunde">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="wiz_village">Village / Localité *</label>
                            <input type="text" class="form-control" name="village" id="wiz_village" required placeholder="Ex: Minova">
                        </div>
                    </div>
                </div>

                <!-- GPS -->
                <div class="step-card mt-3">
                    <div class="step-section-title">Coordonnées GPS</div>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label" for="gps_lat">Latitude</label>
                            <input type="text" class="form-control gps-readonly" name="gps_lat" id="gps_lat" placeholder="Auto depuis la carte" readonly>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="gps_lng">Longitude</label>
                            <input type="text" class="form-control gps-readonly" name="gps_lng" id="gps_lng" placeholder="Auto depuis la carte" readonly>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-gps w-100" id="btn-geoloc">
                                <i class="fa-solid fa-location-crosshairs me-1"></i>Ma position
                            </button>
                        </div>
                    </div>
                </div>

                <div class="step-nav-divider d-flex justify-content-end">
                    <button type="button" class="wizard-btn-next wizard-next">
                        Suivant <i class="fa-solid fa-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════
                 ÉTAPE 2 — FAITS & BILAN
                 ═══════════════════════════════════════════════ -->
            <div id="step-2" class="content" role="tabpanel" aria-labelledby="step-2-trigger">
                <p class="step-ux-hint"><i class="fa-solid fa-triangle-exclamation me-2 text-warning"></i>Dites-nous en un peu plus sur les faits, le bilan et le nombre de victimes...</p>

                <div class="step-card">
                    <div class="step-section-title">Classification de l'incident</div>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label" for="wiz_incident_type">Type d'incident *
                                <i class="fa-solid fa-circle-info tooltip-icon text-primary ms-1"
                                   data-bs-toggle="tooltip"
                                   title="Catégorie principale de l'incident (ex: Violence armée, Déplacement forcé, Violence sexuelle, Vol...)"></i>
                            </label>
                            <select class="form-select" name="incident_type" id="wiz_incident_type" required>
                                <option value="">Sélectionner un type...</option>
                                <option value="Violence armée">Violence armée</option>
                                <option value="Déplacement forcé">Déplacement forcé</option>
                                <option value="Violence sexuelle">Violence sexuelle (VSBG)</option>
                                <option value="Vol / Pillage">Vol / Pillage</option>
                                <option value="Enlèvement / Kidnapping">Enlèvement / Kidnapping</option>
                                <option value="Détention arbitraire">Détention arbitraire</option>
                                <option value="Destruction de biens">Destruction de biens</option>
                                <option value="Recrutement forcé">Recrutement forcé</option>
                                <option value="Autre">Autre (préciser dans la description)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="wiz_urgency_level">Niveau de gravité *
                                <i class="fa-solid fa-circle-info tooltip-icon text-primary ms-1"
                                   data-bs-toggle="tooltip"
                                   title="Faible: impact limité | Moyenne: plusieurs victimes | Élevée: impact communautaire | Critique: urgence absolue"></i>
                            </label>
                            <select class="form-select" name="urgency_level" id="wiz_urgency_level" required>
                                <option value="">Choisir...</option>
                                <option value="Faible">🟢 Faible</option>
                                <option value="Moyenne" selected>🟡 Moyenne</option>
                                <option value="Elevee">🟠 Élevée</option>
                                <option value="Critique">🔴 Critique</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="step-card mt-3">
                    <div class="step-section-title">Bilan humain</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="wiz_victims_count">Nombre de victimes directes
                                <i class="fa-solid fa-circle-info tooltip-icon text-primary ms-1"
                                   data-bs-toggle="tooltip"
                                   title="Nombre de personnes directement affectées (blessées, tuées, abusées...)"></i>
                            </label>
                            <input type="number" min="0" class="form-control" name="victims_count" id="wiz_victims_count" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="wiz_displaced">Ménages déplacés
                                <i class="fa-solid fa-circle-info tooltip-icon text-primary ms-1"
                                   data-bs-toggle="tooltip"
                                   title="Nombre de ménages ayant fui suite à cet incident"></i>
                            </label>
                            <input type="number" min="0" class="form-control" name="displaced_households" id="wiz_displaced" value="0">
                        </div>
                    </div>
                </div>

                <div class="step-card mt-3">
                    <div class="step-section-title">Description des faits</div>
                    <label class="form-label" for="wiz_facts_text">Faits constatés *
                        <i class="fa-solid fa-circle-info tooltip-icon text-primary ms-1"
                           data-bs-toggle="tooltip"
                           title="Décrivez les faits objectivement : qui, quoi, quand, comment. Évitez les noms sensibles (utilisez des codes si nécessaire)."></i>
                    </label>
                    <textarea class="form-control textarea-facts" name="facts_text" id="wiz_facts_text" rows="6"
                              placeholder="Décrivez objectivement les faits constatés sur le terrain..." required></textarea>
                    <small class="text-muted mt-1 d-block"><i class="fa-solid fa-shield-halved me-1 text-success"></i>Les termes sensibles seront automatiquement codifiés avant l'enregistrement.</small>
                </div>

                <div class="step-nav-divider d-flex justify-content-between">
                    <button type="button" class="wizard-btn-prev wizard-prev">
                        <i class="fa-solid fa-arrow-left me-1"></i>Précédent
                    </button>
                    <button type="button" class="wizard-btn-next wizard-next">
                        Suivant <i class="fa-solid fa-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════
                 ÉTAPE 3 — ANALYSE & RECOMMANDATIONS
                 ═══════════════════════════════════════════════ -->
            <div id="step-3" class="content" role="tabpanel" aria-labelledby="step-3-trigger">
                <p class="step-ux-hint"><i class="fa-solid fa-magnifying-glass-chart me-2 text-primary"></i>Complétez votre analyse humanitaire et proposez des recommandations...</p>

                <div class="step-card">
                    <label class="form-label" for="wiz_analysis_text">Impacts humanitaires &amp; Analyse *
                        <i class="fa-solid fa-circle-info tooltip-icon text-primary ms-1"
                           data-bs-toggle="tooltip"
                           title="Analysez les causes, conséquences et dynamiques de protection liées à cet incident."></i>
                    </label>
                    <textarea class="form-control textarea-analysis" name="analysis_text" id="wiz_analysis_text" rows="5"
                              placeholder="Analysez les causes, les conséquences, les dynamiques de protection observées..." required></textarea>
                </div>

                <div class="step-card mt-3">
                    <label class="form-label" for="wiz_priority_needs">Besoins prioritaires *
                        <i class="fa-solid fa-circle-info tooltip-icon text-primary ms-1"
                           data-bs-toggle="tooltip"
                           title="Listez les besoins immédiats identifiés pour les personnes affectées (abri, WASH, santé, protection...)"></i>
                    </label>
                    <textarea class="form-control textarea-analysis" name="priority_needs" id="wiz_priority_needs" rows="4"
                              placeholder="Ex: Abri d'urgence, eau potable, assistance psychosociale, sécurité..." required></textarea>
                </div>

                <div class="step-card mt-3">
                    <label class="form-label" for="wiz_recommendations_text">Recommandations *
                        <i class="fa-solid fa-circle-info tooltip-icon text-primary ms-1"
                           data-bs-toggle="tooltip"
                           title="Actions concrètes recommandées au Cluster Protection pour répondre à cet incident."></i>
                    </label>
                    <textarea class="form-control textarea-analysis" name="recommendations_text" id="wiz_recommendations_text" rows="4"
                              placeholder="Recommandations opérationnelles à soumettre au Cluster Protection..." required></textarea>
                </div>

                <div class="step-nav-divider d-flex justify-content-between">
                    <button type="button" class="wizard-btn-prev wizard-prev">
                        <i class="fa-solid fa-arrow-left me-1"></i>Précédent
                    </button>
                    <button type="button" class="wizard-btn-next wizard-next">
                        Suivant <i class="fa-solid fa-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════
                 ÉTAPE 4 — PREUVES & VALIDATION
                 ═══════════════════════════════════════════════ -->
            <div id="step-4" class="content" role="tabpanel" aria-labelledby="step-4-trigger">
                <div class="step-card">
                    <div class="step-section-title">Pièces jointes (optionnel)</div>
                    <div id="wizard-dropzone" class="dropzone dz-premium">
                        <div class="dz-message needsclick">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <strong>Glissez-déposez vos fichiers ici</strong><br>
                            <span class="text-muted" style="font-size:.82rem">ou cliquez pour sélectionner</span>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">
                        <i class="fa-solid fa-circle-check text-success me-1"></i>
                        Formats autorisés : jpg, jpeg, png, webp, pdf, doc, docx, xls, xlsx, txt — Max 10 Mo par fichier.
                    </small>
                </div>

                <!-- Checklist de validation avant envoi -->
                <div class="step-card mt-3" id="wizard-recap-card">
                    <div class="step-section-title">Checklist interactive de validation</div>
                    <p class="text-muted small mb-3">Veuillez vérifier les éléments ci-dessous avant de soumettre le rapport d'incident. Vous pouvez compléter les informations manquantes en cliquant sur les boutons.</p>
                    <div id="recapChecklist" class="accordion border-0">
                        <!-- Rempli dynamiquement via JS -->
                    </div>
                </div>

                <div class="step-nav-divider d-flex justify-content-between flex-wrap gap-2">
                    <button type="button" class="wizard-btn-prev wizard-prev">
                        <i class="fa-solid fa-arrow-left me-1"></i>Précédent
                    </button>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-outline-secondary btn-save-draft rounded-pill" id="btnSaveDraft">
                            <i class="fa-regular fa-floppy-disk me-1"></i>Sauvegarder le brouillon
                        </button>
                        <button type="button" class="btn-submit-cluster" id="btnSubmitCluster">
                            <i class="fa-solid fa-paper-plane me-1"></i>Soumettre l'alerte
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bs-stepper/dist/js/bs-stepper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.js"></script>

<script>
(function () {
    // ─────────────────────────────────────────────
    // 1. RÉFÉRENCES DOM
    // ─────────────────────────────────────────────
    var form           = document.getElementById('report-wizard-form');
    var stepperEl      = document.getElementById('report-stepper');
    if (!form || !stepperEl) { return; }

    var statusInput    = document.getElementById('status_action');
    var draftIdInput   = document.getElementById('draft_id');
    var gpsLatInput    = document.getElementById('gps_lat');
    var gpsLngInput    = document.getElementById('gps_lng');
    var provinceInput  = document.getElementById('wiz_province');
    var territoryInput = document.getElementById('wiz_territory');
    var villageInput   = document.getElementById('wiz_village');
    var wizardDraftData = <?= $wizardDraftPayload; ?>;

    var wizardGoogleMap  = null;
    var wizardMapMarker  = null;
    var hasGoogleMaps    = false;

    // ─────────────────────────────────────────────
    // 2. STEPPER
    // ─────────────────────────────────────────────
    var stepper = null;
    if (typeof window.Stepper !== 'undefined') {
        stepper = new window.Stepper(stepperEl, { linear: true, animation: true });
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('step') === '4') {
            setTimeout(function() { stepper.to(4); }, 100);
        }
    }

    // ─────────────────────────────────────────────
    // 3. TOOLTIPS BOOTSTRAP
    // ─────────────────────────────────────────────
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        if (window.bootstrap && window.bootstrap.Tooltip) {
            new window.bootstrap.Tooltip(el, { trigger: 'hover focus' });
        }
    });

    // ─────────────────────────────────────────────
    // 4. LEAFLET MAPS INIT
    // ─────────────────────────────────────────────
    var wizardLeafletMap = null;
    var wizardLeafletMarker = null;

    function initWizardLeafletMap() {
        var mapEl = document.getElementById('wizard-leaflet-map');
        if (!mapEl || typeof L === 'undefined') { return; }

        // Centre sur la RDC (Est)
        var defaultCenter = [-2.9, 28.7];

        wizardLeafletMap = L.map('wizard-leaflet-map').setView(defaultCenter, 8);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(wizardLeafletMap);

        wizardLeafletMap.on('click', function(e) {
            var lat = e.latlng.lat;
            var lng = e.latlng.lng;
            setGps(lat, lng);
            placeLeafletMarker(lat, lng);
            reverseGeocodeOSM(lat, lng);
        });

        // Si coordonnées GPS déjà présentes (brouillon), centrer la carte
        var existingLat = parseFloat(gpsLatInput ? gpsLatInput.value : '');
        var existingLng = parseFloat(gpsLngInput ? gpsLngInput.value : '');
        if (!isNaN(existingLat) && !isNaN(existingLng) && existingLat !== 0) {
            wizardLeafletMap.setView([existingLat, existingLng], 12);
            placeLeafletMarker(existingLat, existingLng);
        }
    }

    function placeLeafletMarker(lat, lng) {
        if (!wizardLeafletMap) return;
        if (!wizardLeafletMarker) {
            // Icone par défaut de Leaflet
            wizardLeafletMarker = L.marker([lat, lng], { draggable: true }).addTo(wizardLeafletMap);
            wizardLeafletMarker.on('dragend', function(e) {
                var newPos = e.target.getLatLng();
                setGps(newPos.lat, newPos.lng);
                reverseGeocodeOSM(newPos.lat, newPos.lng);
            });
        } else {
            wizardLeafletMarker.setLatLng([lat, lng]);
        }
    }

    function reverseGeocodeOSM(lat, lng) {
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
            .then(r => r.json())
            .then(data => {
                if (data && data.address) {
                    var province = data.address.state || '';
                    var territory = data.address.county || data.address.city || '';
                    var village = data.address.village || data.address.town || data.address.suburb || '';

                    if (province && provinceInput) provinceInput.value = province;
                    if (territory && territoryInput) territoryInput.value = territory;
                    if (village && villageInput) villageInput.value = village;
                }
            })
            .catch(err => console.error("Erreur de geocoding inverse OSM:", err));
    }
    
    setTimeout(initWizardLeafletMap, 300);

    // ─────────────────────────────────────────────
    // 4.b. AUTOCOMPLETE OSM (Nominatim)
    // ─────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('place_search');
        const resultsContainer = document.getElementById('search_results');

        if (!searchInput || !resultsContainer) return; // Sécurité

        searchInput.addEventListener('input', function() {
            let query = this.value.trim();
            
            if (query.length < 3) {
                resultsContainer.style.display = 'none';
                return;
            }

            // Appel à l'API gratuite Nominatim (restreint à la RDC)
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=cd&limit=5`)
                .then(response => response.json())
                .then(data => {
                    resultsContainer.innerHTML = '';
                    if (data.length > 0) {
                        resultsContainer.style.display = 'block';
                        data.forEach(place => {
                            let li = document.createElement('li');
                            li.className = 'list-group-item list-group-item-action cursor-pointer';
                            li.style.cursor = 'pointer';
                            // Nettoyer le nom pour n'afficher que l'essentiel
                            li.textContent = place.display_name;
                            
                            li.onclick = function() {
                                // Remplir la barre de recherche avec le nom court
                                searchInput.value = place.name || place.display_name.split(',')[0];
                                
                                // Remplir les champs GPS cachés ou visibles
                                let latInput = document.querySelector('input[name="latitude"]') || document.getElementById('gps_lat');
                                let lngInput = document.querySelector('input[name="longitude"]') || document.getElementById('gps_lng');
                                
                                if(latInput) latInput.value = place.lat;
                                if(lngInput) lngInput.value = place.lon;
                                
                                // Centrer la carte si possible
                                if (typeof wizardLeafletMap !== 'undefined' && wizardLeafletMap) {
                                    wizardLeafletMap.setView([place.lat, place.lon], 13);
                                    if (typeof placeLeafletMarker === 'function') {
                                        placeLeafletMarker(place.lat, place.lon);
                                    }
                                }
                                
                                // Cacher la liste après sélection
                                resultsContainer.style.display = 'none';
                            };
                            resultsContainer.appendChild(li);
                        });
                    } else {
                        resultsContainer.innerHTML = '<li class="list-group-item text-muted">Aucun résultat trouvé en RDC</li>';
                        resultsContainer.style.display = 'block';
                    }
                }).catch(err => {
                    console.error("Erreur de recherche OSM:", err);
                });
        });

        // Cacher les résultats si on clique ailleurs sur la page
        document.addEventListener('click', function(e) {
            if (e.target.id !== 'place_search') {
                resultsContainer.style.display = 'none';
            }
        });
    });

    // ─────────────────────────────────────────────
    // 5. GPS NATIF (FALLBACK)
    // ─────────────────────────────────────────────
    function setGps(lat, lng) {
        if (gpsLatInput) { gpsLatInput.value = Number(lat).toFixed(7); }
        if (gpsLngInput) { gpsLngInput.value = Number(lng).toFixed(7); }
    }

    var btnGeoloc = document.getElementById('btn-geoloc');
    if (btnGeoloc) {
        btnGeoloc.addEventListener('click', function () {
            if (!navigator.geolocation) {
                showAlert('warning', 'Indisponible', 'La géolocalisation n\'est pas disponible sur ce navigateur.');
                return;
            }
            btnGeoloc.disabled = true;
            btnGeoloc.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Localisation...';
            navigator.geolocation.getCurrentPosition(
                function (pos) {
                    var lat = pos.coords.latitude;
                    var lng = pos.coords.longitude;
                    setGps(lat, lng);
                    if (wizardLeafletMap) {
                        wizardLeafletMap.setView([lat, lng], 14);
                        placeLeafletMarker(lat, lng);
                        reverseGeocodeOSM(lat, lng);
                    }
                    btnGeoloc.disabled = false;
                    btnGeoloc.innerHTML = '<i class="fa-solid fa-location-crosshairs me-1"></i>Ma position';
                },
                function (err) {
                    var msgs = {
                        1: 'Accès refusé. Autorisez la localisation dans les paramètres du navigateur.',
                        2: 'Signal GPS introuvable. Réessayez près d\'une fenêtre.',
                        3: 'Délai expiré. Votre connexion est peut-être lente.'
                    };
                    showAlert('error', 'Erreur GPS', msgs[err.code] || 'Impossible de récupérer votre position.');
                    btnGeoloc.disabled = false;
                    btnGeoloc.innerHTML = '<i class="fa-solid fa-location-crosshairs me-1"></i>Ma position';
                },
                { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
            );
        });
    }

    // ─────────────────────────────────────────────
    // 6. NAVIGATION WIZARD (Suivant / Précédent)
    // ─────────────────────────────────────────────
    function currentStepPane() {
        return form.querySelector('.content.dstepper-block');
    }

    function validateStep(stepPane) {
        if (!stepPane) { return true; }
        var fields = stepPane.querySelectorAll('[required]');
        for (var i = 0; i < fields.length; i++) {
            if (!fields[i].checkValidity()) {
                fields[i].reportValidity();
                return false;
            }
        }
        return true;
    }

    // ─────────────────────────────────────────────
    // 6.b. AUTO-SAVE SILENCIEUX (brouillon) à chaque "Suivant"
    // ─────────────────────────────────────────────
    var autoSaveIndicator = null;

    function showAutoSaveIndicator(state) {
        // state: 'saving' | 'saved' | 'error'
        if (!autoSaveIndicator) {
            autoSaveIndicator = document.createElement('div');
            autoSaveIndicator.id = 'autosave-indicator';
            autoSaveIndicator.style.cssText = [
                'position:fixed',
                'bottom:24px',
                'left:50%',
                'transform:translateX(-50%)',
                'background:#1e293b',
                'color:#f1f5f9',
                'font-size:0.78rem',
                'font-weight:500',
                'padding:7px 18px',
                'border-radius:50px',
                'box-shadow:0 4px 16px rgba(0,0,0,.18)',
                'z-index:9999',
                'transition:opacity .3s ease',
                'pointer-events:none',
                'display:flex',
                'align-items:center',
                'gap:7px'
            ].join(';');
            document.body.appendChild(autoSaveIndicator);
        }
        if (state === 'saving') {
            autoSaveIndicator.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="font-size:.75rem"></i> Sauvegarde automatique...';
            autoSaveIndicator.style.opacity = '1';
        } else if (state === 'saved') {
            autoSaveIndicator.innerHTML = '<i class="fa-solid fa-check" style="color:#34d399;font-size:.75rem"></i> Brouillon sauvegardé';
            autoSaveIndicator.style.opacity = '1';
            window.clearTimeout(autoSaveIndicator._hideTimer);
            autoSaveIndicator._hideTimer = window.setTimeout(function () {
                autoSaveIndicator.style.opacity = '0';
            }, 2500);
        } else if (state === 'error') {
            autoSaveIndicator.innerHTML = '<i class="fa-solid fa-triangle-exclamation" style="color:#f87171;font-size:.75rem"></i> Sauvegarde impossible';
            autoSaveIndicator.style.opacity = '1';
            window.clearTimeout(autoSaveIndicator._hideTimer);
            autoSaveIndicator._hideTimer = window.setTimeout(function () {
                autoSaveIndicator.style.opacity = '0';
            }, 3000);
        }
    }

    function saveDraftSilent(callback) {
        statusInput.value = 'Brouillon';
        var payload = new FormData(form);
        payload.append('action', 'save_report_wizard');

        showAutoSaveIndicator('saving');

        fetch('api/save_report.php', {
            method: 'POST',
            body: payload,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data && data.ok === true) {
                // Met à jour le draft_id si c'est un nouveau brouillon
                if (draftIdInput && Number(data.report_id || 0) > 0) {
                    draftIdInput.value = String(Number(data.report_id));
                }
                showAutoSaveIndicator('saved');
            } else {
                showAutoSaveIndicator('error');
            }
            if (typeof callback === 'function') { callback(); }
        })
        .catch(function () {
            showAutoSaveIndicator('error');
            if (typeof callback === 'function') { callback(); }
        });
    }

    form.querySelectorAll('.wizard-next').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!validateStep(currentStepPane())) { return; }
            // Sauvegarde silencieuse du brouillon AVANT de passer à l'étape suivante
            saveDraftSilent(function () {
                if (stepper) { stepper.next(); }
            });
        });
    });


    form.querySelectorAll('.wizard-prev').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (stepper) { stepper.previous(); }
        });
    });

    // ─────────────────────────────────────────────
    // 7. CHECKLIST INTERACTIVE ÉTAPE 4
    // ─────────────────────────────────────────────
    stepperEl.addEventListener('show.bs-stepper', function (e) {
        if (e && e.detail && e.detail.to === 3) {
            updateChecklistUI();
        }
    });
    stepperEl.addEventListener('shown.bs-stepper', function (e) {
        if (e && e.detail && e.detail.indexStep === 3) {
            updateChecklistUI();
        }
    });

    window.getLocation = function() {
        var btn = document.getElementById('btn-geoloc');
        if (btn) { btn.click(); }
    };

    function updateChecklistUI() {
        const recapContainer = document.getElementById('recapChecklist');
        if (!recapContainer) return;
        recapContainer.innerHTML = ''; // Vider avant de remplir

        // Récupérer les valeurs des champs
        const getVal = (id) => {
            const el = document.getElementById(id);
            return el ? el.value.trim() : '';
        };

        const province = getVal('wiz_province');
        const territory = getVal('wiz_territory');
        const healthZone = getVal('wiz_health_zone');
        const village = getVal('wiz_village');
        const lat = getVal('gps_lat');
        const lng = getVal('gps_lng');

        const incident = getVal('wiz_incident_type');
        const gravity = getVal('wiz_urgency_level');
        const victims = getVal('wiz_victims_count') || '0';
        const displaced = getVal('wiz_displaced') || '0';
        const facts = getVal('wiz_facts_text');

        const analysis = getVal('wiz_analysis_text');
        const needs = getVal('wiz_priority_needs');
        const recs = getVal('wiz_recommendations_text');

        // Déterminer s'il manque des éléments
        const isLocMissing = !province || province === 'Non renseigné' || province === '—' || province === '0';
        const isGPSMissing = !lat || lat === '—' || lat === '0' || lat === '';
        const isFactsMissing = !facts || facts === 'Non renseigné' || facts === '—' || facts === '0';
        const isRecsMissing = !recs || recs === 'Non renseigné' || recs === '—' || recs === '0';

        // 1. Localisation Accordion Item
        const locHTML = `
            <div class="accordion-item mb-3 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="headingLoc">
                    <button class="accordion-button collapsed py-3 d-flex justify-content-between align-items-center bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLoc" aria-expanded="false" aria-controls="collapseLoc" style="box-shadow: none;">
                        <div class="d-flex align-items-center w-100 me-3">
                            <i class="fa-solid ${isLocMissing ? 'fa-triangle-exclamation text-warning' : 'fa-circle-check text-success'} fs-5 me-3"></i>
                            <div class="flex-grow-1 text-start">
                                <span class="fw-semibold text-dark fs-6">Localisation administrative</span>
                                <span class="d-block text-secondary small mt-0.5">${province ? `${province}, ${territory || ''} (${village || ''})` : 'Informations de localisation'}</span>
                            </div>
                            <span class="badge ${isLocMissing ? 'bg-warning text-dark' : 'bg-success'} rounded-pill px-3 py-1.5 fs-7 ms-auto">${isLocMissing ? 'Incomplet' : 'OK'}</span>
                        </div>
                    </button>
                </h2>
                <div id="collapseLoc" class="accordion-collapse collapse" aria-labelledby="headingLoc" data-bs-parent="#recapChecklist">
                    <div class="accordion-body bg-light-subtle text-start p-4 border-top">
                        <div class="row g-3">
                            <div class="col-sm-6"><strong>Province :</strong> <span class="text-secondary">${province || 'Non renseigné'}</span></div>
                            <div class="col-sm-6"><strong>Territoire :</strong> <span class="text-secondary">${territory || 'Non renseigné'}</span></div>
                            <div class="col-sm-6"><strong>Zone de santé :</strong> <span class="text-secondary">${healthZone || 'Non renseigné'}</span></div>
                            <div class="col-sm-6"><strong>Village / Localité :</strong> <span class="text-secondary">${village || 'Non renseigné'}</span></div>
                        </div>
                        <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="if(typeof stepper !== 'undefined' && stepper) { stepper.to(1); }">
                                <i class="fa-solid fa-pen-to-square me-1"></i> Modifier la localisation
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // 2. GPS Accordion Item
        const gpsHTML = `
            <div class="accordion-item mb-3 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="headingGPS">
                    <button class="accordion-button collapsed py-3 d-flex justify-content-between align-items-center bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGPS" aria-expanded="false" aria-controls="collapseGPS" style="box-shadow: none;">
                        <div class="d-flex align-items-center w-100 me-3">
                            <i class="fa-solid ${isGPSMissing ? 'fa-location-crosshairs text-danger animate-pulse' : 'fa-circle-check text-success'} fs-5 me-3"></i>
                            <div class="flex-grow-1 text-start">
                                <span class="fw-semibold text-dark fs-6">Coordonnées GPS</span>
                                <span class="d-block text-secondary small mt-0.5">${!isGPSMissing ? `Latitude: ${lat} / Longitude: ${lng}` : 'Obligatoire pour la cartographie'}</span>
                            </div>
                            <span class="badge ${isGPSMissing ? 'bg-danger' : 'bg-success'} rounded-pill px-3 py-1.5 fs-7 ms-auto">${isGPSMissing ? 'Manquant' : 'OK'}</span>
                        </div>
                    </button>
                </h2>
                <div id="collapseGPS" class="accordion-collapse collapse" aria-labelledby="headingGPS" data-bs-parent="#recapChecklist">
                    <div class="accordion-body bg-light-subtle text-start p-4 border-top">
                        <div class="row g-3">
                            <div class="col-sm-6"><strong>Latitude :</strong> <span class="text-secondary">${lat || 'Non renseigné'}</span></div>
                            <div class="col-sm-6"><strong>Longitude :</strong> <span class="text-secondary">${lng || 'Non renseigné'}</span></div>
                        </div>
                        <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                            <span class="text-danger small fw-medium">${isGPSMissing ? '<i class="fa-solid fa-circle-exclamation me-1"></i> Position indispensable pour la cartographie interactive' : ''}</span>
                            <div class="d-flex gap-2">
                                ${isGPSMissing ? '<button type="button" class="btn btn-sm btn-danger rounded-pill px-3" onclick="getLocation()"><i class="fa-solid fa-satellite-dish me-1"></i> Capter ma position</button>' : ''}
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="if(typeof stepper !== 'undefined' && stepper) { stepper.to(1); }">
                                    <i class="fa-solid fa-map-location-dot me-1"></i> Placer sur la carte
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // 3. Faits & Bilan Accordion Item
        const factsHTML = `
            <div class="accordion-item mb-3 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="headingFacts">
                    <button class="accordion-button collapsed py-3 d-flex justify-content-between align-items-center bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFacts" aria-expanded="false" aria-controls="collapseFacts" style="box-shadow: none;">
                        <div class="d-flex align-items-center w-100 me-3">
                            <i class="fa-solid ${isFactsMissing ? 'fa-triangle-exclamation text-warning' : 'fa-circle-check text-success'} fs-5 me-3"></i>
                            <div class="flex-grow-1 text-start">
                                <span class="fw-semibold text-dark fs-6">Faits et Bilan</span>
                                <span class="d-block text-secondary small mt-0.5">${incident ? `${incident} (${gravity || 'Moyenne'})` : 'Détails de l\'incident'}</span>
                            </div>
                            <span class="badge ${isFactsMissing ? 'bg-warning text-dark' : 'bg-success'} rounded-pill px-3 py-1.5 fs-7 ms-auto">${isFactsMissing ? 'Incomplet' : 'OK'}</span>
                        </div>
                    </button>
                </h2>
                <div id="collapseFacts" class="accordion-collapse collapse" aria-labelledby="headingFacts" data-bs-parent="#recapChecklist">
                    <div class="accordion-body bg-light-subtle text-start p-4 border-top">
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6"><strong>Type d\'incident :</strong> <span class="text-secondary">${incident || 'Non renseigné'}</span></div>
                            <div class="col-sm-6"><strong>Niveau de gravité :</strong> <span class="text-secondary">${gravity || 'Moyenne'}</span></div>
                            <div class="col-sm-6"><strong>Victimes directes :</strong> <span class="text-secondary">${victims}</span></div>
                            <div class="col-sm-6"><strong>Ménages déplacés :</strong> <span class="text-secondary">${displaced}</span></div>
                        </div>
                        <div class="mb-3">
                            <strong>Description des faits :</strong>
                            <div class="p-3 bg-white border rounded mt-2 text-secondary" style="white-space: pre-wrap; font-size: 0.9rem;">${facts || 'Aucune description renseignée.'}</div>
                        </div>
                        <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="if(typeof stepper !== 'undefined' && stepper) { stepper.to(2); }">
                                <i class="fa-solid fa-pen-to-square me-1"></i> Modifier les faits
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // 4. Recommandations Accordion Item
        const recsHTML = `
            <div class="accordion-item mb-3 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="headingRecs">
                    <button class="accordion-button collapsed py-3 d-flex justify-content-between align-items-center bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRecs" aria-expanded="false" aria-controls="collapseRecs" style="box-shadow: none;">
                        <div class="d-flex align-items-center w-100 me-3">
                            <i class="fa-solid ${isRecsMissing ? 'fa-triangle-exclamation text-warning' : 'fa-circle-check text-success'} fs-5 me-3"></i>
                            <div class="flex-grow-1 text-start">
                                <span class="fw-semibold text-dark fs-6">Recommandations et Analyse</span>
                                <span class="d-block text-secondary small mt-0.5">${recs ? recs.substring(0, 60) + '...' : 'Analyse et besoins identifiés'}</span>
                            </div>
                            <span class="badge ${isRecsMissing ? 'bg-warning text-dark' : 'bg-success'} rounded-pill px-3 py-1.5 fs-7 ms-auto">${isRecsMissing ? 'Incomplet' : 'OK'}</span>
                        </div>
                    </button>
                </h2>
                <div id="collapseRecs" class="accordion-collapse collapse" aria-labelledby="headingRecs" data-bs-parent="#recapChecklist">
                    <div class="accordion-body bg-light-subtle text-start p-4 border-top">
                        <div class="mb-3">
                            <strong>Impacts humanitaires & Analyse :</strong>
                            <div class="p-3 bg-white border rounded mt-2 text-secondary" style="white-space: pre-wrap; font-size: 0.9rem;">${analysis || 'Aucune analyse renseignée.'}</div>
                        </div>
                        <div class="mb-3">
                            <strong>Besoins prioritaires :</strong>
                            <div class="p-3 bg-white border rounded mt-2 text-secondary" style="white-space: pre-wrap; font-size: 0.9rem;">${needs || 'Aucun besoin prioritaire renseigné.'}</div>
                        </div>
                        <div class="mb-3">
                            <strong>Recommandations opérationnelles :</strong>
                            <div class="p-3 bg-white border rounded mt-2 text-secondary" style="white-space: pre-wrap; font-size: 0.9rem;">${recs || 'Aucune recommandation renseignée.'}</div>
                        </div>
                        <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="if(typeof stepper !== 'undefined' && stepper) { stepper.to(3); }">
                                <i class="fa-solid fa-pen-to-square me-1"></i> Modifier l'analyse & recs
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        recapContainer.innerHTML = locHTML + gpsHTML + factsHTML + recsHTML;
    }

    // ─────────────────────────────────────────────
    // 8. DROPZONE.JS
    // ─────────────────────────────────────────────
    var dropzone = null;
    if (typeof window.Dropzone !== 'undefined') {
        window.Dropzone.autoDiscover = false;
        dropzone = new window.Dropzone('#wizard-dropzone', {
            url: '/noop',
            autoProcessQueue: false,
            uploadMultiple: true,
            parallelUploads: 10,
            addRemoveLinks: true,
            acceptedFiles: '.jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt',
            maxFilesize: 10,
            dictDefaultMessage: '',
            dictRemoveFile: '✕ Retirer'
        });
    }

    // ─────────────────────────────────────────────
    // 9. SAUVEGARDE AJAX
    // ─────────────────────────────────────────────
    function showAlert(icon, title, text) {
        if (window.premiumAlert && typeof window.premiumAlert.fire === 'function') {
            return window.premiumAlert.fire({ icon: icon, title: title, text: text });
        }
        if (window.Swal) {
            return window.Swal.fire({ icon: icon, title: title, text: text, confirmButtonColor: '#005BBB' });
        }
        window.alert(title + '\n' + text);
        return Promise.resolve();
    }

    function submitWizard(targetStatus) {
        if (targetStatus !== 'Brouillon' && !validateStep(currentStepPane())) { return; }

        statusInput.value = targetStatus;
        var payload = new FormData(form);
        payload.append('action', 'save_report_wizard');

        if (dropzone) {
            dropzone.getAcceptedFiles().forEach(function (file) {
                payload.append('files[]', file, file.name);
            });
        }

        var submitBtn = targetStatus === 'Brouillon'
            ? document.getElementById('btnSaveDraft')
            : document.getElementById('btnSubmitCluster');

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Enregistrement...';
        }

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

            showAlert(
                'success',
                targetStatus === 'Brouillon' ? '✅ Brouillon sauvegardé' : '🎉 Alerte soumise !',
                targetStatus === 'Brouillon'
                    ? 'Votre brouillon a été enregistré. Vous pouvez y revenir à tout moment.'
                    : String(data.message || 'Votre alerte a été soumise au Cluster Protection.')
            ).then(function () {
                if (targetStatus !== 'Brouillon') {
                    window.location.href = '?page=rapportage-liste-user';
                } else if (draftIdInput && Number(data.report_id || 0) > 0) {
                    draftIdInput.value = String(Number(data.report_id));
                }
            });
        })
        .catch(function (err) {
            showAlert('error', 'Erreur', err.message || 'Erreur lors de la sauvegarde.');
        })
        .finally(function () {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = targetStatus === 'Brouillon'
                    ? '<i class="fa-regular fa-floppy-disk me-1"></i>Sauvegarder le brouillon'
                    : '<i class="fa-solid fa-paper-plane me-1"></i>Soumettre l\'alerte';
            }
        });
    }

    // Boutons d'action
    var btnSaveDraft        = document.getElementById('btnSaveDraft');
    var btnSaveDraftSticky  = document.getElementById('btn-save-draft-sticky');
    var btnSubmitCluster    = document.getElementById('btnSubmitCluster');

    if (btnSaveDraft)       { btnSaveDraft.addEventListener('click',       function () { submitWizard('Brouillon'); }); }
    if (btnSaveDraftSticky) { btnSaveDraftSticky.addEventListener('click', function () { submitWizard('Brouillon'); }); }
    if (btnSubmitCluster)   { btnSubmitCluster.addEventListener('click',   function () { submitWizard('Soumis');    }); }

    // ─────────────────────────────────────────────
    // 10. HYDRATATION depuis brouillon existant
    // ─────────────────────────────────────────────
    function hydrateFormFromDraft(draft) {
        if (!draft || typeof draft !== 'object') { return; }
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
            // Nouvelles colonnes correctes
            facts_text: 'facts_text',
            analysis_text: 'analysis_text',
            recommendations_text: 'recommendations_text',
            priority_needs: 'priority_needs'
        };
        Object.keys(mapping).forEach(function (srcKey) {
            var fieldName = mapping[srcKey];
            var el = form.querySelector('[name="' + fieldName + '"]');
            if (!el) { return; }
            var val = draft[srcKey];
            if (val === null || val === undefined) { return; }
            el.value = String(val);
        });
        if (draftIdInput && Number(draft.id || 0) > 0) {
            draftIdInput.value = String(Number(draft.id));
        }
        var lat = Number(draft.gps_lat || 0);
        var lng = Number(draft.gps_lng || 0);
        if (!isNaN(lat) && !isNaN(lng) && lat !== 0) {
            setGps(lat, lng);
        }
    }

    function hydrateFromAiPrefill() {
        var raw = '';
        try { raw = window.sessionStorage.getItem('sydra_ia_prefill') || ''; } catch (e) {}
        if (!raw) { return; }
        var prefill;
        try { prefill = JSON.parse(raw); } catch (e) { prefill = null; }
        if (!prefill) { return; }
        var aiMapping = {
            incident_type: 'incident_type',
            urgency_level: 'urgency_level',
            // L'IA génère description → on met dans facts_text
            description: 'facts_text',
            analyse: 'analysis_text',
            priority_needs: 'priority_needs',
            recommandations: 'recommendations_text',
            victims_count: 'victims_count',
            displaced_households: 'displaced_households'
        };
        Object.keys(aiMapping).forEach(function (src) {
            var el = form.querySelector('[name="' + aiMapping[src] + '"]');
            if (!el) { return; }
            var val = prefill[src];
            if (val === null || val === undefined) { return; }
            el.value = String(val);
        });
        try { window.sessionStorage.removeItem('sydra_ia_prefill'); } catch (e) {}
    }

    hydrateFormFromDraft(wizardDraftData);
    if (!wizardDraftData) { hydrateFromAiPrefill(); }
})();
</script>
