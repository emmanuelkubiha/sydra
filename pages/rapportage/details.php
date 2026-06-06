<?php
/**
 * ROLE DU FICHIER:
 * - Afficher le détail complet d'une alerte (données, pièces, timeline, carte).
 * - Piloter la prise de décision Lead/Admin (validation, demande d'infos, rejet).
 * - Orchestrer les popups SweetAlert2 et l'appel API sécurisé vers change_status.
 */
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

// Helpers UI: normalisation et mapping statut -> icône/couleurs.
if (!function_exists('normalize_status_token')) {
    function normalize_status_token(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = str_replace(['é', 'è', 'ê', 'à', 'ù', 'ô', 'î', 'ï', 'ç', "'", '-'], ['e', 'e', 'e', 'a', 'u', 'o', 'i', 'i', 'c', ' ', ' '], $normalized);
        return preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
    }
}

if (!function_exists('status_ui_meta')) {
    /**
     * @return array{icon:string,text_class:string,bg_class:string,badge_class:string}
     */
    function status_ui_meta(string $statusLabel): array
    {
        $normalized = normalize_status_token($statusLabel);

        if (str_contains($normalized, 'valide') || str_contains($normalized, 'approuve') || str_contains($normalized, 'publie')) {
            return ['icon' => 'fa-solid fa-circle-check', 'text_class' => 'text-success', 'bg_class' => 'timeline-dot-success', 'badge_class' => 'text-bg-success'];
        }

        if (str_contains($normalized, 'rejete') || str_contains($normalized, 'rejet')) {
            return ['icon' => 'fa-solid fa-circle-xmark', 'text_class' => 'text-danger', 'bg_class' => 'timeline-dot-danger', 'badge_class' => 'text-bg-danger'];
        }

        if (str_contains($normalized, 'revue') || str_contains($normalized, 'revision') || str_contains($normalized, 'demande information')) {
            return ['icon' => 'fa-solid fa-hourglass-half', 'text_class' => 'text-warning', 'bg_class' => 'timeline-dot-warning', 'badge_class' => 'text-bg-warning'];
        }

        if (str_contains($normalized, 'soumis') || str_contains($normalized, 'cree')) {
            return ['icon' => 'fa-solid fa-paper-plane', 'text_class' => 'text-primary', 'bg_class' => 'timeline-dot-primary', 'badge_class' => 'text-bg-primary'];
        }

        return ['icon' => 'fa-solid fa-circle-info', 'text_class' => 'text-secondary', 'bg_class' => 'timeline-dot-secondary', 'badge_class' => 'text-bg-secondary'];
    }
}

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

// Le panneau est verrouillé dès qu'une décision est déjà posée.
$decisionLocked = $isDecisionRole && !in_array($statusNormalized, ['brouillon', 'soumis'], true);
$latestDecisionEvent = null;
if (is_array($rapportageTimeline) && $rapportageTimeline !== []) {
    for ($idx = count($rapportageTimeline) - 1; $idx >= 0; $idx--) {
        $candidate = $rapportageTimeline[$idx] ?? null;
        if (is_array($candidate)) {
            $eventStatus = strtolower(trim((string) ($candidate['status_label'] ?? '')));
            $eventStatus = str_replace(['é', 'è', 'ê'], 'e', $eventStatus);
            if (in_array($eventStatus, ['approuve', 'rejete', 'demande information', 'demande d information', 'demande info', 'en revision', 'en revue'], true)) {
                $latestDecisionEvent = $candidate;
                break;
            }

            if ($latestDecisionEvent === null) {
                $latestDecisionEvent = $candidate;
            }
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

$alertSubmittedRaw = trim((string) ($rapportageView['submitted_at'] ?? $rapportageView['created_at'] ?? ''));
$alertSubmittedAt = $alertSubmittedRaw;
if ($alertSubmittedRaw !== '') {
    try {
        $alertSubmittedAt = (new DateTime($alertSubmittedRaw))->format('d/m/Y H:i');
    } catch (Throwable $e) {
        $alertSubmittedAt = $alertSubmittedRaw;
    }
}

$decisionLockTone = 'decision-lock-warning';
if (in_array($statusNormalized, ['valide', 'publie', 'approuve'], true)) {
    $decisionLockTone = 'decision-lock-success';
} elseif (in_array($statusNormalized, ['rejete'], true)) {
    $decisionLockTone = 'decision-lock-danger';
} elseif (in_array($statusNormalized, ['en revision', 'en revue', 'demande information', 'demande d information', 'demande info'], true)) {
    $decisionLockTone = 'decision-lock-info';
}

$aiAnalysisEnabledStatuses = ['soumis', 'en revue', 'en revision', 'demande information', 'demande d information', 'demande info'];
$isAiAnalysisVisible = $isDecisionRole && in_array($statusNormalized, $aiAnalysisEnabledStatuses, true);

// Métadonnées visuelles du statut courant pour le panneau verrouillé.
$decisionStatusMeta = status_ui_meta($status);
$reviewDeadlineRaw = trim((string) ($rapportageView['review_deadline'] ?? ''));
$reviewDeadlineAt = $reviewDeadlineRaw;
if ($reviewDeadlineRaw !== '') {
    try {
        $reviewDeadlineAt = (new DateTime($reviewDeadlineRaw))->format('d/m/Y H:i');
    } catch (Throwable $e) {
        $reviewDeadlineAt = $reviewDeadlineRaw;
    }
}

$aiReportContext = [
    'report_id' => $reportId,
    'organization_name' => $orgName,
    'workflow_status' => $status,
    'report_type' => (string) ($rapportageView['report_type'] ?? 'FLASH'),
    'urgency_level' => (string) ($rapportageView['urgency_level'] ?? 'Moyenne'),
    'location_text' => (string) ($rapportageView['location_text'] ?? $rapportageView['province'] ?? ''),
    'incident_label' => (string) ($rapportageView['incident_label'] ?? ''),
    'content' => (string) ($rapportageView['content'] ?? ''),
    'analysis_text' => (string) ($rapportageView['analysis_text'] ?? ''),
    'additional_notes' => (string) ($rapportageView['additional_notes'] ?? ''),
    'victims_count' => (int) ($rapportageView['victims_count'] ?? 0),
    'displaced_households' => (int) ($rapportageView['displaced_households'] ?? 0),
    'recommendations_text' => (string) ($rapportageView['recommendations_text'] ?? ''),
    'priority_needs_text' => (string) ($rapportageView['priority_needs_text'] ?? ''),
];
$aiReportContextJson = json_encode($aiReportContext, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
if (!is_string($aiReportContextJson)) {
    $aiReportContextJson = '{}';
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
            <?php if ($alertSubmittedAt !== ''): ?>
                <p class="text-muted small mb-0 mt-1">
                    <i class="fa-solid fa-calendar-check text-primary me-1"></i>
                    Soumise le <?= htmlspecialchars($alertSubmittedAt, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>
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
                        <?php
                            $timelineLabel = (string) ($event['status_label'] ?? $event['action'] ?? 'Événement');
                            $timelineMeta = status_ui_meta($timelineLabel);
                        ?>
                        <li>
                            <div class="rapportage-timeline-dot <?= htmlspecialchars($timelineMeta['bg_class'], ENT_QUOTES, 'UTF-8'); ?>">
                                <i class="<?= htmlspecialchars($timelineMeta['icon'], ENT_QUOTES, 'UTF-8'); ?> <?= htmlspecialchars($timelineMeta['text_class'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                            </div>
                            <div>
                                <strong class="d-inline-flex align-items-center gap-2">
                                    <span><?= htmlspecialchars((string) ($event['status_label'] ?? 'Événement'), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="badge rounded-pill <?= htmlspecialchars($timelineMeta['badge_class'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars((string) ($event['status_label'] ?? 'Événement'), ENT_QUOTES, 'UTF-8'); ?></span>
                                </strong>
                                <?php if ((int) ($event['is_decision_change'] ?? 0) === 1): ?>
                                    <span class="badge decision-change-badge ms-2">Décision modifiée</span>
                                <?php endif; ?>
                                <?php if (trim((string) ($event['change_note'] ?? '')) !== ''): ?>
                                    <p class="mb-1 small decision-change-note"><?= htmlspecialchars((string) $event['change_note'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endif; ?>
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

<?php if ($isAiAnalysisVisible): ?>
<button type="button"
        class="btn btn-primary ai-analysis-fab"
        data-bs-toggle="offcanvas"
        data-bs-target="#aiAnalysisOffcanvas"
        aria-controls="aiAnalysisOffcanvas">
    <i class="fa-solid fa-robot me-1"></i>Discuter avec l'IA
</button>

<div class="offcanvas offcanvas-end ai-analysis-offcanvas" tabindex="-1" id="aiAnalysisOffcanvas" aria-labelledby="aiAnalysisOffcanvasLabel">
    <div class="offcanvas-header border-bottom">
        <h2 class="offcanvas-title h6 mb-0" id="aiAnalysisOffcanvasLabel">
            <i class="fa-solid fa-brain me-2 text-primary"></i>Assistant d'analyse décisionnelle
        </h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-0">
        <div class="ai-analysis-chat" id="ai-analysis-chat"></div>
        <form id="ai-analysis-form" class="ai-analysis-form border-top">
            <input type="hidden" id="ai-analysis-csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <textarea id="ai-analysis-input" class="form-control" rows="2" placeholder="Ex: Fais-moi un résumé de 2 lignes" required></textarea>
            <button type="submit" class="btn btn-primary" id="ai-analysis-send">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

<div id="decision-action-panel" class="card border-0 shadow-sm rounded-4 mt-3 rapportage-decision-panel"<?= $decisionLocked ? ' style="display:none;"' : ''; ?>>
    <div class="card-header decision-panel-header border-0 rounded-top-4 px-4 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="h5 mb-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-gavel"></i>
            <span>Prise de décision (Cluster)</span>
        </h2>
        <span class="badge text-bg-light border">Validation intelligente</span>
    </div>
    <div class="card-body p-4">
        <div id="decision-reopen-banner" class="alert alert-warning rounded-4 border-0 d-flex justify-content-between align-items-center gap-3 mb-3" style="display:none;">
            <div>
                <strong>Réouverture en cours.</strong>
                Choisissez une nouvelle décision pour finaliser la modification.
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 js-cancel-reopen">
                Annuler la réouverture
            </button>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <form method="post" action="?page=rapportage-voir&id=<?= $reportId; ?>" class="inline-form js-decision-form h-100" data-api-action="VALIDATE">
                    <input type="hidden" name="action" value="lead_report_decision">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="report_id" value="<?= $reportId; ?>">
                    <input type="hidden" name="decision" value="publish">
                    <input type="hidden" name="decision_comment" value="Validation Lead GTMP.">
                    <button type="submit" class="btn decision-action-card decision-action-success w-100 h-100">
                        <span class="decision-action-icon"><i class="bi bi-check2-circle"></i></span>
                        <span class="decision-action-title">Valider &amp; Publier</span>
                        <span class="decision-action-subtitle">Publication immédiate et notification organisation</span>
                    </button>
                </form>
            </div>

            <div class="col-md-4">
                <button type="button" class="btn decision-action-card decision-action-info w-100 h-100" data-bs-toggle="modal" data-bs-target="#decisionInfoModal">
                    <span class="decision-action-icon"><i class="fa-solid fa-circle-question"></i></span>
                    <span class="decision-action-title">Demander des infos</span>
                    <span class="decision-action-subtitle">Renvoi au reporter pour précisions ciblées</span>
                </button>
            </div>

            <div class="col-md-4">
                <button type="button" class="btn decision-action-card decision-action-danger w-100 h-100" data-bs-toggle="modal" data-bs-target="#decisionRejectModal">
                    <span class="decision-action-icon"><i class="fa-solid fa-ban"></i></span>
                    <span class="decision-action-title">Rejeter</span>
                    <span class="decision-action-subtitle">Clôture avec justification et notification</span>
                </button>
            </div>
        </div>
    </div>
</div>

<?php if ($decisionLocked): ?>
<div id="decision-lock-box" class="card shadow-sm rounded-4 mt-3 border-0 decision-locked-box <?= htmlspecialchars($decisionLockTone, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h2 class="h5 mb-2 d-flex align-items-center gap-2"><i class="fa-solid fa-lock"></i>Décision déjà soumise</h2>
                <p class="mb-1 d-flex align-items-center gap-2 flex-wrap">
                    <strong>Statut actuel :</strong>
                    <span class="badge rounded-pill fs-6 <?= htmlspecialchars($decisionStatusMeta['badge_class'], ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="<?= htmlspecialchars($decisionStatusMeta['icon'], ENT_QUOTES, 'UTF-8'); ?> me-1"></i>
                        <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </p>
                <p class="mb-2"><strong>Soumis le :</strong> <?= htmlspecialchars($decisionSubmittedAt !== '' ? $decisionSubmittedAt : 'Date indisponible', ENT_QUOTES, 'UTF-8'); ?></p>
                <?php if (in_array($statusNormalized, ['en revision', 'en revue', 'demande information', 'demande d information', 'demande info'], true) && $reviewDeadlineAt !== ''): ?>
                    <p class="mb-2"><strong>Délai de réponse :</strong> <?= htmlspecialchars($reviewDeadlineAt, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
                <div class="decision-comment-box">
                    <strong>Commentaire du Lead :</strong>
                    <div class="mt-1"><?= nl2br(htmlspecialchars($decisionComment, ENT_QUOTES, 'UTF-8')); ?></div>
                </div>
            </div>
            <div class="align-self-end">
                <button type="button" class="btn btn-sydra-secondary rounded-3 fw-semibold js-reopen-decision">
                    <i class="fa-solid fa-pen-to-square me-1"></i>Modifier la décision
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="modal fade" id="decisionInfoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content decision-modal-content border-0 rounded-4">
            <form method="post" action="?page=rapportage-voir&id=<?= $reportId; ?>" class="js-decision-form" data-api-action="REQUEST_INFO">
                <div class="modal-header decision-modal-header border-0">
                    <h5 class="modal-title">Demande d'informations supplémentaires</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="lead_report_decision">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="report_id" value="<?= $reportId; ?>">
                    <input type="hidden" name="decision" value="request_info">

                    <label for="decision_info_reason" class="form-label">Raison de la demande</label>
                    <select id="decision_info_reason" name="decision_reason" class="form-select mb-3">
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
                    <button type="submit" class="btn btn-sydra-secondary">Envoyer la demande</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="decisionRejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content decision-modal-content border-0 rounded-4">
            <form method="post" action="?page=rapportage-voir&id=<?= $reportId; ?>" class="js-decision-form" data-api-action="REJECT">
                <div class="modal-header decision-modal-header border-0">
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
                    <button type="submit" class="btn btn-sydra-danger">Confirmer le rejet</button>
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



.rapportage-decision-panel {
    border: 1px solid #0050a6;
    overflow: hidden;
    background: linear-gradient(180deg, #0664cc 0%, #005bbb 100%);
}

.decision-panel-header {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(2px);
}

.rapportage-decision-panel .card-header h2,
.rapportage-decision-panel .card-header i {
    color: #ffffff;
}

.rapportage-decision-panel .card-header .badge {
    background: rgba(255, 255, 255, 0.18) !important;
    color: #ffffff !important;
    border-color: rgba(255, 255, 255, 0.35) !important;
}

.decision-action-card {
    min-height: 126px;
    border-radius: 14px;
    border: 1px solid transparent;
    padding: 14px;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: flex-start;
    gap: 8px;
    text-align: left;
    transition: all 0.3s ease;
    transform: translateY(0);
}

.decision-action-card:hover,
.decision-action-card:focus {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(0, 91, 187, 0.18);
}

.decision-action-icon {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    background: rgba(255, 255, 255, 0.7);
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.1);
}

.decision-action-success .decision-action-icon i {
    color: #ffffff !important;
}

.decision-action-info .decision-action-icon i {
    color: #ffffff !important;
}

.decision-action-danger .decision-action-icon i {
    color: #ffffff !important;
}

.decision-action-title {
    font-size: 1.03rem;
    font-weight: 700;
}

.decision-action-subtitle {
    font-size: 0.86rem;
    opacity: 0.93;
}

.decision-action-success {
    background: linear-gradient(145deg, #0ea86a 0%, #0a8f5a 100%);
    border-color: #32c187;
    color: #ffffff !important;
}

.decision-action-info {
    background: linear-gradient(145deg, #0d7fd3 0%, #0869b1 100%);
    border-color: #4aa7ea;
    color: #ffffff !important;
}

.decision-action-danger {
    background: linear-gradient(145deg, #d93450 0%, #b6263e 100%);
    border-color: #f06d84;
    color: #ffffff !important;
}

.decision-action-title,
.decision-action-subtitle {
    color: #ffffff;
}

.decision-lock-box {
    border-left: 6px solid #f59e0b;
    background: linear-gradient(140deg, #fffdf5 0%, #fff8e6 100%);
}

.decision-lock-success {
    border-left-color: #10b981;
    background: linear-gradient(140deg, #f2fff8 0%, #ebfdf4 100%);
}

.decision-lock-danger {
    border-left-color: #ef4444;
    background: linear-gradient(140deg, #fff5f5 0%, #ffecec 100%);
}

.decision-lock-info {
    border-left-color: #0ea5e9;
    background: linear-gradient(140deg, #f2fbff 0%, #e6f5ff 100%);
}

.decision-lock-warning {
    border-left-color: #f59e0b;
}

.decision-comment-box {
    background: rgba(248, 250, 252, 0.95);
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    padding: 12px;
    font-style: italic;
}

.decision-change-badge {
    background: #e8f1ff;
    color: #005bbb;
    border: 1px solid #cfe1ff;
    font-weight: 600;
}

.decision-change-note {
    color: #005bbb;
    font-weight: 600;
}

.decision-modal-content {
    box-shadow: 0 20px 48px rgba(15, 23, 42, 0.18);
}

.decision-modal-header {
    background: linear-gradient(135deg, #f1f7ff 0%, #e4f0ff 100%);
    border-bottom: 1px solid #d8e8ff !important;
}

.decision-modal-content .modal-body .form-control,
.decision-modal-content .modal-body .form-select {
    border-radius: 10px;
    border-color: #cfe1ff;
    background: #f9fcff;
}

.decision-modal-content .modal-body .form-control:focus,
.decision-modal-content .modal-body .form-select:focus {
    border-color: #005bbb;
    box-shadow: 0 0 0 0.2rem rgba(0, 91, 187, 0.15);
}

.btn-sydra-secondary,
.btn-sydra-danger {
    border-radius: 10px;
    font-weight: 700;
    padding: 0.48rem 0.95rem;
}

.btn-sydra-secondary {
    background: #005bbb;
    color: #ffffff;
    border: 1px solid #005bbb;
}

.btn-sydra-secondary:hover,
.btn-sydra-secondary:focus {
    background: #004a97;
    border-color: #004a97;
    color: #ffffff;
}

.btn-sydra-danger {
    background: #d7263d;
    color: #ffffff;
    border: 1px solid #d7263d;
}

.btn-sydra-danger:hover,
.btn-sydra-danger:focus {
    background: #b81e33;
    border-color: #b81e33;
    color: #ffffff;
}

.sydra-swal-popup {
    border-radius: 1rem !important;
    border: 0 !important;
    box-shadow: 0 1rem 2.5rem rgba(15, 23, 42, 0.24) !important;
}

.sydra-swal-popup .swal2-title {
    font-size: 1.08rem !important;
    line-height: 1.35 !important;
}

.sydra-swal-popup .swal2-html-container {
    font-size: 0.92rem !important;
    line-height: 1.45 !important;
    margin-top: 0.4rem !important;
}

.sydra-swal-popup .swal2-input-label {
    font-size: 0.84rem !important;
    font-weight: 600 !important;
    color: #334155 !important;
    margin: 0.38rem 0 0.22rem !important;
}

.sydra-swal-confirm,
.sydra-swal-cancel {
    border-radius: 0.75rem !important;
    padding: 0.42rem 0.9rem !important;
    font-size: 0.86rem !important;
    font-weight: 700 !important;
}

.sydra-swal-confirm {
    background: #005bbb !important;
    color: #fff !important;
}

.sydra-swal-cancel {
    background: #f8fafc !important;
    color: #334155 !important;
    border: 1px solid #cbd5e1 !important;
}

.sydra-swal-input {
    background: #f8fafc !important;
    border: 1px solid #dbeafe !important;
    border-radius: 0.75rem !important;
    min-height: 120px !important;
}

.sydra-swal-input:focus {
    border-color: #005bbb !important;
    box-shadow: 0 0 0 0.2rem rgba(0, 91, 187, 0.2) !important;
}

.mail-help-box {
    margin-top: 0.45rem;
    border: 1px solid #fed7aa;
    background: #fff7ed;
    border-radius: 10px;
    padding: 10px 12px;
}

.mail-help-box strong {
    color: #9a3412;
}

.mail-help-list {
    margin: 0.3rem 0 0;
    padding-left: 1.1rem;
}

.mail-help-list li {
    margin-bottom: 0.18rem;
    color: #7c2d12;
}

.sydra-swal-icon-logo {
    border: 0 !important;
    width: 4.2em !important;
    height: 4.2em !important;
    margin: 0.35em auto 0.5em !important;
}

.sydra-swal-icon-logo .swal2-icon-content {
    display: flex !important;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

.sydra-swal-icon-logo img {
    width: 54px;
    height: 54px;
    object-fit: contain;
}

.sydra-swal-icon-pulse {
    animation: sydraPulseIcon 1.15s ease-in-out infinite;
}

.rapportage-timeline-dot {
    width: 34px;
    height: 34px;
    min-width: 34px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #dbe2ea;
}

.rapportage-timeline-dot i {
    font-size: 0.95rem;
}

.timeline-dot-primary {
    background: rgba(59, 130, 246, 0.15);
}

.timeline-dot-success {
    background: rgba(16, 185, 129, 0.14);
}

.timeline-dot-danger {
    background: rgba(239, 68, 68, 0.14);
}

.timeline-dot-warning {
    background: rgba(245, 158, 11, 0.18);
}

.timeline-dot-secondary {
    background: rgba(100, 116, 139, 0.15);
}

.ai-analysis-fab {
    position: fixed;
    right: 18px;
    bottom: 22px;
    z-index: 1080;
    border-radius: 999px;
    padding: 0.55rem 1rem;
    box-shadow: 0 14px 24px rgba(0, 91, 187, 0.28);
}

.ai-analysis-offcanvas {
    width: min(460px, 96vw);
}

.ai-analysis-chat {
    flex: 1;
    overflow-y: auto;
    background: #f8fafc;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.ai-analysis-bubble {
    max-width: 86%;
    border-radius: 14px;
    padding: 9px 11px;
    line-height: 1.42;
    white-space: pre-wrap;
}

.ai-analysis-bubble.user {
    align-self: flex-end;
    background: #005BBB;
    color: #fff;
    border-bottom-right-radius: 8px;
}

.ai-analysis-bubble.assistant {
    align-self: flex-start;
    background: #e2e8f0;
    color: #0f172a;
    border-bottom-left-radius: 8px;
}

.ai-analysis-form {
    display: flex;
    gap: 8px;
    padding: 10px;
    background: #fff;
}

.ai-analysis-form textarea {
    resize: none;
}

@keyframes sydraPulseIcon {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.06);
    }
    100% {
        transform: scale(1);
    }
}
</style>

<script>
(function () {
    // Mini-carte locale de l'incident pour consultation rapide.
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

    // Branche les formulaires de décision vers la soumission AJAX + SweetAlert2.
    function bindDecisionForms() {
        var forms = document.querySelectorAll('.js-decision-form');
        if (!forms || forms.length === 0) {
            return;
        }

        // Classes partagées pour uniformiser le style de tous les popups.
        function getSwalBootstrapClasses() {
            return {
                popup: 'sydra-swal-popup rounded-4 border-0 shadow-lg',
                title: 'fw-bold text-dark',
                htmlContainer: 'text-start',
                icon: 'sydra-swal-icon-logo',
                input: 'sydra-swal-input bg-light border-0 rounded-3',
                confirmButton: 'btn rounded-3 px-3 py-1 fw-semibold sydra-swal-confirm',
                cancelButton: 'btn rounded-3 px-3 py-1 fw-semibold sydra-swal-cancel'
            };
        }

        function animateSwalIcon() {
            var iconEl = document.querySelector('.swal2-icon');
            if (iconEl) {
                iconEl.classList.add('sydra-swal-icon-pulse');
            }
        }

        function swalLogoIconHtml() {
            return '<img src="assets/img/sydra-logo/BLEU-PRIMARY-SYDRA-LOGO.png" alt="SyDRA">';
        }

        // Gère la réouverture temporaire d'une décision déjà verrouillée.
        function bindDecisionReopen() {
            var reopenButton = document.querySelector('.js-reopen-decision');
            var lockBox = document.getElementById('decision-lock-box');
            var actionPanel = document.getElementById('decision-action-panel');
            var reopenBanner = document.getElementById('decision-reopen-banner');
            var cancelReopenButton = document.querySelector('.js-cancel-reopen');
            var reopenTimeoutId = null;

            function relockIfNoSubmission(showInfo) {
                if (!lockBox || !actionPanel) {
                    return;
                }

                actionPanel.style.display = 'none';
                lockBox.style.display = '';
                actionPanel.setAttribute('data-reopen-mode', '0');
                if (reopenBanner) {
                    reopenBanner.style.display = 'none';
                }

                if (reopenTimeoutId !== null) {
                    window.clearTimeout(reopenTimeoutId);
                    reopenTimeoutId = null;
                }

                if (!showInfo || !(window.Swal && typeof window.Swal.fire === 'function')) {
                    return;
                }

                window.Swal.fire({
                    icon: 'info',
                    iconHtml: swalLogoIconHtml(),
                    title: 'Panneau reverrouillé',
                    text: 'Aucune nouvelle décision n\'a été soumise.',
                    customClass: getSwalBootstrapClasses(),
                    buttonsStyling: false,
                    confirmButtonText: 'Compris',
                    didOpen: animateSwalIcon
                });
            }

            function enableTemporaryReopen() {
                if (!lockBox || !actionPanel) {
                    return;
                }

                lockBox.style.display = 'none';
                actionPanel.style.display = '';
                actionPanel.setAttribute('data-reopen-mode', '1');
                if (reopenBanner) {
                    reopenBanner.style.display = '';
                }

                if (reopenTimeoutId !== null) {
                    window.clearTimeout(reopenTimeoutId);
                }

                // La réouverture est temporaire: sans nouvelle soumission, on reverrouille.
                reopenTimeoutId = window.setTimeout(function () {
                    if (actionPanel.getAttribute('data-reopen-mode') === '1') {
                        relockIfNoSubmission(true);
                    }
                }, 120000);
            }

            if (!reopenButton || !lockBox || !actionPanel) {
                return;
            }

            if (cancelReopenButton) {
                cancelReopenButton.addEventListener('click', function () {
                    relockIfNoSubmission(false);
                });
            }

            reopenButton.addEventListener('click', function () {
                if (!(window.Swal && typeof window.Swal.fire === 'function')) {
                    var ok = window.confirm('La réouverture annulera la décision précédente. Continuer ?');
                    if (!ok) {
                        return;
                    }
                    enableTemporaryReopen();
                    return;
                }

                window.Swal.fire({
                    title: 'Modifier la décision ?',
                    html: '<p style="margin:0;">Attention : La réouverture de cette alerte annulera la décision précédente. Vous devrez soumettre une nouvelle décision (Validation, Rejet ou Demande d\'info), et un nouvel email sera envoyé à l\'organisation pour l\'informer de ce changement.</p>',
                    icon: 'warning',
                    iconHtml: swalLogoIconHtml(),
                    showCancelButton: true,
                    confirmButtonText: 'Oui, réouvrir',
                    cancelButtonText: 'Annuler',
                    reverseButtons: true,
                    customClass: getSwalBootstrapClasses(),
                    buttonsStyling: false,
                    didOpen: animateSwalIcon
                }).then(function (result) {
                    if (!result || !result.isConfirmed) {
                        return;
                    }

                    enableTemporaryReopen();
                });
            });
        }

        // Libellés front pour confirmations/résultats d'actions.
        var actionLabels = {
            VALIDATE: 'Valider et publier',
            REQUEST_INFO: 'Demander des informations',
            REJECT: 'Rejeter'
        };

        var actionResultLabels = {
            VALIDATE: 'Validation',
            REQUEST_INFO: 'Demande d\'informations',
            REJECT: 'Rejet'
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

        // Confirmation générique pour Validation/Rejet.
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
                return Promise.resolve({
                    isConfirmed: window.confirm('Confirmer la décision: ' + title + ' ?'),
                    payloadComment: payloadComment
                });
            }

            return window.Swal.fire({
                title: title,
                html: '<div style="text-align:left;">'
                    + '<p style="margin-bottom:8px;">Cette action va être enregistrée maintenant.</p>'
                    + '<ul style="padding-left:18px;margin:0 0 4px;">' + impactsHtml + '</ul>'
                    + commentHtml
                    + '</div>',
                input: apiAction === 'VALIDATE' ? 'textarea' : undefined,
                inputLabel: apiAction === 'VALIDATE' ? 'Commentaire de validation (modifiable)' : undefined,
                inputValue: apiAction === 'VALIDATE' ? payloadComment : undefined,
                inputPlaceholder: apiAction === 'VALIDATE' ? 'Ex: Validation confirmée après revue du dossier.' : undefined,
                icon: 'question',
                iconHtml: swalLogoIconHtml(),
                showCancelButton: true,
                confirmButtonText: 'Oui, confirmer',
                cancelButtonText: 'Annuler',
                reverseButtons: true,
                customClass: getSwalBootstrapClasses(),
                buttonsStyling: false,
                didOpen: animateSwalIcon
            }).then(function (result) {
                var finalComment = payloadComment;
                if (apiAction === 'VALIDATE' && result && result.isConfirmed) {
                    finalComment = String(result.value || '').trim();
                }

                if (apiAction === 'VALIDATE' && finalComment === '') {
                    finalComment = 'Validation Lead GTMP.';
                }

                return {
                    isConfirmed: !!(result && result.isConfirmed),
                    payloadComment: finalComment
                };
            });
        }

        // Formulaire enrichi pour Demande d'infos: commentaire + délai d'expiration.
        function promptRequestInfoDecision(defaultComment) {
            if (!(window.Swal && typeof window.Swal.fire === 'function')) {
                return Promise.resolve({
                    isConfirmed: window.confirm('Confirmer la demande d\'informations ?'),
                    payloadComment: String(defaultComment || '').trim(),
                    reviewDelayHours: 168
                });
            }

            var optionsHtml = [
                '<option value="24">24 Heures</option>',
                '<option value="48">48 Heures</option>',
                '<option value="72">3 Jours</option>',
                '<option value="168" selected>7 Jours (Défaut)</option>'
            ].join('');

            return window.Swal.fire({
                title: 'Demander des informations',
                icon: 'warning',
                iconHtml: swalLogoIconHtml(),
                html: '<div style="text-align:left;">'
                    + '<label for="swal-request-comment" style="font-weight:600;margin-bottom:6px;display:block;">Commentaire</label>'
                    + '<textarea id="swal-request-comment" class="swal2-textarea" style="display:block;margin:0;width:100%;min-height:110px;" placeholder="Précisez ce qui manque...">' + escapeHtml(defaultComment || '') + '</textarea>'
                    + '<label for="swal-request-deadline" style="font-weight:600;margin:10px 0 6px;display:block;">Délai de réponse</label>'
                    + '<select id="swal-request-deadline" class="swal2-select" style="display:block;margin:0;width:100%;">' + optionsHtml + '</select>'
                    + '<div style="margin-top:10px;padding:10px;border:1px solid #fecaca;border-radius:10px;background:#fff1f2;color:#b91c1c;font-size:0.9rem;">'
                    + '<strong>Attention :</strong> Si l\'organisation ne répond pas avant ce délai, l\'alerte sera automatiquement rejetée par le système.'
                    + '</div>'
                    + '</div>',
                showCancelButton: true,
                confirmButtonText: 'Envoyer la demande',
                cancelButtonText: 'Annuler',
                reverseButtons: true,
                customClass: getSwalBootstrapClasses(),
                buttonsStyling: false,
                focusConfirm: false,
                didOpen: animateSwalIcon,
                preConfirm: function () {
                    var commentEl = document.getElementById('swal-request-comment');
                    var deadlineEl = document.getElementById('swal-request-deadline');
                    var commentValue = commentEl ? String(commentEl.value || '').trim() : '';
                    var deadlineValue = deadlineEl ? String(deadlineEl.value || '').trim() : '168';

                    if (commentValue === '') {
                        window.Swal.showValidationMessage('Veuillez saisir un commentaire pour la demande d\'informations.');
                        return false;
                    }

                    if (['24', '48', '72', '168'].indexOf(deadlineValue) === -1) {
                        window.Swal.showValidationMessage('Veuillez sélectionner un délai valide.');
                        return false;
                    }

                    return {
                        payloadComment: commentValue,
                        reviewDelayHours: Number(deadlineValue)
                    };
                }
            }).then(function (result) {
                var value = result && result.value ? result.value : {};
                return {
                    isConfirmed: !!(result && result.isConfirmed),
                    payloadComment: String(value.payloadComment || '').trim(),
                    reviewDelayHours: Number(value.reviewDelayHours || 168)
                };
            });
        }

        // Routeur de décision: choisit le bon workflow popup selon l'action.
        function soumettreDecision(apiAction, payloadComment) {
            if (apiAction === 'REQUEST_INFO') {
                return promptRequestInfoDecision(payloadComment);
            }

            return confirmDecision(apiAction, payloadComment);
        }

        // Restitution métier post-API: succès, warning SMTP, ou erreur bloquante.
        function showOutcome(data, apiAction) {
            var actionTitle = actionResultLabels[apiAction] || 'Décision';
            var statusText = String((data && data.status) ? data.status : 'Mis à jour');
            var mail = data && data.mail ? data.mail : { attempted: false, success: false, error: '' };
            var warningText = data && data.warning ? String(data.warning) : '';

            function buildMailTroubleshooting(errorText) {
                var err = String(errorText || '').toLowerCase();
                var steps = [];

                if (err.indexOf('phpmailer') !== -1) {
                    steps.push('Depuis la racine SyDRA, executer: composer install');
                    steps.push('Si besoin, executer: composer require phpmailer/phpmailer');
                    steps.push('Verifier que vendor/autoload.php existe bien sur le serveur.');
                }
                if (err.indexOf('vendor/autoload.php est absent') !== -1 || err.indexOf('vendor/autoload.php') !== -1) {
                    steps.push('Verifier les permissions de lecture du dossier vendor pour Apache/PHP.');
                }
                if (err.indexOf('smtp_host') !== -1 || err.indexOf('smtp_port') !== -1 || err.indexOf('smtp_user') !== -1 || err.indexOf('smtp_pass') !== -1 || err.indexOf('smtp_secure') !== -1) {
                    steps.push('Vérifier les variables SMTP dans .env ou .env. (host, port, user, pass, secure).');
                }
                if (err.indexOf('connection') !== -1 || err.indexOf('timed out') !== -1) {
                    steps.push('Tester la connectivité SMTP (port, pare-feu, DNS, accès sortant).');
                }

                if (steps.length === 0) {
                    steps.push('Contrôler la configuration SMTP dans config/config.php.');
                    steps.push('Lire les logs Apache/PHP pour le détail technique de l\'échec.');
                }

                return '<div class="mail-help-box">'
                    + '<strong>Actions à tenter :</strong>'
                    + '<ol class="mail-help-list">'
                    + steps.map(function (s) { return '<li>' + escapeHtml(s) + '</li>'; }).join('')
                    + '</ol>'
                    + '</div>';
            }

            function statusIconHtml(statusValue) {
                var normalized = String(statusValue || '')
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .trim();

                if (normalized.indexOf('approuve') !== -1 || normalized.indexOf('valide') !== -1 || normalized.indexOf('publie') !== -1) {
                    return '<i class="bi bi-patch-check-fill text-success me-1"></i>';
                }
                if (normalized.indexOf('revision') !== -1 || normalized.indexOf('revue') !== -1 || normalized.indexOf('demande') !== -1) {
                    return '<i class="bi bi-hourglass-split text-warning me-1"></i>';
                }
                if (normalized.indexOf('rejet') !== -1) {
                    return '<i class="bi bi-x-octagon-fill text-danger me-1"></i>';
                }
                if (normalized.indexOf('soumis') !== -1) {
                    return '<i class="bi bi-send-check-fill text-primary me-1"></i>';
                }

                return '<i class="bi bi-info-circle-fill text-secondary me-1"></i>';
            }

            if (!(window.Swal && typeof window.Swal.fire === 'function')) {
                window.alert((warningText !== '' ? warningText : (actionTitle + ' : ' + statusText)));
                return Promise.resolve();
            }

            if (warningText !== '') {
                var warningDetail = String(mail && mail.error ? mail.error : '');
                return window.Swal.fire({
                    icon: 'warning',
                    iconHtml: swalLogoIconHtml(),
                    title: actionTitle + ' enregistrée',
                    html: '<div style="text-align:left;">'
                        + '<p style="margin-bottom:6px;"><strong>Statut :</strong> ' + statusIconHtml(statusText) + escapeHtml(statusText) + '</p>'
                        + '<p style="margin-bottom:6px;color:#b45309;">' + escapeHtml(warningText) + '</p>'
                        + (warningDetail !== '' ? ('<p style="margin-bottom:0;color:#7c2d12;"><strong>Détail SMTP :</strong> ' + escapeHtml(warningDetail) + '</p>') : '')
                        + buildMailTroubleshooting(warningDetail)
                        + '</div>',
                    confirmButtonText: 'Compris',
                    customClass: getSwalBootstrapClasses(),
                    buttonsStyling: false,
                    didOpen: animateSwalIcon
                });
            }

            if (mail.attempted && !mail.success) {
                return window.Swal.fire({
                    icon: 'warning',
                    iconHtml: swalLogoIconHtml(),
                    title: actionTitle + ' enregistrée',
                    html: '<div style="text-align:left;">'
                        + '<p><strong>Serveur :</strong> mise à jour effectuée en base.</p>'
                        + '<p><strong>Email :</strong> échec d\'envoi.</p>'
                        + '<p style="margin-bottom:0;color:#b91c1c;"><strong>Détail SMTP :</strong> ' + escapeHtml(String(mail.error || 'Erreur SMTP inconnue')) + '</p>'
                        + buildMailTroubleshooting(String(mail.error || ''))
                        + '</div>',
                    confirmButtonText: 'Compris',
                    customClass: getSwalBootstrapClasses(),
                    buttonsStyling: false,
                    didOpen: animateSwalIcon
                });
            }

            return window.Swal.fire({
                icon: 'success',
                iconHtml: swalLogoIconHtml(),
                title: actionTitle + ' enregistrée',
                html: '<div style="text-align:left;">'
                    + '<p><strong>Statut :</strong> ' + statusIconHtml(statusText) + escapeHtml(statusText) + '</p>'
                    + '<p><strong>Serveur :</strong> mise à jour en base confirmée.</p>'
                    + '<p style="margin-bottom:0;"><strong>Email :</strong> '
                    + (mail.attempted ? 'notification envoyée.' : 'aucun destinataire email disponible, notification in-app uniquement.')
                    + '</p>'
                    + '</div>',
                confirmButtonText: 'Continuer',
                customClass: getSwalBootstrapClasses(),
                buttonsStyling: false,
                didOpen: animateSwalIcon
            });
        }

        // Binding de chaque formulaire avec validation locale et timeout réseau.
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

                if (apiAction === 'REJECT' && reason === '') {
                    window.alert('Veuillez sélectionner une raison.');
                    return;
                }

                var payloadComment = reason;
                if (comment !== '') {
                    payloadComment = payloadComment ? (payloadComment + ' | ' + comment) : comment;
                }

                var reviewDelayHours = '';

                var decisionPromise = soumettreDecision(apiAction, payloadComment);

                decisionPromise.then(function (decisionResult) {
                    if (!decisionResult || !decisionResult.isConfirmed) {
                        return;
                    }

                    if (typeof decisionResult.payloadComment === 'string') {
                        payloadComment = decisionResult.payloadComment.trim();
                    }

                    if (apiAction === 'REQUEST_INFO' && Number(decisionResult.reviewDelayHours || 0) > 0) {
                        reviewDelayHours = String(Number(decisionResult.reviewDelayHours));
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
                    if (reviewDelayHours !== '') {
                        formData.append('review_delay_hours', reviewDelayHours);
                    }

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
                                try {
                                    window.sessionStorage.setItem('sydraSkipLoaderOnce', '1');
                                } catch (e) {
                                    // Ignore storage errors and continue normal reload.
                                }
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
                                    iconHtml: swalLogoIconHtml(),
                                    title: 'Problème serveur',
                                    html: '<p style="margin:0;">La décision n\'a pas été appliquée.<br><strong>Détail:</strong> ' + escapeHtml(message) + '</p>',
                                    customClass: getSwalBootstrapClasses(),
                                    buttonsStyling: false,
                                    confirmButtonText: 'Fermer',
                                    didOpen: animateSwalIcon
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

    function bindAiAnalysisChat() {
        var chatBox = document.getElementById('ai-analysis-chat');
        var form = document.getElementById('ai-analysis-form');
        var input = document.getElementById('ai-analysis-input');
        var sendBtn = document.getElementById('ai-analysis-send');
        var csrfInput = document.getElementById('ai-analysis-csrf');

        if (!chatBox || !form || !input || !sendBtn || !csrfInput) {
            return;
        }

        var csrf = String(csrfInput.value || '');
        var reportContext = <?= $aiReportContextJson; ?>;
        var conversation = [];

        function pushBubble(role, text) {
            var div = document.createElement('div');
            div.className = 'ai-analysis-bubble ' + (role === 'user' ? 'user' : 'assistant');
            div.textContent = String(text || '');
            chatBox.appendChild(div);
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        function setBusy(isBusy) {
            sendBtn.disabled = isBusy;
            input.disabled = isBusy;
        }

        function askAnalysis(promptText) {
            conversation.push({ role: 'user', content: promptText });
            pushBubble('user', promptText);
            setBusy(true);

            fetch('api/ai_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    action: 'analyze_report',
                    csrf: csrf,
                    report_context: reportContext,
                    messages: conversation
                })
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data || data.ok !== true) {
                        throw new Error((data && data.message) ? data.message : 'Reponse IA indisponible.');
                    }

                    var reply = String(data.message || '').trim();
                    conversation.push({ role: 'assistant', content: reply });
                    pushBubble('assistant', reply);
                })
                .catch(function (error) {
                    if (window.Swal && typeof window.Swal.fire === 'function') {
                        window.Swal.fire({
                            icon: 'error',
                            title: 'Analyse IA indisponible',
                            text: error.message || 'Impossible de contacter l\'IA.',
                            customClass: getSwalBootstrapClasses(),
                            buttonsStyling: false,
                            didOpen: animateSwalIcon
                        });
                    }
                })
                .finally(function () {
                    setBusy(false);
                    input.focus();
                });
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var text = String(input.value || '').trim();
            if (text === '') {
                return;
            }
            input.value = '';
            askAnalysis(text);
        });

        pushBubble('assistant', 'Assistant IA pret. Posez une question sur cette alerte (resume, faiblesses, proposition d\'email de correction, etc.).');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initMiniMap();
            bindDecisionForms();
            bindAiAnalysisChat();
        });
    } else {
        initMiniMap();
        bindDecisionForms();
        bindAiAnalysisChat();
    }
})();
</script>
