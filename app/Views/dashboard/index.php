<?php

declare(strict_types=1);

$stats = $stats ?? [];
$reports = $reports ?? [];

$cards = [
    ['label' => 'Total rapports', 'value' => (int) ($stats['total_reports'] ?? 0), 'icon' => 'bi-collection'],
    ['label' => 'FLASH', 'value' => (int) ($stats['total_flash'] ?? 0), 'icon' => 'bi-lightning-charge'],
    ['label' => 'NOTE', 'value' => (int) ($stats['total_note'] ?? 0), 'icon' => 'bi-journal-text'],
    ['label' => 'En attente revue', 'value' => (int) ($stats['pending_review'] ?? 0), 'icon' => 'bi-hourglass-split'],
    ['label' => 'Valides', 'value' => (int) ($stats['approved'] ?? 0), 'icon' => 'bi-check-circle'],
];
?>
<!-- Section: Entete dashboard -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 fw-bold mb-1">Tableau de bord Monitoring</h1>
        <p class="text-muted mb-0">Statistiques, tendances et alertes critiques.</p>
    </div>
    <a class="btn btn-primary" href="?r=reports/create"><i class="bi bi-plus-circle"></i> Nouveau rapport</a>
</div>

<!-- Section: Cartes KPI -->
<div class="row g-3 mb-3">
    <?php foreach ($cards as $card): ?>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card card-outline card-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="small text-muted mb-2"><?= htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="h3 mb-0 fw-bold"><?= $card['value']; ?></div>
                        </div>
                        <div class="text-primary"><i class="bi <?= htmlspecialchars($card['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Section: Graphiques et bloc de priorites -->
<div class="row g-3 mb-3">
    <div class="col-xl-7">
        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-bar-chart"></i> Tendance des rapports</h3></div>
            <div class="card-body" style="height: 280px;">
                <canvas
                    id="chart_dashboard"
                    data-flash="<?= (int) ($stats['total_flash'] ?? 0); ?>"
                    data-note="<?= (int) ($stats['total_note'] ?? 0); ?>"
                    data-pending="<?= (int) ($stats['pending_review'] ?? 0); ?>"
                    data-approved="<?= (int) ($stats['approved'] ?? 0); ?>"
                ></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card card-outline card-warning h-100">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-exclamation-triangle"></i> Priorites operationnelles</h3></div>
            <div class="card-body">
                <div class="small text-muted mb-2">Points d'attention immediats</div>
                <ul class="mb-0">
                    <li>Traiter rapidement les rapports FLASH en attente.</li>
                    <li>Verifier la coherence entre besoins et recommandations.</li>
                    <li>Assurer la qualite des localisations GPS pour la cartographie.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Section: Statistiques vulnerabilites -->
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card card-outline card-danger kpi-engaging">
            <div class="card-body">
                <div class="small text-muted">Enfants identifies</div>
                <div class="h4 mb-0 fw-bold"><?= (int) ($stats['total_vulnerable_children'] ?? 0); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-outline card-warning kpi-engaging">
            <div class="card-body">
                <div class="small text-muted">Personnes agees identifiees</div>
                <div class="h4 mb-0 fw-bold"><?= (int) ($stats['total_vulnerable_elderly'] ?? 0); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-outline card-info kpi-engaging">
            <div class="card-body">
                <div class="small text-muted">Personnes avec handicap</div>
                <div class="h4 mb-0 fw-bold"><?= (int) ($stats['total_vulnerable_disability'] ?? 0); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Section: Tableau des derniers rapports -->
<div class="card card-outline card-secondary">
    <div class="card-header"><h3 class="card-title"><i class="bi bi-table"></i> Derniers rapports</h3></div>
    <div class="table-responsive">
        <table id="table_rapports" class="table table-bordered table-striped align-middle mb-0">
            <thead>
            <tr>
                <th>Reference</th>
                <th>Type</th>
                <th>Organisation</th>
                <th>Zone</th>
                <th>Statut</th>
                <th>Date</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($reports as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['reference_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars($row['report_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars($row['organization_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars(trim(($row['province'] ?? '') . ' / ' . ($row['territory'] ?? '') . ' / ' . ($row['locality'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars($row['status_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string) $row['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
