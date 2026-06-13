<?php
/** @var array<int, array<string, mixed>> $rapportageAdminReports */
/** @var int|null $rapportageLatestSubmitted */

$adminRoles = ['ADMIN', 'GTMP_LEAD', 'GTMP_COLEAD', 'CLUSTER_LEADER', 'CLUSTER_PROTECTION'];
if (!isset($_SESSION['role_code']) || !in_array($_SESSION['role_code'], $adminRoles)) {
?>
    <div class="d-flex align-items-center justify-content-center min-vh-100" style="background-color: #f8f9fa;">
        <div class="text-center p-5 bg-white" style="border-radius: 16px; box-shadow: 0 15px 40px rgba(0,0,0,0.08); max-width: 450px;">
            <div class="mb-4">
                <i class="fa-solid fa-shield-halved text-primary" style="font-size: 4rem; opacity: 0.8;"></i>
            </div>
            <h3 style="font-family: 'Inter', 'Poppins', sans-serif; font-weight: 600; color: #1f2937;">Accès Restreint</h3>
            <p class="text-muted mt-3 mb-4" style="font-size: 0.95rem;">
                Désolé, cette section est réservée à l'équipe de coordination du GTMP. Si vous pensez qu'il s'agit d'une erreur, veuillez contacter votre administrateur.
            </p>
            <a href="?page=dashboard" class="btn btn-primary rounded-pill px-4 py-2" style="font-weight: 500;">
                <i class="fa-solid fa-arrow-left me-2"></i> Retour à l'accueil
            </a>
        </div>
    </div>
<?php
    exit;
}

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

    <div class="border rounded-3 p-3 bg-body-tertiary mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h6 mb-0">Afficher et Rechercher</h2>
            <div class="d-flex align-items-center gap-2">
                <span class="small text-muted">Vue coordination Lead/Admin</span>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-download me-1"></i>Exporter la liste
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li>
                            <a class="dropdown-item js-export-link" data-format="pdf" href="api/export_reports.php?format=pdf&csrf=<?= urlencode((string) csrf_token()); ?>">
                                <i class="fa-regular fa-file-pdf me-2 text-danger"></i>Exporter en PDF
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item js-export-link" data-format="xlsx" href="api/export_reports.php?format=xlsx&csrf=<?= urlencode((string) csrf_token()); ?>">
                                <i class="fa-regular fa-file-excel me-2 text-success"></i>Exporter en Excel (.xlsx)
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item js-export-link" data-format="csv" href="api/export_reports.php?format=csv&csrf=<?= urlencode((string) csrf_token()); ?>">
                                <i class="fa-regular fa-file-lines me-2 text-primary"></i>Exporter en CSV
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row g-3 align-items-end">
            <div class="col-xl-4 col-lg-12">
                <label for="rapportage-admin-search" class="form-label small mb-1">Recherche</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input id="rapportage-admin-search" type="search" class="form-control" placeholder="Incident, organisation, lieu, statut...">
                </div>
            </div>

            <div class="col-xl-2 col-md-4">
                <label for="rapportage-admin-filter-status" class="form-label small mb-1">Statut</label>
                <select id="rapportage-admin-filter-status" class="form-select">
                    <option value="">Tous les statuts</option>
                    <option value="soumis">Soumis</option>
                    <option value="en revision">En révision</option>
                    <option value="approuve">Approuvé/Validé</option>
                    <option value="rejete">Rejeté</option>
                </select>
            </div>

            <div class="col-xl-2 col-md-4">
                <label for="rapportage-admin-filter-type" class="form-label small mb-1">Type</label>
                <select id="rapportage-admin-filter-type" class="form-select">
                    <option value="">Tous les types</option>
                    <option value="flash">FLASH</option>
                    <option value="note">NOTE</option>
                </select>
            </div>

            <div class="col-xl-2 col-md-4">
                <label for="rapportage-admin-filter-urgency" class="form-label small mb-1">Gravité</label>
                <select id="rapportage-admin-filter-urgency" class="form-select">
                    <option value="">Toutes</option>
                    <option value="critique">Critique</option>
                    <option value="elevee">Élevée</option>
                    <option value="moyenne">Moyenne</option>
                    <option value="faible">Faible</option>
                </select>
            </div>

            <div class="col-xl-1 col-md-6">
                <label for="rapportage-admin-filter-from" class="form-label small mb-1">Du</label>
                <input id="rapportage-admin-filter-from" type="date" class="form-control">
            </div>

            <div class="col-xl-1 col-md-6">
                <label for="rapportage-admin-filter-to" class="form-label small mb-1">Au</label>
                <input id="rapportage-admin-filter-to" type="date" class="form-control">
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-2 border-top">
            <button type="button" id="rapportage-admin-reset" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Réinitialiser
            </button>

            <span class="ms-auto small text-muted">
                <strong id="rapportage-admin-visible-count">0</strong>
                résultat(s) affiché(s)
            </span>
        </div>
    </div>

    <div class="table-responsive">
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
            <?php
            $status = (string) ($report['workflow_status'] ?? 'Soumis');
            $type = (string) ($report['report_type'] ?? 'FLASH');
            $organization = (string) ($report['organization_name'] ?? 'Organisation inconnue');
            $location = (string) ($report['location_text'] ?? 'Non précisée');
            $incident = (string) ($report['incident_label'] ?? 'Incident');
            $urgency = (string) ($report['urgency_level'] ?? 'Moyenne');
            $createdAt = (string) ($report['created_at'] ?? '');

            $searchBlob = strtolower(trim(implode(' ', [
                $type,
                $organization,
                $location,
                $incident,
                $urgency,
                $status,
            ])));
            ?>
            <tr data-type="<?= htmlspecialchars(strtolower($type), ENT_QUOTES, 'UTF-8'); ?>"
                data-status="<?= htmlspecialchars(strtolower(str_replace(['é', 'è', 'ê'], 'e', $status)), ENT_QUOTES, 'UTF-8'); ?>"
                data-urgency="<?= htmlspecialchars(strtolower(str_replace(['é', 'è', 'ê'], 'e', $urgency)), ENT_QUOTES, 'UTF-8'); ?>"
                data-date="<?= htmlspecialchars(substr($createdAt, 0, 10), ENT_QUOTES, 'UTF-8'); ?>"
                data-search="<?= htmlspecialchars($searchBlob, ENT_QUOTES, 'UTF-8'); ?>">
                <td><?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>
                    <?php if (isset($report['is_ai_generated']) && $report['is_ai_generated'] == 1): ?>
                        <span class="badge rounded-pill ms-2" style="background-color: #f3e8ff; color: #7e22ce; border: 1px solid #d8b4fe; font-size: 0.75rem;">
                            <i class="fa-solid fa-wand-magic-sparkles me-1"></i> IA
                        </span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($organization, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars($location, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars($incident, ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <span class="urgency-badge"><?= htmlspecialchars($urgency, ENT_QUOTES, 'UTF-8'); ?></span>
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
    </div><!-- /table-responsive -->

    <p id="rapportage-admin-empty-state" class="text-muted mt-3 mb-0 d-none">
        Aucun résultat ne correspond aux filtres sélectionnés.
    </p>
</div>

<script>
(function () {
    var table = document.getElementById('rapportage-admin-table');
    if (!table) {
        return;
    }

    var tbody = table.querySelector('tbody');
    if (!tbody) {
        return;
    }

    var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
    var searchInput = document.getElementById('rapportage-admin-search');
    var statusSelect = document.getElementById('rapportage-admin-filter-status');
    var typeSelect = document.getElementById('rapportage-admin-filter-type');
    var urgencySelect = document.getElementById('rapportage-admin-filter-urgency');
    var dateFrom = document.getElementById('rapportage-admin-filter-from');
    var dateTo = document.getElementById('rapportage-admin-filter-to');
    var resetBtn = document.getElementById('rapportage-admin-reset');
    var visibleCount = document.getElementById('rapportage-admin-visible-count');
    var emptyState = document.getElementById('rapportage-admin-empty-state');
    var exportLinks = Array.prototype.slice.call(document.querySelectorAll('.js-export-link'));

    function normalize(str) {
        return String(str || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function applyFilters() {
        var search = normalize(searchInput ? searchInput.value : '');
        var status = normalize(statusSelect ? statusSelect.value : '');
        var type = normalize(typeSelect ? typeSelect.value : '');
        var urgency = normalize(urgencySelect ? urgencySelect.value : '');
        var from = (dateFrom && dateFrom.value) ? dateFrom.value : '';
        var to = (dateTo && dateTo.value) ? dateTo.value : '';

        var shown = 0;

        rows.forEach(function (row) {
            var rowSearch = normalize(row.getAttribute('data-search'));
            var rowStatus = normalize(row.getAttribute('data-status'));
            var rowType = normalize(row.getAttribute('data-type'));
            var rowUrgency = normalize(row.getAttribute('data-urgency'));
            var rowDate = String(row.getAttribute('data-date') || '').trim();

            var ok = true;

            if (search !== '' && rowSearch.indexOf(search) === -1) {
                ok = false;
            }
            if (ok && status !== '' && rowStatus.indexOf(status) === -1) {
                ok = false;
            }
            if (ok && type !== '' && rowType !== type) {
                ok = false;
            }
            if (ok && urgency !== '' && rowUrgency.indexOf(urgency) === -1) {
                ok = false;
            }
            if (ok && from !== '' && rowDate !== '' && rowDate < from) {
                ok = false;
            }
            if (ok && to !== '' && rowDate !== '' && rowDate > to) {
                ok = false;
            }

            row.classList.toggle('d-none', !ok);
            if (ok) {
                shown += 1;
            }
        });

        if (visibleCount) {
            visibleCount.textContent = String(shown);
        }
        if (emptyState) {
            emptyState.classList.toggle('d-none', shown !== 0);
        }

        exportLinks.forEach(function (link) {
            var format = String(link.getAttribute('data-format') || 'csv');
            var params = new URLSearchParams();
            params.set('format', format);
            params.set('csrf', '<?= urlencode((string) csrf_token()); ?>');
            params.set('scope', 'admin');
            params.set('search', String(searchInput ? searchInput.value : '').trim());
            params.set('status', String(statusSelect ? statusSelect.value : '').trim());
            params.set('type', String(typeSelect ? typeSelect.value : '').trim());
            params.set('urgency', String(urgencySelect ? urgencySelect.value : '').trim());
            params.set('date_from', String(dateFrom ? dateFrom.value : '').trim());
            params.set('date_to', String(dateTo ? dateTo.value : '').trim());
            link.setAttribute('href', 'api/export_reports.php?' + params.toString());
        });
    }

    [searchInput, statusSelect, typeSelect, urgencySelect, dateFrom, dateTo].forEach(function (el) {
        if (!el) {
            return;
        }
        el.addEventListener('input', applyFilters);
        el.addEventListener('change', applyFilters);
    });

    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            if (searchInput) { searchInput.value = ''; }
            if (statusSelect) { statusSelect.value = ''; }
            if (typeSelect) { typeSelect.value = ''; }
            if (urgencySelect) { urgencySelect.value = ''; }
            if (dateFrom) { dateFrom.value = ''; }
            if (dateTo) { dateTo.value = ''; }
            applyFilters();
        });
    }

    applyFilters();
})();
</script>
