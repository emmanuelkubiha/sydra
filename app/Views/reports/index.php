<?php

declare(strict_types=1);

$reports = $reports ?? [];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 fw-bold mb-1">Rapports</h1>
        <p class="text-muted mb-0">Liste des rapports enregistres.</p>
    </div>
    <a href="?r=reports/create" class="btn btn-sydra">Nouveau</a>
</div>

<div class="card card-soft border-0">
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
