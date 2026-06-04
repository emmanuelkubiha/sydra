<?php
/** @var array<int, array<string, mixed>> $rapportageAdminReports */
/** @var int|null $rapportageLatestSubmitted */

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
?>

<div class="card shadow-sm rounded-4 rapportage-list-card">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h1 class="mb-1">Tour de contrôle Lead GTMP</h1>
            <p class="text-muted mb-0">Supervision de tous les rapports avec priorisation des alertes soumises.</p>
        </div>
        <a href="?page=rapportage" class="btn btn-small">Retour au module Rapportage</a>
    </div>

    <p class="users-section-note mb-3">
        Notification sonore active: le système vérifie toutes les 60 secondes l'arrivée d'une nouvelle alerte au statut Soumis.
    </p>

    <table
        class="table table-users"
        id="rapportage-admin-table"
        data-last-submitted-id="<?= (int) ($rapportageLatestSubmitted ?? 0); ?>"
    >
        <thead>
        <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Organisation</th>
            <th>Localisation</th>
            <th>Incident</th>
            <th>Gravité</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (($rapportageAdminReports ?? []) as $report): ?>
            <?php $status = (string) ($report['workflow_status'] ?? 'Soumis'); ?>
            <tr>
                <td><?= htmlspecialchars((string) ($report['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars((string) ($report['report_type'] ?? 'FLASH'), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars((string) ($report['organization_name'] ?? 'Organisation inconnue'), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars((string) ($report['location_text'] ?? 'Non précisée'), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars((string) ($report['incident_label'] ?? 'Incident'), ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <span class="urgency-badge"><?= htmlspecialchars((string) ($report['urgency_level'] ?? 'Moyenne'), ENT_QUOTES, 'UTF-8'); ?></span>
                </td>
                <td>
                    <span class="<?= htmlspecialchars($statusClass($status), ENT_QUOTES, 'UTF-8'); ?>">
                        <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </td>
                <td>
                    <a href="?page=rapportage-voir&id=<?= (int) ($report['id'] ?? 0); ?>" class="btn btn-small">Traiter</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
