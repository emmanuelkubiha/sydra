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
} elseif ($statusNormalized === 'en revision') {
    $statusBadgeClass = 'status-badge status-review';
} elseif ($statusNormalized === 'valide' || $statusNormalized === 'publie') {
    $statusBadgeClass = 'status-badge status-valid';
} elseif ($statusNormalized === 'rejete') {
    $statusBadgeClass = 'status-badge status-rejected';
}
?>

<div class="card shadow-sm rounded-4 mb-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1 class="mb-1">Détail du rapport #<?= (int) ($rapportageView['id'] ?? 0); ?></h1>
            <p class="text-muted mb-0">
                <?= htmlspecialchars((string) ($rapportageView['organization_name'] ?? 'Organisation inconnue'), ENT_QUOTES, 'UTF-8'); ?>
                •
                <span class="<?= htmlspecialchars($statusBadgeClass, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></span>
            </p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button type="button" class="btn btn-small js-print-report"><i class="bi bi-printer"></i> Imprimer</button>
            <a href="pages/reports/alerte_details.php?id=<?= (int) ($rapportageView['id'] ?? 0); ?>" target="_blank" class="btn btn-small"><i class="bi bi-filetype-pdf"></i> Exporter PDF</a>
            <button type="button" class="btn btn-small js-export-report-excel" data-table-id="rapportage-detail-export-table"><i class="bi bi-file-earmark-spreadsheet"></i> Exporter Excel</button>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm rounded-4 rapportage-detail-main">
            <h2 class="mb-3">Informations principales</h2>
            <div class="rapportage-detail-grid">
                <div>
                    <strong>Type</strong>
                    <p><?= htmlspecialchars((string) ($rapportageView['report_type'] ?? 'FLASH'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div>
                    <strong>Niveau de gravité</strong>
                    <p><?= htmlspecialchars((string) ($rapportageView['urgency_level'] ?? 'Moyenne'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div>
                    <strong>Province</strong>
                    <p><?= htmlspecialchars((string) ($rapportageView['province'] ?? 'Non précisée'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div>
                    <strong>Localisation</strong>
                    <p><?= htmlspecialchars((string) ($rapportageView['location_text'] ?? 'Non précisée'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div>
                    <strong>Coordonnées GPS</strong>
                    <p>
                        <?= htmlspecialchars((string) ($rapportageView['gps_lat'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>,
                        <?= htmlspecialchars((string) ($rapportageView['gps_lng'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                </div>
                <div>
                    <strong>Victimes</strong>
                    <p><?= htmlspecialchars((string) ($rapportageView['victims_count'] ?? 'Non renseigné'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>

            <hr>

            <div class="mb-3">
                <strong>Incident</strong>
                <p class="mb-1"><?= htmlspecialchars((string) ($rapportageView['incident_label'] ?? $rapportageView['title'] ?? 'Incident'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

            <div class="mb-3">
                <strong>Description</strong>
                <p class="mb-1"><?= nl2br(htmlspecialchars((string) ($rapportageView['content'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></p>
            </div>

            <div class="mb-3">
                <strong>Analyse</strong>
                <p class="mb-1"><?= nl2br(htmlspecialchars((string) ($rapportageView['analysis_text'] ?? 'Aucune analyse complémentaire.'), ENT_QUOTES, 'UTF-8')); ?></p>
            </div>

            <div>
                <strong>Notes additionnelles</strong>
                <p class="mb-0"><?= nl2br(htmlspecialchars((string) ($rapportageView['additional_notes'] ?? 'Aucune note additionnelle.'), ENT_QUOTES, 'UTF-8')); ?></p>
            </div>

            <table class="d-none" id="rapportage-detail-export-table">
                <tbody>
                <tr><td>ID</td><td><?= (int) ($rapportageView['id'] ?? 0); ?></td></tr>
                <tr><td>Organisation</td><td><?= htmlspecialchars((string) ($rapportageView['organization_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                <tr><td>Statut</td><td><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></td></tr>
                <tr><td>Type</td><td><?= htmlspecialchars((string) ($rapportageView['report_type'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                <tr><td>Localisation</td><td><?= htmlspecialchars((string) ($rapportageView['location_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                <tr><td>Description</td><td><?= htmlspecialchars((string) ($rapportageView['content'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                </tbody>
            </table>
        </div>

        <div class="card shadow-sm rounded-4 mt-3">
            <h2 class="mb-3">Pièces jointes</h2>
            <?php if (($rapportageAttachments ?? []) === []): ?>
                <p class="text-muted mb-0">Aucune pièce jointe pour ce rapport.</p>
            <?php else: ?>
                <div class="rapportage-attachments-grid">
                    <?php foreach ($rapportageAttachments as $attachment): ?>
                        <a
                            href="<?= htmlspecialchars((string) ($attachment['storage_path'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>"
                            class="rapportage-attachment-item"
                            target="_blank"
                        >
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
        <div class="card shadow-sm rounded-4 rapportage-timeline-card">
            <h2 class="mb-3">Timeline de traitement</h2>
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
                                <small class="text-muted">
                                    <?= htmlspecialchars((string) ($event['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                    •
                                    <?= htmlspecialchars((string) ($event['actor_name'] ?? 'Système'), ENT_QUOTES, 'UTF-8'); ?>
                                </small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($isDecisionRole): ?>
<div class="card shadow-sm rounded-4 mt-3 rapportage-decision-panel">
    <h2 class="mb-3">Zone de décision Lead GTMP</h2>
    <div class="d-flex flex-wrap gap-2">
        <form method="post" action="?page=rapportage-voir&id=<?= (int) ($rapportageView['id'] ?? 0); ?>" class="inline-form">
            <input type="hidden" name="action" value="lead_report_decision">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="report_id" value="<?= (int) ($rapportageView['id'] ?? 0); ?>">
            <input type="hidden" name="decision" value="publish">
            <button type="submit" class="btn rapportage-btn-success">Valider et Publier</button>
        </form>

        <button type="button" class="btn rapportage-btn-info" data-bs-toggle="modal" data-bs-target="#decisionInfoModal">
            Demander des informations supplémentaires
        </button>

        <form method="post" action="?page=rapportage-voir&id=<?= (int) ($rapportageView['id'] ?? 0); ?>" class="inline-form">
            <input type="hidden" name="action" value="lead_report_decision">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="report_id" value="<?= (int) ($rapportageView['id'] ?? 0); ?>">
            <input type="hidden" name="decision" value="reject">
            <button type="submit" class="btn rapportage-btn-danger">Rejeter</button>
        </form>
    </div>
</div>

<div class="modal fade" id="decisionInfoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="?page=rapportage-voir&id=<?= (int) ($rapportageView['id'] ?? 0); ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Demande d'informations supplémentaires</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="lead_report_decision">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="report_id" value="<?= (int) ($rapportageView['id'] ?? 0); ?>">
                    <input type="hidden" name="decision" value="request_info">

                    <label for="decision_message">Message à envoyer à l'organisation</label>
                    <textarea id="decision_message" name="decision_message" class="form-control" rows="6" placeholder="Précisez les informations attendues..." required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn rapportage-btn-info">Envoyer la demande</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
