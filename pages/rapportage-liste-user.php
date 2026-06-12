<?php
/**
 * Page: rapportage-liste-user (alias rapportage-mes-alertes)
 *
 * Rôle du fichier:
 * - Afficher la liste des alertes selon le rôle connecté.
 * - Reporter: voit ses alertes uniquement.
 * - Lead/Admin: vue coordination sur toutes les alertes.
 * - Proposer des actions contextualisées (voir, télécharger, modifier, supprimer).
 */

/** @var array<int, array<string, mixed>> $rapportageUserReports */
/** @var array<string, mixed>|null $authUser */

// Contexte de vue: permissions et identité utilisateur connecté.
$isLeadOrAdminView = is_array($authUser) && (is_lead_gtmp($authUser) || is_admin($authUser));
$currentUserId = is_array($authUser) ? (int) ($authUser['id'] ?? 0) : 0;

// Mapping visuel des statuts vers des classes de badge.
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

// Règle métier: seuls Brouillon/Soumis restent modifiables par le propriétaire.
$canEditOrDelete = static function (string $status): bool {
    $normalized = strtolower(trim($status));
    $normalized = str_replace(['é', 'è', 'ê'], 'e', $normalized);
    return in_array($normalized, ['brouillon', 'soumis'], true);
};
?>

<div class="card shadow-sm rounded-4 rapportage-list-card">
    <!-- En-tête de la page avec message de portée selon le rôle. -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h1 class="mb-1">Mes rapports</h1>
            <p class="text-muted mb-0">
                <?= $isLeadOrAdminView
                    ? 'Vue coordination: les brouillons restent privés et visibles uniquement par leur auteur.'
                    : 'Suivi des rapports de votre organisation uniquement.'; ?>
            </p>
        </div>
        <a href="?page=rapportage" class="btn btn-small">Retour au module Rapportage</a>
    </div>

    <!-- Barre de filtres: recherche plein texte + filtres métier + plage de dates. -->
    <div class="border rounded-3 p-3 bg-body-tertiary mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h6 mb-0">Afficher et Rechercher</h2>
            <div class="d-flex align-items-center gap-2">
                <span class="small text-muted">Filtrage local sans rechargement</span>
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
                <label for="rapportage-search-input" class="form-label small mb-1">Recherche</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input id="rapportage-search-input" type="search" class="form-control" placeholder="Incident, lieu, organisation, statut...">
                </div>
            </div>

            <div class="col-xl-2 col-md-4">
                <label for="rapportage-filter-status" class="form-label small mb-1">Statut</label>
                <select id="rapportage-filter-status" class="form-select">
                    <option value="">Tous les statuts</option>
                    <option value="brouillon">Brouillon</option>
                    <option value="soumis">Soumis</option>
                    <option value="en revision">En révision</option>
                    <option value="approuve">Approuvé/Validé</option>
                    <option value="rejete">Rejeté</option>
                </select>
            </div>

            <div class="col-xl-2 col-md-4">
                <label for="rapportage-filter-type" class="form-label small mb-1">Type</label>
                <select id="rapportage-filter-type" class="form-select">
                    <option value="">Tous les types</option>
                    <option value="flash">FLASH</option>
                    <option value="note">NOTE</option>
                </select>
            </div>

            <div class="col-xl-2 col-md-4">
                <label for="rapportage-filter-urgency" class="form-label small mb-1">Urgence</label>
                <select id="rapportage-filter-urgency" class="form-select">
                    <option value="">Toutes</option>
                    <option value="critique">Critique</option>
                    <option value="elevee">Élevée</option>
                    <option value="moyenne">Moyenne</option>
                    <option value="faible">Faible</option>
                </select>
            </div>

            <div class="col-xl-1 col-md-6">
                <label for="rapportage-filter-from" class="form-label small mb-1">Du</label>
                <input id="rapportage-filter-from" type="date" class="form-control">
            </div>

            <div class="col-xl-1 col-md-6">
                <label for="rapportage-filter-to" class="form-label small mb-1">Au</label>
                <input id="rapportage-filter-to" type="date" class="form-control">
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-2 border-top">
            <?php if ($isLeadOrAdminView): ?>
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="rapportage-filter-mine-only">
                    <label class="form-check-label small" for="rapportage-filter-mine-only">Mes alertes uniquement</label>
                </div>
            <?php endif; ?>

            <button type="button" id="rapportage-filter-reset" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Réinitialiser
            </button>

            <span class="ms-auto small text-muted">
                <strong id="rapportage-visible-count">0</strong>
                résultat(s) affiché(s)
            </span>
        </div>
    </div>

    <!-- Tableau principal des alertes avec métadonnées data-* pour le filtrage client. -->
    <div class="table-responsive">
    <table class="table table-users" id="rapportage-user-table">
        <thead>
        <tr>
            <th>Date</th>
            <?php if ($isLeadOrAdminView): ?>
            <th>Organisation</th>
            <?php endif; ?>
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
            $ownerUserId = (int) ($report['owner_user_id'] ?? 0);
            $isOwner = $ownerUserId > 0 && $ownerUserId === $currentUserId;
            $isEditable = $isOwner && $canEditOrDelete($status);
            $type = (string) ($report['report_type'] ?? 'FLASH');
            $urgency = (string) ($report['urgency_level'] ?? 'Moyenne');
            $location = trim((string) ($report['location_text'] ?? ''));
            if ($location === '') {
                $location = 'Non précisée';
            }
            $incident = (string) ($report['incident_label'] ?? 'Incident');
            $organization = (string) ($report['organization_name'] ?? 'Organisation');
            $createdAt = (string) ($report['created_at'] ?? '');

            $searchBlob = strtolower(trim(implode(' ', [
                $type,
                $location,
                $incident,
                $status,
                $urgency,
                $organization,
            ])));
            ?>
            <tr data-type="<?= htmlspecialchars(strtolower($type), ENT_QUOTES, 'UTF-8'); ?>"
                data-status="<?= htmlspecialchars(strtolower(str_replace(['é', 'è', 'ê'], 'e', $status)), ENT_QUOTES, 'UTF-8'); ?>"
                data-urgency="<?= htmlspecialchars(strtolower(str_replace(['é', 'è', 'ê'], 'e', $urgency)), ENT_QUOTES, 'UTF-8'); ?>"
                data-date="<?= htmlspecialchars(substr($createdAt, 0, 10), ENT_QUOTES, 'UTF-8'); ?>"
                data-owner-id="<?= $ownerUserId; ?>"
                data-search="<?= htmlspecialchars($searchBlob, ENT_QUOTES, 'UTF-8'); ?>">
                <td><?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8'); ?></td>
                <?php if ($isLeadOrAdminView): ?>
                <td><?= htmlspecialchars($organization, ENT_QUOTES, 'UTF-8'); ?></td>
                <?php endif; ?>
                <td><?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars($location, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars($incident, ENT_QUOTES, 'UTF-8'); ?></td>
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
    </div><!-- /table-responsive -->

    <p id="rapportage-empty-state" class="text-muted mt-3 mb-0 d-none">
        Aucun résultat ne correspond aux filtres sélectionnés.
    </p>
</div>

<script>
(function () {
    // Filtrage client du tableau pour une expérience rapide sans rechargement.
    var table = document.getElementById('rapportage-user-table');
    if (!table) {
        return;
    }

    var tbody = table.querySelector('tbody');
    if (!tbody) {
        return;
    }

    var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
    var searchInput = document.getElementById('rapportage-search-input');
    var statusSelect = document.getElementById('rapportage-filter-status');
    var typeSelect = document.getElementById('rapportage-filter-type');
    var urgencySelect = document.getElementById('rapportage-filter-urgency');
    var dateFrom = document.getElementById('rapportage-filter-from');
    var dateTo = document.getElementById('rapportage-filter-to');
    var mineOnly = document.getElementById('rapportage-filter-mine-only');
    var resetBtn = document.getElementById('rapportage-filter-reset');
    var visibleCount = document.getElementById('rapportage-visible-count');
    var emptyState = document.getElementById('rapportage-empty-state');
    var currentUserId = <?= (int) $currentUserId; ?>;
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
        var onlyMine = !!(mineOnly && mineOnly.checked);

        var shown = 0;

        rows.forEach(function (row) {
            var rowSearch = normalize(row.getAttribute('data-search'));
            var rowStatus = normalize(row.getAttribute('data-status'));
            var rowType = normalize(row.getAttribute('data-type'));
            var rowUrgency = normalize(row.getAttribute('data-urgency'));
            var rowDate = String(row.getAttribute('data-date') || '').trim();
            var rowOwnerId = Number(row.getAttribute('data-owner-id') || 0);

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
            if (ok && onlyMine && rowOwnerId > 0 && rowOwnerId !== currentUserId) {
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
            params.set('scope', 'user');
            params.set('search', String(searchInput ? searchInput.value : '').trim());
            params.set('status', String(statusSelect ? statusSelect.value : '').trim());
            params.set('type', String(typeSelect ? typeSelect.value : '').trim());
            params.set('urgency', String(urgencySelect ? urgencySelect.value : '').trim());
            params.set('date_from', String(dateFrom ? dateFrom.value : '').trim());
            params.set('date_to', String(dateTo ? dateTo.value : '').trim());
            params.set('mine_only', mineOnly && mineOnly.checked ? '1' : '0');
            link.setAttribute('href', 'api/export_reports.php?' + params.toString());
        });
    }

    [searchInput, statusSelect, typeSelect, urgencySelect, dateFrom, dateTo, mineOnly].forEach(function (el) {
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
            if (mineOnly) { mineOnly.checked = false; }
            applyFilters();
        });
    }

    applyFilters();
})();
</script>
