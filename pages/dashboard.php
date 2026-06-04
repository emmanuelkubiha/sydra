<?php
/** @var array|null $authUser */
/** @var array<int, array<string, mixed>> $urgentAlerts */
/** @var array<int, array<string, mixed>> $mapAlerts */
/** @var array<string, array<int, int>|array<int, string>> $orgReportTrend */

$role = strtoupper((string) ($authUser['role'] ?? 'REPORTER'));
$isDecisionRole = in_array($role, ['ADMIN', 'CLUSTER_LEADER', 'LEAD_GTMP', 'GTMP_LEAD'], true);

$mapJson = json_encode($mapAlerts ?? [], JSON_UNESCAPED_UNICODE);
if (!is_string($mapJson)) {
    $mapJson = '[]';
}

$trendJson = json_encode($orgReportTrend ?? ['labels' => [], 'flash' => [], 'note' => []], JSON_UNESCAPED_UNICODE);
if (!is_string($trendJson)) {
    $trendJson = '{"labels":[],"flash":[],"note":[]}';
}

$flashTotal = array_sum((array) ($orgReportTrend['flash'] ?? []));
$noteTotal = array_sum((array) ($orgReportTrend['note'] ?? []));
$allTotal = $flashTotal + $noteTotal;
?>

<div class="card">
    <h1>Cartographie operationnelle</h1>
    <p class="hero-intro">Visualisation rapide des zones d'alerte pour aide a la decision.</p>
    <div id="decision-map" class="decision-map" data-alerts='<?= htmlspecialchars($mapJson, ENT_QUOTES, 'UTF-8'); ?>'></div>
</div>

<?php if ($isDecisionRole): ?>
<?php
$urgencyCounts = ['Faible' => 0, 'Moyenne' => 0, 'Elevee' => 0, 'Critique' => 0];
foreach (($urgentAlerts ?? []) as $alertRow) {
    $lvl = (string) ($alertRow['urgency_level'] ?? 'Moyenne');
    if (!isset($urgencyCounts[$lvl])) {
        $urgencyCounts[$lvl] = 0;
    }
    $urgencyCounts[$lvl]++;
}
?>
<div class="card">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <h2 class="mb-0">Pilotage strategique</h2>
        <a class="btn btn-small" href="?page=stats">Ouvrir Stats GTMP</a>
    </div>
    <p><small class="muted">Cette vue consolidee regroupe les alertes critiques. Les analyses avancees sont disponibles dans l'onglet Stats.</small></p>
</div>

<div class="card">
    <h2>Graphique des urgences en attente</h2>
    <canvas
        id="urgency-chart"
        data-faible="<?= (int) ($urgencyCounts['Faible'] ?? 0); ?>"
        data-moyenne="<?= (int) ($urgencyCounts['Moyenne'] ?? 0); ?>"
        data-elevee="<?= (int) ($urgencyCounts['Elevee'] ?? 0); ?>"
        data-critique="<?= (int) ($urgencyCounts['Critique'] ?? 0); ?>"
        height="110"
    ></canvas>
</div>

<div class="card">
    <h2>Alertes necessitant une action immediate</h2>
    <p><small class="muted">Vue Lead GTMP epuree: validation et diffusion en un clic.</small></p>

    <table class="table" id="lead-alert-table">
        <thead>
        <tr>
            <th>ID</th>
            <th>Lieu</th>
            <th>Type</th>
            <th>Urgence</th>
            <th>Soumis par</th>
            <th>Date</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (($urgentAlerts ?? []) as $alert): ?>
            <tr>
                <td><?= (int) ($alert['id'] ?? 0); ?></td>
                <td><?= htmlspecialchars((string) ($alert['location_text'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars((string) ($alert['report_type'] ?? 'FLASH'), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><span class="urgency-badge"><?= htmlspecialchars((string) ($alert['urgency_level'] ?? 'Moyenne'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                <td><?= htmlspecialchars((string) ($alert['submitted_by'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars((string) ($alert['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <form method="post" action="?page=tableau_de_bord" class="inline-form">
                        <input type="hidden" name="action" value="validate_and_diffuse">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="report_id" value="<?= (int) ($alert['id'] ?? 0); ?>">
                        <button type="submit" class="btn btn-small">Valider et diffuser</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="card">
    <h1>Activité de votre organisation sur 6 mois</h1>
    <p class="hero-intro">Suivi mensuel de vos rapports Note et Flash pour piloter les actions terrain.</p>

    <div class="stats-inline-grid">
        <div class="stats-inline-card">
            <span>Total rapports</span>
            <strong><?= (int) $allTotal; ?></strong>
        </div>
        <div class="stats-inline-card">
            <span>FLASH</span>
            <strong><?= (int) $flashTotal; ?></strong>
        </div>
        <div class="stats-inline-card">
            <span>NOTE</span>
            <strong><?= (int) $noteTotal; ?></strong>
        </div>
    </div>

    <canvas id="org-reports-trend" height="110" data-trend='<?= htmlspecialchars($trendJson, ENT_QUOTES, 'UTF-8'); ?>'></canvas>

    <div class="mt-3">
        <a class="btn" href="?page=rapport_creer">Créer un rapport</a>
    </div>
</div>
<?php endif; ?>
