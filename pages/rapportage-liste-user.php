<?php
/** @var array<int, array<string, mixed>> $rapportageUserReports */

$statusClass = static function (string $status): string {
    $normalized = strtolower(trim($status));
    $normalized = str_replace(['é', 'è', 'ê'], 'e', $normalized);

    if ($normalized === 'brouillon') {
        return 'status-badge status-draft';
    }
    if ($normalized === 'soumis') {
        return 'status-badge status-submitted';
    }
    if ($normalized === 'en revision') {
        return 'status-badge status-review';
    }
    if ($normalized === 'valide' || $normalized === 'publie') {
        return 'status-badge status-valid';
    }
    if ($normalized === 'rejete') {
        return 'status-badge status-rejected';
    }

    return 'status-badge status-neutral';
};

$canEditOrDelete = static function (string $status): bool {
    $normalized = strtolower(trim($status));
    $normalized = str_replace(['é', 'è', 'ê'], 'e', $normalized);
    return in_array($normalized, ['brouillon', 'soumis'], true);
};
?>

<div class="card shadow-sm rounded-4 rapportage-list-card">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h1 class="mb-1">Mes rapports</h1>
            <p class="text-muted mb-0">Suivi des rapports de votre organisation uniquement.</p>
        </div>
        <a href="?page=rapportage" class="btn btn-small">Retour au module Rapportage</a>
    </div>

    <table class="table table-users" id="rapportage-user-table">
        <thead>
        <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Localisation</th>
            <th>Incident</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (($rapportageUserReports ?? []) as $report): ?>
            <?php
            $status = (string) ($report['workflow_status'] ?? 'Soumis');
            $isEditable = $canEditOrDelete($status);
            ?>
            <tr>
                <td><?= htmlspecialchars((string) ($report['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars((string) ($report['report_type'] ?? 'FLASH'), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars((string) ($report['location_text'] ?? 'Non précisée'), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars((string) ($report['incident_label'] ?? 'Incident'), ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <span class="<?= htmlspecialchars($statusClass($status), ENT_QUOTES, 'UTF-8'); ?>">
                        <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </td>
                <td>
                    <div class="users-actions">
                        <a href="?page=rapportage-voir&id=<?= (int) ($report['id'] ?? 0); ?>" class="btn-icon btn-icon-soft" title="Voir">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="pages/reports/alerte_details.php?id=<?= (int) ($report['id'] ?? 0); ?>" target="_blank" class="btn-icon btn-icon-primary" title="Télécharger">
                            <i class="bi bi-download"></i>
                        </a>

                        <?php if ($isEditable): ?>
                            <a href="?page=rapportage-creer-wizar&report_id=<?= (int) ($report['id'] ?? 0); ?>" class="btn-icon btn-icon-warning" title="Modifier">
                                <i class="bi bi-pencil-square"></i>
                            </a>

                            <form method="post" action="?page=rapportage-liste-user" class="inline-form">
                                <input type="hidden" name="action" value="delete_org_report">
                                <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="report_id" value="<?= (int) ($report['id'] ?? 0); ?>">
                                <button type="submit" class="btn-icon btn-icon-danger" title="Supprimer">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
