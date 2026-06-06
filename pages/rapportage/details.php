<?php
/** @var array<string, mixed>|null $rapportageView */
/** @var array<int, array<string, mixed>> $rapportageAttachments */
/** @var array<int, array<string, mixed>> $rapportageTimeline */
/** @var array<string, mixed>|null $authUser */

if (!is_array($rapportageView)) {
    echo '<div class="card"><p>Rapport introuvable.</p></div>';
    return;
}

$role = strtoupper((string) ($authUser['role'] ?? 'REPORTER'));
$isDecisionRole = in_array($role, ['ADMIN', 'CLUSTER_LEADER', 'LEAD_GTMP', 'GTMP_LEAD'], true);

$status = (string) ($rapportageView['workflow_status'] ?? 'Soumis');
$statusNormalized = strtolower(trim($status));
$statusNormalized = str_replace(['é', 'è', 'ê'], 'e', $statusNormalized);

$statusBadgeClass = 'status-badge status-neutral';
if ($statusNormalized === 'brouillon') {
    $statusBadgeClass = 'status-badge status-draft';
} elseif ($statusNormalized === 'soumis') {
    $statusBadgeClass = 'status-badge status-submitted';
} elseif ($statusNormalized === 'en revision' || $statusNormalized === 'en revue') {
    $statusBadgeClass = 'status-badge status-review';
} elseif ($statusNormalized === 'valide' || $statusNormalized === 'publie' || $statusNormalized === 'approuve') {
    $statusBadgeClass = 'status-badge status-valid';
} elseif ($statusNormalized === 'rejete') {
    $statusBadgeClass = 'status-badge status-rejected';
}

$orgName = (string) ($rapportageView['organization_name'] ?? 'Organisation inconnue');
$orgEmail = trim((string) ($rapportageView['organization_email'] ?? ''));
$orgSite = trim((string) ($rapportageView['organization_site_web'] ?? ''));
$orgLogo = trim((string) ($rapportageView['organization_logo_path'] ?? ''));
$orgLogoDisplay = $orgLogo !== '' ? $orgLogo : 'assets/img/sydra-logo/BLEU-PRIMARY-SYDRA-LOGO.png';

$reportId = (int) ($rapportageView['id'] ?? 0);
$gpsLat = is_numeric($rapportageView['gps_lat'] ?? null) ? (float) $rapportageView['gps_lat'] : null;
$gpsLng = is_numeric($rapportageView['gps_lng'] ?? null) ? (float) $rapportageView['gps_lng'] : null;
$hasGps = $gpsLat !== null && $gpsLng !== null && $gpsLat !== 0.0 && $gpsLng !== 0.0;

$decisionLocked = $isDecisionRole && !in_array($statusNormalized, ['brouillon', 'soumis'], true);
$latestDecisionEvent = null;
if (is_array($rapportageTimeline) && $rapportageTimeline !== []) {
    for ($idx = count($rapportageTimeline) - 1; $idx >= 0; $idx--) {
        $candidate = $rapportageTimeline[$idx] ?? null;
        if (is_array($candidate)) {
            $latestDecisionEvent = $candidate;
            break;
        }
    }
}

$decisionComment = trim((string) ($latestDecisionEvent['event_note'] ?? ''));
if ($decisionComment === '') {
    $decisionComment = 'Aucun commentaire renseigné.';
}

$decisionSubmittedRaw = (string) ($latestDecisionEvent['created_at'] ?? '');
$decisionSubmittedAt = $decisionSubmittedRaw;
if ($decisionSubmittedRaw !== '') {
    try {
        $decisionSubmittedAt = (new DateTime($decisionSubmittedRaw))->format('d/m/Y H:i');
    } catch (Throwable $e) {
        $decisionSubmittedAt = $decisionSubmittedRaw;
    }
}
?>

<div class="card shadow-sm rounded-4 mb-3 rapport-header-card">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="mb-1">Alerte #<?= $reportId; ?></h1>
            <p class="text-muted mb-0">
                <span class="<?= htmlspecialchars($statusBadgeClass, ENT_QUOTES, 'UTF-8'); ?>">
                    <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>
                </span>
                <span class="mx-2">•</span>
                <?= htmlspecialchars((string) ($rapportageView['report_type'] ?? 'FLASH'), ENT_QUOTES, 'UTF-8'); ?>
                <span class="mx-2">•</span>
                <?= htmlspecialchars((string) ($rapportageView['urgency_level'] ?? 'Moyenne'), ENT_QUOTES, 'UTF-8'); ?>
            </p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button type="button" class="btn btn-small js-print-report"><i class="bi bi-printer"></i> Imprimer</button>
            <a href="pages/rapportage/alerte_details.php?id=<?= $reportId; ?>&print=1" target="_blank" class="btn btn-danger btn-small">
                <i class="bi bi-filetype-pdf"></i> Télécharger alerte (PDF)
            </a>
            <a href="?page=rapportage&focus=<?= $reportId; ?>" class="btn btn-light border btn-small">
                <i class="bi bi-arrow-left"></i> Retour Hub
            </a>
        </div>
    </div>
</div>

<div class="card shadow-sm rounded-4 mb-3 org-profile-card border-0">
    <div class="row g-3 align-items-center">
        <div class="col-lg-7 d-flex align-items-center gap-3 flex-wrap">
            <img src="<?= htmlspecialchars($orgLogoDisplay, ENT_QUOTES, 'UTF-8'); ?>"
                 alt="Logo organisation"
                 class="org-logo-avatar"
                 onerror="this.onerror=null;this.src='assets/img/sydra-logo/BLEU-PRIMARY-SYDRA-LOGO.png';">

            <div>
                <small class="text-uppercase text-secondary fw-semibold d-block">Profil de l'organisation</small>
                <h2 class="h5 mb-1"><?= htmlspecialchars($orgName, ENT_QUOTES, 'UTF-8'); ?></h2>
                <div class="text-muted small">
                    <div><i class="fa-solid fa-envelope me-1 text-primary"></i><?= htmlspecialchars($orgEmail !== '' ? $orgEmail : 'Email non renseigné', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div><i class="fa-solid fa-globe me-1 text-primary"></i><?= htmlspecialchars($orgSite !== '' ? $orgSite : 'Site web non renseigné', ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
        </div>

        <div class="col-lg-5 d-flex justify-content-lg-end">
            <?php if ($orgEmail !== '' && filter_var($orgEmail, FILTER_VALIDATE_EMAIL)): ?>
            <a class="btn btn-primary shadow-sm" href="mailto:<?= htmlspecialchars($orgEmail, ENT_QUOTES, 'UTF-8'); ?>?subject=SyDRA%20-%20Alerte%20%23<?= $reportId; ?>">
                <i class="fa-solid fa-paper-plane me-2"></i>Contacter l'organisation
            </a>
            <?php else: ?>
            <button class="btn btn-outline-secondary" type="button" disabled>
                <i class="fa-solid fa-paper-plane me-2"></i>Contact indisponible
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card shadow-sm rounded-4 border-0 report-data-card h-100">
            <h2 class="h5 mb-3">Détails de l'incident</h2>

            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <div class="data-pill"><i class="fa-solid fa-location-dot text-primary"></i><span><strong>Localisation :</strong> <?= htmlspecialchars((string) ($rapportageView['location_text'] ?? $rapportageView['province'] ?? 'Non précisée'), ENT_QUOTES, 'UTF-8'); ?></span></div>
                </div>
                <div class="col-md-6">
                    <div class="data-pill"><i class="fa-solid fa-map-pin text-primary"></i><span><strong>Province :</strong> <?= htmlspecialchars((string) ($rapportageView['province'] ?? 'Non précisée'), ENT_QUOTES, 'UTF-8'); ?></span></div>
                </div>
                <div class="col-md-6">
                    <div class="data-pill"><i class="fa-solid fa-user-injured text-danger"></i><span><strong>Victimes :</strong> <?= htmlspecialchars((string) ($rapportageView['victims_count'] ?? '0'), ENT_QUOTES, 'UTF-8'); ?></span></div>
                </div>
                <div class="col-md-6">
                    <div class="data-pill"><i class="fa-solid fa-house-crack text-warning"></i><span><strong>Ménages déplacés :</strong> <?= htmlspecialchars((string) ($rapportageView['displaced_households'] ?? '0'), ENT_QUOTES, 'UTF-8'); ?></span></div>
                </div>
                <div class="col-12">
                    <div class="data-pill"><i class="fa-solid fa-crosshairs text-info"></i><span><strong>Coordonnées GPS :</strong> <?= htmlspecialchars((string) ($rapportageView['gps_lat'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars((string) ($rapportageView['gps_lng'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></span></div>
                </div>
            </div>

            <div class="content-block">
                <h3 class="h6"><i class="fa-solid fa-triangle-exclamation me-2 text-danger"></i>Incident signalé</h3>
                <p class="mb-0"><?= htmlspecialchars((string) ($rapportageView['incident_label'] ?? 'Incident'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

            <div class="content-block">
                <h3 class="h6"><i class="fa-solid fa-file-lines me-2 text-primary"></i>Description</h3>
                <p class="mb-0"><?= nl2br(htmlspecialchars((string) ($rapportageView['content'] ?? 'Aucune description fournie.'), ENT_QUOTES, 'UTF-8')); ?></p>
            </div>

            <div class="content-block">
                <h3 class="h6"><i class="fa-solid fa-magnifying-glass-chart me-2 text-success"></i>Analyse</h3>
                <p class="mb-0"><?= nl2br(htmlspecialchars((string) ($rapportageView['analysis_text'] ?? 'Aucune analyse complémentaire.'), ENT_QUOTES, 'UTF-8')); ?></p>
            </div>

            <div class="content-block mb-0">
                <h3 class="h6"><i class="fa-solid fa-notes-medical me-2 text-secondary"></i>Notes additionnelles</h3>
                <p class="mb-0"><?= nl2br(htmlspecialchars((string) ($rapportageView['additional_notes'] ?? 'Aucune note additionnelle.'), ENT_QUOTES, 'UTF-8')); ?></p>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card shadow-sm rounded-4 border-0 h-100">
            <h2 class="h5 mb-3">Mini-carte incident</h2>
            <?php if ($hasGps): ?>
                <div id="incident-mini-map" class="mini-map" data-lat="<?= htmlspecialchars((string) $gpsLat, ENT_QUOTES, 'UTF-8'); ?>" data-lng="<?= htmlspecialchars((string) $gpsLng, ENT_QUOTES, 'UTF-8'); ?>"></div>
                <p class="text-muted small mt-2 mb-0">Carte centrée sur les coordonnées exactes de l'incident.</p>
            <?php else: ?>
                <div class="mini-map-fallback">
                    <i class="fa-solid fa-map-location-dot fa-2x text-muted mb-2"></i>
                    <p class="mb-0 text-muted">Coordonnées GPS non disponibles pour cet incident.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-8">
        <div class="card shadow-sm rounded-4 border-0">
            <h2 class="h5 mb-3">Pièces jointes</h2>
            <?php if (($rapportageAttachments ?? []) === []): ?>
                <p class="text-muted mb-0">Aucune pièce jointe pour ce rapport.</p>
            <?php else: ?>
                <div class="rapportage-attachments-grid">
                    <?php foreach ($rapportageAttachments as $attachment): ?>
                        <a href="<?= htmlspecialchars((string) ($attachment['storage_path'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>" class="rapportage-attachment-item" target="_blank">
                            <i class="bi bi-paperclip"></i>
                            <strong><?= htmlspecialchars((string) ($attachment['original_name'] ?? 'Fichier'), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <small><?= htmlspecialchars((string) ($attachment['mime_type'] ?? 'Document'), ENT_QUOTES, 'UTF-8'); ?></small>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm rounded-4 border-0 rapportage-timeline-card">
            <h2 class="h5 mb-3">Timeline de traitement</h2>
            <?php if (($rapportageTimeline ?? []) === []): ?>
                <p class="text-muted mb-0">Aucun historique disponible.</p>
            <?php else: ?>
                <ul class="rapportage-timeline">
                    <?php foreach ($rapportageTimeline as $event): ?>
                        <li>
                            <div class="rapportage-timeline-dot"></div>
                            <div>
                                <strong><?= htmlspecialchars((string) ($event['status_label'] ?? 'Événement'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <p class="mb-1 text-muted"><?= htmlspecialchars((string) ($event['event_note'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                                <small class="text-muted"><?= htmlspecialchars((string) ($event['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> • <?= htmlspecialchars((string) ($event['actor_name'] ?? 'Système'), ENT_QUOTES, 'UTF-8'); ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($isDecisionRole): ?>
<div id="decision-action-panel" class="card shadow-sm rounded-4 mt-3 rapportage-decision-panel border-0"<?= $decisionLocked ? ' style="display:none;"' : ''; ?>>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h2 class="h5 mb-0">Panneau de décision Lead GTMP</h2>
        <span class="badge text-bg-light border">Validation intelligente</span>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <form method="post" action="?page=rapportage-voir&id=<?= $reportId; ?>" class="inline-form js-decision-form" data-api-action="VALIDATE">
            <input type="hidden" name="action" value="lead_report_decision">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="report_id" value="<?= $reportId; ?>">
            <input type="hidden" name="decision" value="publish">
            <input type="hidden" name="decision_comment" value="Validation Lead GTMP.">
            <button type="submit" class="btn rapportage-btn-success"><i class="fa-solid fa-badge-check me-1"></i>Valider et Publier</button>
        </form>

        <button type="button" class="btn rapportage-btn-info" data-bs-toggle="modal" data-bs-target="#decisionInfoModal">
            <i class="fa-solid fa-circle-question me-1"></i>Demander des informations
        </button>

        <button type="button" class="btn rapportage-btn-danger" data-bs-toggle="modal" data-bs-target="#decisionRejectModal">
            <i class="fa-solid fa-ban me-1"></i>Rejeter
        </button>
    </div>
</div>

<?php if ($decisionLocked): ?>
<div id="decision-lock-box" class="card shadow-sm rounded-4 mt-3 border-0 decision-locked-box">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h2 class="h5 mb-2"><i class="fa-solid fa-lock me-2"></i>Décision déjà soumise</h2>
            <p class="mb-1"><strong>Statut actuel :</strong> <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="mb-1"><strong>Commentaire du Lead :</strong> <?= nl2br(htmlspecialchars($decisionComment, ENT_QUOTES, 'UTF-8')); ?></p>
            <p class="mb-0"><strong>Soumis le :</strong> <?= htmlspecialchars($decisionSubmittedAt !== '' ? $decisionSubmittedAt : 'Date indisponible', ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div>
            <button type="button" class="btn btn-warning text-dark fw-semibold js-reopen-decision">
                <i class="fa-solid fa-rotate-left me-1"></i>Modifier la décision
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="modal fade" id="decisionInfoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="?page=rapportage-voir&id=<?= $reportId; ?>" class="js-decision-form" data-api-action="REQUEST_INFO">
                <div class="modal-header">
                    <h5 class="modal-title">Demande d'informations supplémentaires</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="lead_report_decision">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="report_id" value="<?= $reportId; ?>">
                    <input type="hidden" name="decision" value="request_info">

                    <label for="decision_info_reason" class="form-label">Raison de la demande</label>
                    <select id="decision_info_reason" name="decision_reason" class="form-select mb-3" required>
                        <option value="">Sélectionner une raison</option>
                        <option value="Manque d'informations précises">Manque d'informations précises</option>
                        <option value="Localisation incomplète">Localisation incomplète</option>
                        <option value="Bilan à vérifier">Bilan à vérifier</option>
                        <option value="Autre (Préciser)">Autre (Préciser)</option>
                    </select>

                    <label for="decision_info_comment" class="form-label">Commentaire (facultatif)</label>
                    <textarea id="decision_info_comment" name="decision_comment" class="form-control" rows="5" placeholder="Précisions à transmettre à l'organisation..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn rapportage-btn-info">Envoyer la demande</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="decisionRejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="?page=rapportage-voir&id=<?= $reportId; ?>" class="js-decision-form" data-api-action="REJECT">
                <div class="modal-header">
                    <h5 class="modal-title">Rejeter l'alerte</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="lead_report_decision">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="report_id" value="<?= $reportId; ?>">
                    <input type="hidden" name="decision" value="reject">

                    <label for="decision_reject_reason" class="form-label">Raison du rejet</label>
                    <select id="decision_reject_reason" name="decision_reason" class="form-select mb-3" required>
                        <option value="">Sélectionner une raison</option>
                        <option value="Non pertinente pour la protection">Non pertinente pour la protection</option>
                        <option value="Fausse alerte / Non avéré">Fausse alerte / Non avéré</option>
                        <option value="Incident doublon">Incident doublon</option>
                        <option value="Autre">Autre</option>
                    </select>

                    <label for="decision_reject_comment" class="form-label">Commentaire (facultatif)</label>
                    <textarea id="decision_reject_comment" name="decision_comment" class="form-control" rows="5" placeholder="Précisez les éléments de rejet..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn rapportage-btn-danger">Confirmer le rejet</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.rapport-header-card,
.org-profile-card,
.report-data-card,
.rapportage-decision-panel {
    border: 1px solid #e2e8f0;
}
.org-profile-card {
    background: linear-gradient(140deg, #ffffff 0%, #f8fbff 100%);
}
.org-logo-avatar {
    width: 76px;
    height: 76px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #dbeafe;
    background: #f8fafc;
}
.data-pill {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 10px 12px;
    background: #fff;
    display: flex;
    align-items: center;
    gap: 9px;
}
.content-block {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px;
    margin-bottom: 10px;
    background: #fcfdff;
}
.mini-map {
    width: 100%;
    height: 280px;
    border-radius: 12px;
    border: 1px solid #dbeafe;
    overflow: hidden;
}
.mini-map-fallback {
    min-height: 280px;
    border-radius: 12px;
    border: 1px dashed #cbd5e1;
    display: grid;
    place-items: center;
    text-align: center;
    padding: 20px;
}

.rapportage-decision-panel .btn,
.rapportage-decision-panel .btn i,
.modal .rapportage-btn-success,
.modal .rapportage-btn-success i,
.modal .rapportage-btn-info,
.modal .rapportage-btn-info i,
.modal .rapportage-btn-danger,
.modal .rapportage-btn-danger i {
    color: #ffffff !important;
}

.rapportage-btn-success,
.rapportage-btn-info,
.rapportage-btn-danger {
    font-weight: 700;
}

.rapportage-btn-success:hover,
.rapportage-btn-success:focus,
.rapportage-btn-success:active {
    background: #15803d !important;
    border-color: #15803d !important;
    color: #ffffff !important;
}

.rapportage-btn-info:hover,
.rapportage-btn-info:focus,
.rapportage-btn-info:active {
    background: #0369a1 !important;
    border-color: #0369a1 !important;
    color: #ffffff !important;
}

.rapportage-btn-danger:hover,
.rapportage-btn-danger:focus,
.rapportage-btn-danger:active {
    background: #b91c1c !important;
    border-color: #b91c1c !important;
    color: #ffffff !important;
}

.decision-locked-box {
    border: 1px solid #f6e0ac;
    background: linear-gradient(140deg, #fffdf5 0%, #fff8e6 100%);
}
</style>

<script>
(function () {
    function initMiniMap() {
        var mapEl = document.getElementById('incident-mini-map');
        if (!mapEl) {
            return;
        }

        if (!window.L) {
            window.setTimeout(initMiniMap, 120);
            return;
        }

        var lat = Number(mapEl.getAttribute('data-lat') || 0);
        var lng = Number(mapEl.getAttribute('data-lng') || 0);
        if (Number.isNaN(lat) || Number.isNaN(lng) || lat === 0 || lng === 0) {
            return;
        }

        var miniMap = window.L.map(mapEl, {
            zoomControl: false,
            dragging: false,
            scrollWheelZoom: false,
            doubleClickZoom: false,
            boxZoom: false,
            keyboard: false,
            tap: false
        }).setView([lat, lng], 11);

        window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(miniMap);

        window.L.circleMarker([lat, lng], {
            radius: 10,
            color: '#E53E3E',
            weight: 2,
            fillColor: '#E53E3E',
            fillOpacity: 0.82
        }).addTo(miniMap);
    }

    function bindDecisionForms() {
        var forms = document.querySelectorAll('.js-decision-form');
        if (!forms || forms.length === 0) {
            return;
        }

        function bindDecisionReopen() {
            var reopenButton = document.querySelector('.js-reopen-decision');
            var lockBox = document.getElementById('decision-lock-box');
            var actionPanel = document.getElementById('decision-action-panel');

            if (!reopenButton || !lockBox || !actionPanel) {
                return;
            }

            reopenButton.addEventListener('click', function () {
                if (!(window.Swal && typeof window.Swal.fire === 'function')) {
                    var ok = window.confirm('La réouverture annulera la décision précédente. Continuer ?');
                    if (!ok) {
                        return;
                    }
                    lockBox.style.display = 'none';
                    actionPanel.style.display = '';
                    return;
                }

                window.Swal.fire({
                    title: 'Modifier la décision ?',
                    text: 'Attention : La réouverture de cette alerte annulera la décision précédente. Vous devrez soumettre une nouvelle décision (Validation, Rejet ou Demande d\'info), et un nouvel email sera envoyé à l\'organisation pour l\'informer de ce changement.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Oui, réouvrir l\'alerte',
                    cancelButtonText: 'Annuler',
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    reverseButtons: true
                }).then(function (result) {
                    if (!result || !result.isConfirmed) {
                        return;
                    }

                    lockBox.style.display = 'none';
                    actionPanel.style.display = '';
                });
            });
        }

        var actionLabels = {
            VALIDATE: 'Valider et publier',
            REQUEST_INFO: 'Demander des informations',
            REJECT: 'Rejeter'
        };

        var actionImpacts = {
            VALIDATE: [
                'Le rapport sera marqué comme validé/publie.',
                'Une notification in-app sera envoyée à l\'organisation.',
                'Un email de validation sera tenté automatiquement.'
            ],
            REQUEST_INFO: [
                'Le rapport passe en demande d\'informations.',
                'L\'organisation devra compléter avant nouvelle revue.',
                'Une notification et un email de demande seront envoyés.'
            ],
            REJECT: [
                'Le rapport sera marqué comme rejeté.',
                'L\'organisation sera notifiée immédiatement.',
                'Un email de rejet sera tenté automatiquement.'
            ]
        };

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function confirmDecision(apiAction, payloadComment) {
            var impacts = actionImpacts[apiAction] || [];
            var title = actionLabels[apiAction] || 'Soumettre la décision';
            var impactsHtml = impacts.map(function (line) {
                return '<li style="margin-bottom:4px;">' + escapeHtml(line) + '</li>';
            }).join('');
            var commentHtml = payloadComment
                ? ('<div style="margin-top:8px;padding:8px;border:1px solid #dbe8f5;border-radius:8px;background:#f8fbff;"><strong>Commentaire transmis:</strong><br>' + escapeHtml(payloadComment) + '</div>')
                : '';

            if (!(window.Swal && typeof window.Swal.fire === 'function')) {
                return Promise.resolve(window.confirm('Confirmer la décision: ' + title + ' ?'));
            }

            return window.Swal.fire({
                title: title,
                html: '<div style="text-align:left;">'
                    + '<p style="margin-bottom:8px;">Cette action va être enregistrée maintenant.</p>'
                    + '<ul style="padding-left:18px;margin:0 0 4px;">' + impactsHtml + '</ul>'
                    + commentHtml
                    + '</div>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Oui, confirmer',
                cancelButtonText: 'Annuler',
                confirmButtonColor: '#005bbb',
                cancelButtonColor: '#94a3b8',
                reverseButtons: true
            }).then(function (result) {
                return !!(result && result.isConfirmed);
            });
        }

        function showOutcome(data, apiAction) {
            var actionTitle = actionLabels[apiAction] || 'Décision soumise';
            var statusText = String((data && data.status) ? data.status : 'Mis à jour');
            var mail = data && data.mail ? data.mail : { attempted: false, success: false, error: '' };
            var warningText = data && data.warning ? String(data.warning) : '';

            if (!(window.Swal && typeof window.Swal.fire === 'function')) {
                window.alert((warningText !== '' ? warningText : (actionTitle + ' : ' + statusText)));
                return Promise.resolve();
            }

            if (warningText !== '') {
                return window.Swal.fire({
                    icon: 'warning',
                    title: actionTitle + ' enregistrée',
                    html: '<div style="text-align:left;">'
                        + '<p><strong>Statut:</strong> ' + escapeHtml(statusText) + '</p>'
                        + '<p style="margin-bottom:0;color:#b45309;">' + escapeHtml(warningText) + '</p>'
                        + '</div>',
                    confirmButtonText: 'Compris',
                    confirmButtonColor: '#d97706'
                });
            }

            if (mail.attempted && !mail.success) {
                return window.Swal.fire({
                    icon: 'warning',
                    title: actionTitle + ' soumis avec succès',
                    html: '<div style="text-align:left;">'
                        + '<p><strong>Serveur:</strong> mise à jour effectuée en base.</p>'
                        + '<p><strong>Email:</strong> échec d\'envoi.</p>'
                        + '<p style="margin-bottom:0;color:#b91c1c;"><strong>Détail:</strong> ' + escapeHtml(String(mail.error || 'Erreur SMTP inconnue')) + '</p>'
                        + '</div>',
                    confirmButtonText: 'Compris',
                    confirmButtonColor: '#005bbb'
                });
            }

            return window.Swal.fire({
                icon: 'success',
                title: actionTitle + ' soumis avec succès',
                html: '<div style="text-align:left;">'
                    + '<p><strong>Statut:</strong> ' + escapeHtml(statusText) + '</p>'
                    + '<p><strong>Serveur:</strong> mise à jour en base confirmée.</p>'
                    + '<p style="margin-bottom:0;"><strong>Email:</strong> '
                    + (mail.attempted ? 'notification envoyée.' : 'aucun destinataire email disponible, notification in-app uniquement.')
                    + '</p>'
                    + '</div>',
                confirmButtonText: 'Continuer',
                confirmButtonColor: '#005bbb'
            });
        }

        forms.forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();

                var apiAction = String(form.getAttribute('data-api-action') || '').trim().toUpperCase();
                var reportInput = form.querySelector('input[name="report_id"]');
                var csrfInput = form.querySelector('input[name="csrf"]');
                var reasonInput = form.querySelector('select[name="decision_reason"]');
                var commentInput = form.querySelector('textarea[name="decision_comment"], input[name="decision_comment"]');

                var reportId = reportInput ? Number(reportInput.value || 0) : 0;
                var csrf = csrfInput ? String(csrfInput.value || '') : '';
                var reason = reasonInput ? String(reasonInput.value || '').trim() : '';
                var comment = commentInput ? String(commentInput.value || '').trim() : '';

                if (reportId <= 0 || apiAction === '' || csrf === '') {
                    window.alert('Paramètres de décision invalides.');
                    return;
                }

                if ((apiAction === 'REQUEST_INFO' || apiAction === 'REJECT') && reason === '') {
                    window.alert('Veuillez sélectionner une raison.');
                    return;
                }

                var payloadComment = reason;
                if (comment !== '') {
                    payloadComment = payloadComment ? (payloadComment + ' | ' + comment) : comment;
                }

                confirmDecision(apiAction, payloadComment).then(function (isConfirmed) {
                    if (!isConfirmed) {
                        return;
                    }

                    var submitButton = form.querySelector('button[type="submit"]');
                    var originalButtonHtml = submitButton ? submitButton.innerHTML : '';
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Traitement...';
                    }

                    var formData = new FormData();
                    formData.append('csrf', csrf);
                    formData.append('report_id', String(reportId));
                    formData.append('action', apiAction);
                    formData.append('comment', payloadComment);

                    var controller = (typeof AbortController !== 'undefined') ? new AbortController() : null;
                    var timeoutId = window.setTimeout(function () {
                        if (controller) {
                            controller.abort();
                        }
                    }, 20000);

                    fetch('api/change_status.php', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        signal: controller ? controller.signal : undefined
                    })
                        .then(function (res) {
                            return res.text().then(function (bodyText) {
                                var data;
                                try {
                                    data = bodyText ? JSON.parse(bodyText) : {};
                                } catch (e) {
                                    throw new Error('Réponse serveur invalide (JSON attendu).');
                                }

                                if (!res.ok) {
                                    var msg = data && data.message ? String(data.message) : ('Erreur HTTP ' + res.status);
                                    throw new Error(msg);
                                }

                                return data;
                            });
                        })
                        .then(function (data) {
                            if (!data || (data.ok !== true && data.success !== true)) {
                                var baseMsg = (data && data.message) ? String(data.message) : 'Erreur de traitement';
                                var debugMsg = (data && data.error) ? String(data.error) : '';
                                throw new Error(debugMsg !== '' ? (baseMsg + ' (' + debugMsg + ')') : baseMsg);
                            }

                            var modalEl = form.closest('.modal');
                            if (modalEl && window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                                var modal = window.bootstrap.Modal.getInstance(modalEl);
                                if (modal) {
                                    modal.hide();
                                }
                            }

                            return showOutcome(data, apiAction).then(function () {
                                window.location.reload();
                            });
                        })
                        .catch(function (err) {
                            var isAbort = err && err.name === 'AbortError';
                            var message = isAbort
                                ? 'Le serveur met trop de temps à répondre. Réessayez.'
                                : String(err && err.message ? err.message : err);
                            if (window.Swal && typeof window.Swal.fire === 'function') {
                                window.Swal.fire({
                                    icon: 'error',
                                    title: 'Problème serveur',
                                    html: '<p style="margin:0;">La décision n\'a pas été appliquée.<br><strong>Détail:</strong> ' + escapeHtml(message) + '</p>',
                                    confirmButtonColor: '#005bbb'
                                });
                            } else {
                                window.alert('Impossible de traiter la décision: ' + message);
                            }
                        })
                        .finally(function () {
                            window.clearTimeout(timeoutId);
                            if (submitButton) {
                                submitButton.disabled = false;
                                submitButton.innerHTML = originalButtonHtml;
                            }
                        });
                });
            });
        });

        bindDecisionReopen();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initMiniMap();
            bindDecisionForms();
        });
    } else {
        initMiniMap();
        bindDecisionForms();
    }
})();
</script>
