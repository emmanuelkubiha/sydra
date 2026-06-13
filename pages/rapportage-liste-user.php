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
$statusBadgeHtml = static function (string $status): string {
    $normalized = strtolower(trim($status));
    $normalized = str_replace(['é', 'è', 'ê'], 'e', $normalized);

    if (in_array($normalized, ['brouillon', 'draft'], true)) {
        return '<span class="badge-status badge-status-draft"><span class="status-dot"></span> ' . htmlspecialchars($status) . '</span>';
    }
    if (in_array($normalized, ['soumis', 'submitted', 'en revision', 'en revue', 'under_review', 'under review'], true)) {
        return '<span class="badge-status badge-status-pending"><span class="status-dot"></span> ' . htmlspecialchars($status) . '</span>';
    }
    if (in_array($normalized, ['valide', 'validee', 'publie', 'publiee', 'approuve', 'approved'], true)) {
        return '<span class="badge-status badge-status-approved"><span class="status-dot"></span> ' . htmlspecialchars($status) . '</span>';
    }
    if (in_array($normalized, ['rejete', 'rejected'], true)) {
        return '<span class="badge-status badge-status-rejected"><span class="status-dot"></span> ' . htmlspecialchars($status) . '</span>';
    }

    return '<span class="badge-status bg-light text-dark"><span class="status-dot bg-secondary"></span> ' . htmlspecialchars($status) . '</span>';
};

// Règle métier: seuls Brouillon/Soumis restent modifiables par le propriétaire.
$canEditOrDelete = static function (string $status): bool {
    $normalized = strtolower(trim($status));
    $normalized = str_replace(['é', 'è', 'ê'], 'e', $normalized);
    return in_array($normalized, ['brouillon', 'soumis'], true);
};

// Mission 1: Calcul des Statistiques Personnelles
$totalAlertes = 0;
$totalBrouillons = 0;
$totalEnAttente = 0;
$totalValidees = 0;

if (isset($rapportageUserReports) && is_array($rapportageUserReports)) {
    foreach ($rapportageUserReports as $row) {
        $st = strtolower(str_replace(['é', 'è', 'ê'], 'e', trim($row['workflow_status'] ?? '')));
        $totalAlertes++;
        
        if (in_array($st, ['brouillon', 'draft'], true)) {
            $totalBrouillons++;
        } elseif (in_array($st, ['soumis', 'submitted', 'en revision', 'en revue', 'under_review', 'under review', 'en cours', 'review'], true)) {
            $totalEnAttente++;
        } elseif (in_array($st, ['approuve', 'approved', 'valide', 'validee', 'publie', 'publiee'], true)) {
            $totalValidees++;
        }
    }
}
?>

<style>
/* Style global et polices */
.user-list-container {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: #1e293b;
    padding-bottom: 2rem;
}

/* En-tête de la page */
.page-title {
    font-size: 1.75rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.5px;
}
.page-subtitle {
    font-size: 0.95rem;
    color: #64748b;
}

/* Boutons premiums */
.btn-premium-primary {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #ffffff;
    border: none;
    border-radius: 12px;
    padding: 10px 24px;
    font-weight: 600;
    font-size: 0.9rem;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}
.btn-premium-primary:hover {
    background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.25);
    color: #ffffff;
}

.btn-premium-outline {
    background: #ffffff;
    color: #475569;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 10px 20px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}
.btn-premium-outline:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
    transform: translateY(-1px);
}

/* Cartes KPI et Grille */
.user-list-kpi-card {
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
    border: 1px solid rgba(226, 232, 240, 0.8) !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: #ffffff;
    overflow: hidden;
    position: relative;
    padding: 24px !important;
}
.user-list-kpi-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; width: 100%; height: 4px;
}
.user-list-kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px -10px rgba(0, 0, 0, 0.08), 0 4px 12px -5px rgba(0, 0, 0, 0.03) !important;
}

.kpi-total::before { background: linear-gradient(90deg, #3b82f6, #6366f1); }
.kpi-total .kpi-icon-wrap { background: #eff6ff; color: #2563eb; }

.kpi-draft::before { background: linear-gradient(90deg, #64748b, #94a3b8); }
.kpi-draft .kpi-icon-wrap { background: #f8fafc; color: #475569; }

.kpi-pending::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.kpi-pending .kpi-icon-wrap { background: #fffbeb; color: #d97706; }

.kpi-approved::before { background: linear-gradient(90deg, #10b981, #34d399); }
.kpi-approved .kpi-icon-wrap { background: #ecfdf5; color: #059669; }

.kpi-icon-wrap {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    transition: all 0.3s ease;
}
.user-list-kpi-card:hover .kpi-icon-wrap {
    transform: scale(1.1);
}

/* Zone Filtres */
.filter-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
}
.premium-input {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #334155;
    font-size: 0.875rem;
    border-radius: 10px;
    padding: 8px 16px;
    transition: all 0.2s ease;
}
.premium-input:focus {
    border-color: #3b82f6;
    background-color: #ffffff;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
    color: #1e293b;
    outline: none;
}
.premium-select {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #334155;
    font-size: 0.875rem;
    border-radius: 10px;
    padding: 8px 16px;
    transition: all 0.2s ease;
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23475569' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 12px 12px;
    appearance: none;
}
.premium-select:focus {
    border-color: #3b82f6;
    background-color: #ffffff;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
    color: #1e293b;
    outline: none;
}

/* Tableau Premium */
.premium-table-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
    overflow: hidden;
}
.table-premium {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}
.table-premium th {
    background-color: #f8fafc;
    color: #475569;
    font-weight: 600;
    border-bottom: 1px solid #e2e8f0;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.75px;
    padding: 16px 20px;
}
.table-premium td {
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    color: #334155;
    font-size: 0.875rem;
    padding: 16px 20px;
    transition: all 0.2s ease;
}
.table-premium tr:last-child td {
    border-bottom: none;
}
.table-premium tr:hover td {
    background-color: rgba(248, 250, 252, 0.7);
}

/* Badges Statuts */
.badge-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 50px;
    letter-spacing: 0.2px;
}
.status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    display: inline-block;
}

.badge-status-draft {
    background-color: #f1f5f9;
    color: #475569;
}
.badge-status-draft .status-dot {
    background-color: #64748b;
}

.badge-status-pending {
    background-color: #fff7ed;
    color: #c2410c;
}
.badge-status-pending .status-dot {
    background-color: #f97316;
}

.badge-status-approved {
    background-color: #ecfdf5;
    color: #047857;
}
.badge-status-approved .status-dot {
    background-color: #10b981;
}

.badge-status-rejected {
    background-color: #fef2f2;
    color: #b91c1c;
}
.badge-status-rejected .status-dot {
    background-color: #ef4444;
}

/* Badge IA */
.badge-ai {
    background-color: #f3e8ff;
    color: #6b21a8;
    border: 1px solid #e9d5ff;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 50px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

/* Boutons d'Action */
.btn-action-circle {
    width: 36px;
    height: 36px;
    border-radius: 50% !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    border: 1px solid transparent;
}
.btn-action-circle:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08);
}
.btn-action-circle-primary {
    color: #2563eb;
    background: #eff6ff;
    border-color: #dbeafe;
}
.btn-action-circle-primary:hover {
    background: #2563eb !important;
    border-color: #2563eb !important;
    color: #ffffff !important;
}
.btn-action-circle-danger {
    color: #dc2626;
    background: #fef2f2;
    border-color: #fee2e2;
}
.btn-action-circle-danger:hover {
    background: #dc2626 !important;
    border-color: #dc2626 !important;
    color: #ffffff !important;
}
.btn-action-circle-warning {
    color: #d97706;
    background: #fffbeb;
    border-color: #fef3c7;
}
.btn-action-circle-warning:hover {
    background: #d97706 !important;
    border-color: #d97706 !important;
    color: #ffffff !important;
}
.btn-action-circle-secondary {
    color: #475569;
    background: #f1f5f9;
    border-color: #e2e8f0;
}
.btn-action-circle-secondary:hover {
    background: #475569 !important;
    border-color: #475569 !important;
    color: #ffffff !important;
}

/* Menu Dropdown Premium */
.dropdown-menu-premium {
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
    border-radius: 12px;
    padding: 8px;
}
.dropdown-menu-premium .dropdown-item {
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #475569;
    transition: all 0.2s ease;
}
.dropdown-menu-premium .dropdown-item:hover {
    background-color: #f1f5f9;
    color: #0f172a;
}
</style>

<div class="user-list-container">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h1 class="page-title mb-1">Mes rapports</h1>
            <p class="page-subtitle mb-0">
                <?= $isLeadOrAdminView
                    ? 'Vue coordination: les brouillons restent privés et visibles uniquement par leur auteur.'
                    : 'Suivi et historique de vos alertes et notes de monitoring.'; ?>
            </p>
        </div>
        <a href="?page=rapportage" class="btn-premium-primary gap-2 shadow-sm"><i class="fa-solid fa-arrow-left"></i>Retour au Hub</a>
    </div>

    <!-- STATS GRID -->
    <div class="row mb-4">
        <div class="col-6 col-md-3 mb-3 mb-md-0">
            <div class="card border-0 user-list-kpi-card kpi-total">
                <div class="d-flex align-items-center mb-3">
                    <div class="kpi-icon-wrap me-3">
                        <i class="fa-solid fa-folder-open"></i>
                    </div>
                    <small class="text-secondary fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.75rem;">Total Alertes</small>
                </div>
                <strong class="fs-2 fw-bold text-dark mb-0 ms-1" style="font-family: 'Inter', sans-serif;"><?= $totalAlertes; ?></strong>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-3 mb-md-0">
            <div class="card border-0 user-list-kpi-card kpi-draft">
                <div class="d-flex align-items-center mb-3">
                    <div class="kpi-icon-wrap me-3">
                        <i class="fa-solid fa-pen-ruler"></i>
                    </div>
                    <small class="text-secondary fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.75rem;">Brouillons</small>
                </div>
                <strong class="fs-2 fw-bold text-dark mb-0 ms-1" style="font-family: 'Inter', sans-serif;"><?= $totalBrouillons; ?></strong>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-3 mb-md-0">
            <div class="card border-0 user-list-kpi-card kpi-pending">
                <div class="d-flex align-items-center mb-3">
                    <div class="kpi-icon-wrap me-3">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>
                    <small class="text-secondary fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.75rem;">En attente</small>
                </div>
                <strong class="fs-2 fw-bold text-dark mb-0 ms-1" style="font-family: 'Inter', sans-serif;"><?= $totalEnAttente; ?></strong>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-3 mb-md-0">
            <div class="card border-0 user-list-kpi-card kpi-approved">
                <div class="d-flex align-items-center mb-3">
                    <div class="kpi-icon-wrap me-3">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <small class="text-secondary fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.75rem;">Alertes Validées</small>
                </div>
                <strong class="fs-2 fw-bold text-dark mb-0 ms-1" style="font-family: 'Inter', sans-serif;"><?= $totalValidees; ?></strong>
            </div>
        </div>
    </div>

    <!-- Barre de filtres: recherche plein texte + filtres métier + plage de dates. -->
    <div class="filter-card p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h6 mb-0 fw-bold text-dark"><i class="fa-solid fa-magnifying-glass me-2 text-primary"></i>Filtres de recherche</h2>
            <div class="d-flex align-items-center gap-3">
                <span class="small text-muted d-none d-sm-inline"><i class="fa-solid fa-bolt me-1 text-warning"></i>Mise à jour en temps réel</span>
                <div class="dropdown">
                    <button class="btn btn-premium-outline dropdown-toggle d-flex align-items-center gap-2 py-2 px-3 shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 0.85rem;">
                        <i class="fa-solid fa-download text-primary"></i> Exporter la liste
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-premium shadow-sm">
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
                <label class="form-label small mb-1 fw-semibold text-secondary">Recherche</label>
                <div class="position-relative">
                    <i class="fa-solid fa-magnifying-glass position-absolute text-secondary" style="left: 14px; top: 50%; transform: translateY(-50%); font-size: 0.875rem;"></i>
                    <input id="rapportage-search-input" type="search" class="form-control premium-input w-100" placeholder="Incident, lieu, organisation, statut..." style="height: 40px; padding-left: 38px !important;">
                </div>
            </div>

            <div class="col-xl-2 col-md-4">
                <label for="rapportage-filter-status" class="form-label small mb-1 fw-semibold text-secondary">Statut</label>
                <select id="rapportage-filter-status" class="form-select premium-select" style="height: 40px;">
                    <option value="">Tous les statuts</option>
                    <option value="brouillon">Brouillon</option>
                    <option value="soumis">Soumis</option>
                    <option value="en revision">En révision</option>
                    <option value="approuve">Approuvé/Validé</option>
                    <option value="rejete">Rejeté</option>
                </select>
            </div>

            <div class="col-xl-2 col-md-4">
                <label for="rapportage-filter-type" class="form-label small mb-1 fw-semibold text-secondary">Type</label>
                <select id="rapportage-filter-type" class="form-select premium-select" style="height: 40px;">
                    <option value="">Tous les types</option>
                    <option value="flash">FLASH</option>
                    <option value="note">NOTE</option>
                </select>
            </div>

            <div class="col-xl-2 col-md-4">
                <label for="rapportage-filter-urgency" class="form-label small mb-1 fw-semibold text-secondary">Urgence</label>
                <select id="rapportage-filter-urgency" class="form-select premium-select" style="height: 40px;">
                    <option value="">Toutes</option>
                    <option value="critique">Critique</option>
                    <option value="elevee">Élevée</option>
                    <option value="moyenne">Moyenne</option>
                    <option value="faible">Faible</option>
                </select>
            </div>

            <div class="col-xl-1 col-md-6">
                <label for="rapportage-filter-from" class="form-label small mb-1 fw-semibold text-secondary">Du</label>
                <input id="rapportage-filter-from" type="date" class="form-control premium-input" style="height: 40px;">
            </div>

            <div class="col-xl-1 col-md-6">
                <label for="rapportage-filter-to" class="form-label small mb-1 fw-semibold text-secondary">Au</label>
                <input id="rapportage-filter-to" type="date" class="form-control premium-input" style="height: 40px;">
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-3 mt-3 pt-3 border-top">
            <?php if ($isLeadOrAdminView): ?>
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="rapportage-filter-mine-only" style="cursor: pointer;">
                    <label class="form-check-label small text-secondary" style="cursor: pointer;" for="rapportage-filter-mine-only">Mes alertes uniquement</label>
                </div>
            <?php endif; ?>

            <button type="button" id="rapportage-filter-reset" class="btn btn-sm btn-link text-decoration-none text-secondary d-flex align-items-center gap-1 hover-primary ps-0">
                <i class="fa-solid fa-rotate-left" style="font-size: 0.75rem;"></i> Réinitialiser les filtres
            </button>

            <span class="ms-auto small text-secondary">
                <strong id="rapportage-visible-count" class="text-dark">0</strong>
                résultat(s) affiché(s)
            </span>
        </div>
    </div>

    <!-- Tableau principal des alertes avec métadonnées data-* pour le filtrage client. -->
    <div class="premium-table-card mb-4">
        <div class="table-responsive">
            <table class="table table-borderless table-premium mb-0" id="rapportage-user-table">
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
                        <th class="text-end" style="width: 1%;">Actions</th>
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
                        <td><span class="text-secondary" style="font-size: 0.85rem;"><i class="fa-regular fa-calendar me-2"></i><?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <?php if ($isLeadOrAdminView): ?>
                        <td><strong class="text-dark" style="font-size: 0.9rem;"><?= htmlspecialchars($organization, ENT_QUOTES, 'UTF-8'); ?></strong></td>
                        <?php endif; ?>
                        <td>
                            <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 0.75rem; font-weight: 600; letter-spacing: 0.3px;"><?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php if (isset($report['is_ai_generated']) && $report['is_ai_generated'] == 1): ?>
                                <span class="badge-ai ms-1">
                                    <i class="fa-solid fa-wand-magic-sparkles"></i> IA
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><span class="fw-medium text-dark"><i class="fa-solid fa-location-dot text-danger opacity-75 me-1" style="font-size: 0.8rem;"></i><?= htmlspecialchars($location, ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><span class="fw-semibold text-slate-800"><?= htmlspecialchars($incident, ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td>
                            <?= $statusBadgeHtml($status); ?>
                        </td>
                        <td class="text-end" style="white-space: nowrap; width: 1%;">
                            <div class="d-inline-flex gap-1 align-items-center">
                                <?php
                                $rawStatus = (string) ($report['workflow_status'] ?? '');
                                $reportId = (int) ($report['id'] ?? 0);
                                
                                if (strtolower(str_replace(['é', 'è', 'ê'], 'e', $rawStatus)) === 'brouillon' || $rawStatus === 'DRAFT') {
                                    echo '<a href="?page=rapportage-creer-wizar&id_brouillon='.$reportId.'&step=4" class="btn btn-action-circle btn-action-circle-primary" data-bs-toggle="tooltip" title="Finaliser / Modifier"><i class="fa-solid fa-pen"></i></a>';
                                    echo '<button class="btn btn-action-circle btn-action-circle-danger btn-delete" data-id="'.$reportId.'" data-bs-toggle="tooltip" title="Supprimer"><i class="fa-solid fa-trash"></i></button>';
                                } elseif (strtolower(str_replace(['é', 'è', 'ê'], 'e', $rawStatus)) === 'soumis' || $rawStatus === 'SUBMITTED') {
                                    echo '<button class="btn btn-action-circle btn-action-circle-warning btn-cancel-submit" data-id="'.$reportId.'" data-bs-toggle="tooltip" title="Annuler la soumission"><i class="fa-solid fa-rotate-left"></i></button>';
                                } else {
                                    echo '<a href="?page=rapportage-voir&id='.$reportId.'" class="btn btn-action-circle btn-action-circle-secondary" data-bs-toggle="tooltip" title="Voir"><i class="fa-solid fa-eye"></i></a>';
                                }
                                ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div><!-- /table-responsive -->
    </div><!-- /card tableau -->

    <div id="rapportage-empty-state" class="text-center py-5 d-none">
        <div class="mb-3">
            <i class="fa-regular fa-folder-open fs-1 text-muted opacity-50"></i>
        </div>
        <h5 class="fw-semibold text-secondary mb-1">Aucun rapport trouvé</h5>
        <p class="text-muted small mb-0">Essayez de modifier vos critères de recherche ou réinitialisez les filtres.</p>
    </div>
</div> <!-- /user-list-container -->

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

document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des Tooltips Bootstrap 5.3+
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Suppression
    document.querySelectorAll('.btn-delete').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var id = this.getAttribute('data-id');
            if (typeof premiumAlert !== 'undefined') {
                premiumAlert.fire({
                    title: 'Supprimer ce brouillon ?',
                    text: "Êtes-vous sûr de vouloir supprimer ce brouillon ?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Oui, supprimer',
                    cancelButtonText: 'Annuler',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('api/delete_report.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({ id: id, csrf: '<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8"); ?>' })
                        }).then(res => res.json()).then(data => {
                            if (data.ok) { window.location.reload(); }
                            else { premiumAlert.fire('Erreur', data.message || 'Impossible de supprimer.', 'error'); }
                        });
                    }
                });
            }
        });
    });

    // Annulation
    document.querySelectorAll('.btn-cancel-submit').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var id = this.getAttribute('data-id');
            if (typeof premiumAlert !== 'undefined') {
                premiumAlert.fire({
                    title: 'Annuler la soumission ?',
                    text: "Attention, cette action retirera l'alerte de la table de coordination du Cluster et la remettra en Brouillon pour modification. Confirmer ?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Oui, annuler',
                    cancelButtonText: 'Non, garder',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('api/cancel_submission.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({ id: id, csrf: '<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8"); ?>' })
                        }).then(res => res.json()).then(data => {
                            if (data.ok) { window.location.reload(); }
                            else { premiumAlert.fire('Erreur', data.message || 'Impossible d\'annuler.', 'error'); }
                        });
                    }
                });
            }
        });
    });
});
</script>
