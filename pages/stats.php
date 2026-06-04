<?php
/** @var array<string, array<int, int>|array<int, string>> $statsTopOrganizations */
/** @var array<string, array<int, int>|array<int, string>> $statsGlobalTrend */
/** @var array<string, array<int, int>|array<int, string>> $statsUrgencyDistribution */

$topJson = json_encode($statsTopOrganizations ?? ['labels' => [], 'values' => []], JSON_UNESCAPED_UNICODE);
if (!is_string($topJson)) {
    $topJson = '{"labels":[],"values":[]}';
}

$globalJson = json_encode($statsGlobalTrend ?? ['labels' => [], 'totals' => [], 'flash' => [], 'note' => []], JSON_UNESCAPED_UNICODE);
if (!is_string($globalJson)) {
    $globalJson = '{"labels":[],"totals":[],"flash":[],"note":[]}';
}

$urgencyJson = json_encode($statsUrgencyDistribution ?? ['labels' => [], 'values' => []], JSON_UNESCAPED_UNICODE);
if (!is_string($urgencyJson)) {
    $urgencyJson = '{"labels":[],"values":[]}';
}
?>

<div class="card">
    <h1>Statistiques stratégiques GTMP</h1>
    <p class="hero-intro">Tableau de pilotage pour la coordination inter-organisationnelle et la prise de décision rapide.</p>
</div>

<div class="stats-inline-grid">
    <div class="card stats-card">
        <h2>Organisations rapportrices les plus actives</h2>
        <canvas id="top-organizations-chart" height="130" data-chart='<?= htmlspecialchars($topJson, ENT_QUOTES, 'UTF-8'); ?>'></canvas>
    </div>

    <div class="card stats-card">
        <h2>Répartition par niveau d'alerte</h2>
        <canvas id="urgency-distribution-chart" height="130" data-chart='<?= htmlspecialchars($urgencyJson, ENT_QUOTES, 'UTF-8'); ?>'></canvas>
    </div>
</div>

<div class="card stats-card">
    <h2>Évolution globale des rapports (effet nuage)</h2>
    <canvas id="global-evolution-chart" height="110" data-chart='<?= htmlspecialchars($globalJson, ENT_QUOTES, 'UTF-8'); ?>'></canvas>
</div>
