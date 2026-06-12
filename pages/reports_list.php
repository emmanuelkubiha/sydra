<?php
/** @var array<int, array<string, mixed>> $reports */
$reportHeaderTitle = 'Liste des rapports';
require __DIR__ . '/partials/report_header.php';
?>
<div class="card">
    <h1>Liste des rapports</h1>
    <div class="table-responsive">
    <table class="table">
        <thead>
        <tr>
            <th>ID</th>
            <th>Titre</th>
            <th>Type</th>
            <th>Lieu</th>
            <th>Auteur</th>
            <th>Date</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($reports as $row): ?>
            <tr>
                <td><?= (int) $row['id']; ?></td>
                <td><?= htmlspecialchars((string) $row['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars((string) $row['report_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars((string) ($row['location_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars((string) $row['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars((string) $row['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div><!-- /table-responsive -->
</div>
