<?php
/** @var array<int, array<string, mixed>> $rapportageMapAlerts */
/** @var array<int, array<string, mixed>> $rapportageRecentProvinceAlerts */

$mapJson = json_encode($rapportageMapAlerts ?? [], JSON_UNESCAPED_UNICODE);
if (!is_string($mapJson)) {
    $mapJson = '[]';
}
?>

<div class="card rapportage-launch-card shadow-sm rounded-4">
    <div class="rapportage-launch-header">
        <div>
            <h1 class="mb-2">Centre de rapportage SyDRA</h1>
            <p class="text-muted mb-0">Choisissez le mode de création le plus adapté pour documenter rapidement un incident terrain.</p>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-6">
            <a href="?page=rapportage-creer-AI" class="rapportage-mode-card mode-ai text-decoration-none">
                <span class="rapportage-mode-chip"><i class="bi bi-stars"></i> Recommandé</span>
                <h2>Mode Assistant IA</h2>
                <p>Laissez l'IA structurer votre alerte à partir des éléments clés.</p>
                <span class="btn btn-sm btn-primary mt-2">Démarrer en mode IA</span>
            </a>
        </div>
        <div class="col-lg-6">
            <a href="?page=rapportage-creer-wizar" class="rapportage-mode-card mode-manual text-decoration-none">
                <span class="rapportage-mode-chip"><i class="bi bi-ui-checks-grid"></i> Contrôle complet</span>
                <h2>Mode Manuel (Wizard)</h2>
                <p>Remplir le formulaire étape par étape avec validation guidée.</p>
                <span class="btn btn-sm btn-outline-primary mt-2">Démarrer le Wizard</span>
            </a>
        </div>
    </div>
</div>

<div class="card shadow-sm rounded-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <h2 class="mb-0">Incidents récents cartographiés</h2>
        <a href="?page=rapportage-admin-list" class="btn btn-small">Ouvrir la tour de contrôle</a>
    </div>
    <div id="decision-map" class="decision-map" data-alerts='<?= htmlspecialchars($mapJson, ENT_QUOTES, 'UTF-8'); ?>'></div>
</div>

<div class="card shadow-sm rounded-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <h2 class="mb-0">5 dernières alertes soumises</h2>
        <span class="text-muted small">Aperçu rapide pour suivi provincial</span>
    </div>

    <ul class="rapportage-mini-timeline list-unstyled mb-0">
        <?php if (($rapportageRecentProvinceAlerts ?? []) === []): ?>
            <li class="text-muted">Aucune alerte récente disponible.</li>
        <?php else: ?>
            <?php foreach ($rapportageRecentProvinceAlerts as $item): ?>
                <li>
                    <span class="dot"></span>
                    <div>
                        <a class="fw-semibold text-decoration-none" href="?page=rapportage-voir&id=<?= (int) ($item['id'] ?? 0); ?>">
                            <?= htmlspecialchars((string) ($item['incident_label'] ?? 'Incident'), ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                        <div class="small text-muted">
                            <?= htmlspecialchars((string) ($item['report_type'] ?? 'FLASH'), ENT_QUOTES, 'UTF-8'); ?>
                            · <?= htmlspecialchars((string) ($item['province'] ?? 'Province non renseignée'), ENT_QUOTES, 'UTF-8'); ?>
                            · <?= htmlspecialchars((string) ($item['workflow_status'] ?? 'Soumis'), ENT_QUOTES, 'UTF-8'); ?>
                            · <?= htmlspecialchars((string) ($item['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</div>
