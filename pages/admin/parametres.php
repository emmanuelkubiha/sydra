<?php
/** @var array<string, mixed>|null $authUser */

$role = strtoupper((string) ($authUser['role'] ?? ''));
$isAdmin = $role === 'ADMIN';
$isLead = in_array($role, ['GTMP_LEAD', 'LEAD_GTMP'], true);
$isColead = $role === 'GTMP_COLEAD';

$canSeeIaSystem = $isAdmin;
$canSeeSecurity = $isAdmin || $isLead;
$canSeeBusiness = $isAdmin || $isLead;
$hasAnyTab = $canSeeIaSystem || $canSeeSecurity || $canSeeBusiness;
?>

<div class="card border-0 shadow-sm rounded-4 p-3 p-lg-4 settings-shell">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Paramètres & Configuration</h1>
            <p class="text-muted mb-0">Administration centralisée de la plateforme SyDRA.</p>
        </div>
        <span class="badge text-bg-light border">Rôle: <?= htmlspecialchars($role !== '' ? $role : 'INCONNU', ENT_QUOTES, 'UTF-8'); ?></span>
    </div>

    <?php if (!$hasAnyTab): ?>
        <div class="alert alert-warning rounded-4 mb-0">
            Votre profil peut accéder à la page, mais aucun module n'est autorisé pour modification.
        </div>
    <?php else: ?>
    <div class="row g-3">
        <div class="col-lg-4 col-xl-3">
            <div class="nav flex-column nav-pills settings-nav rounded-4 p-2" id="settings-tab" role="tablist" aria-orientation="vertical">
                <?php $firstTab = true; ?>
                <?php if ($canSeeIaSystem): ?>
                    <button class="nav-link text-start <?= $firstTab ? 'active' : ''; ?>"
                            id="tab-ia-system"
                            data-bs-toggle="pill"
                            data-bs-target="#pane-ia-system"
                            type="button"
                            role="tab"
                            aria-controls="pane-ia-system"
                            aria-selected="<?= $firstTab ? 'true' : 'false'; ?>">
                        <i class="fa-solid fa-robot me-2"></i>Configuration IA & Système
                    </button>
                    <?php $firstTab = false; ?>
                <?php endif; ?>

                <?php if ($canSeeSecurity): ?>
                    <button class="nav-link text-start <?= $firstTab ? 'active' : ''; ?>"
                            id="tab-security"
                            data-bs-toggle="pill"
                            data-bs-target="#pane-security"
                            type="button"
                            role="tab"
                            aria-controls="pane-security"
                            aria-selected="<?= $firstTab ? 'true' : 'false'; ?>">
                        <i class="fa-solid fa-user-secret me-2"></i>Sécurité & Codification
                    </button>
                    <?php $firstTab = false; ?>
                <?php endif; ?>

                <?php if ($canSeeBusiness): ?>
                    <button class="nav-link text-start <?= $firstTab ? 'active' : ''; ?>"
                            id="tab-business"
                            data-bs-toggle="pill"
                            data-bs-target="#pane-business"
                            type="button"
                            role="tab"
                            aria-controls="pane-business"
                            aria-selected="<?= $firstTab ? 'true' : 'false'; ?>">
                        <i class="fa-solid fa-scale-balanced me-2"></i>Règles Métier
                    </button>
                    <?php $firstTab = false; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-8 col-xl-9">
            <div class="tab-content" id="settings-tab-content">
                <?php $firstPane = true; ?>

                <?php if ($canSeeIaSystem): ?>
                <div class="tab-pane fade <?= $firstPane ? 'show active' : ''; ?>" id="pane-ia-system" role="tabpanel" aria-labelledby="tab-ia-system" tabindex="0">
                    <div class="card border-0 shadow-sm rounded-4 settings-card">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <i class="fa-solid fa-robot text-primary"></i>
                                <h2 class="h6 mb-0">Configuration IA & Système</h2>
                            </div>

                            <form id="form-ia-system" novalidate>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold mb-2">
                                        <i class="fa-solid fa-microchip me-1 text-primary"></i>
                                        Fournisseur IA actif
                                    </label>
                                    <p class="text-muted small mb-2">Le changement est enregistré automatiquement à la sélection.</p>
                                    <div class="d-flex gap-3 flex-wrap" id="provider-cards-wrapper">

                                        <!-- Card Groq -->
                                        <label class="provider-card flex-fill" id="card-groq" for="provider-groq">
                                            <input type="radio" name="active_ai_provider" id="provider-groq" value="groq" class="provider-radio visually-hidden">
                                            <div class="provider-card-inner">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <div class="provider-card-name">
                                                            <i class="fa-solid fa-bolt text-warning me-1"></i>Groq
                                                        </div>
                                                        <div class="provider-card-model text-muted small">llama-3.1-8b-instant</div>
                                                    </div>
                                                    <span class="badge text-bg-success provider-card-badge">Gratuit</span>
                                                </div>
                                                <div class="provider-card-desc mt-2 text-muted small">
                                                    Ultra-rapide · Idéal pour l'usage quotidien
                                                </div>
                                                <div class="provider-card-check mt-2">
                                                    <i class="fa-solid fa-circle-check"></i>
                                                </div>
                                            </div>
                                        </label>

                                        <!-- Card OpenAI -->
                                        <label class="provider-card flex-fill" id="card-openai" for="provider-openai">
                                            <input type="radio" name="active_ai_provider" id="provider-openai" value="openai" class="provider-radio visually-hidden">
                                            <div class="provider-card-inner">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <div class="provider-card-name">
                                                            <i class="fa-solid fa-robot text-success me-1"></i>OpenAI
                                                        </div>
                                                        <div class="provider-card-model text-muted small">gpt-4o</div>
                                                    </div>
                                                    <span class="badge text-bg-primary provider-card-badge">Puissant</span>
                                                </div>
                                                <div class="provider-card-desc mt-2 text-muted small">
                                                    Plus précis · Analyse avancée
                                                </div>
                                                <div class="provider-card-check mt-2">
                                                    <i class="fa-solid fa-circle-check"></i>
                                                </div>
                                            </div>
                                        </label>

                                    </div>
                                    <!-- Sélecteur caché pour rétrocompatibilité JS save -->
                                    <input type="hidden" id="active_ai_provider" name="active_ai_provider" value="groq">
                                </div>

                                <div class="mb-3 mt-3 p-3 rounded-3 border" id="groq-key-block">
                                    <label class="form-label fw-semibold" for="groq_api_key">
                                        <i class="fa-solid fa-key me-1 text-primary"></i>Clé API Groq (Llama 3)
                                    </label>
                                    <div class="input-group mb-1">
                                        <input type="password"
                                               class="form-control"
                                               id="groq_api_key"
                                               name="groq_api_key"
                                               autocomplete="new-password"
                                               placeholder="Nouvelle clé (laisser vide pour conserver)">
                                        <button class="btn btn-outline-secondary" type="button" id="btn-toggle-groq-key" title="Afficher/Masquer">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted" id="groq-api-key-hint">Chargement...</small>
                                </div>

                                <div class="mb-3 p-3 rounded-3 border" id="openai-key-block">
                                    <label class="form-label fw-semibold" for="openai_api_key">
                                        <i class="fa-solid fa-key me-1 text-success"></i>Clé API OpenAI (GPT-4o)
                                    </label>
                                    <div class="input-group mb-1">
                                        <input type="password"
                                               class="form-control"
                                               id="openai_api_key"
                                               name="openai_api_key"
                                               autocomplete="new-password"
                                               placeholder="Nouvelle clé (laisser vide pour conserver)">
                                        <button class="btn btn-outline-secondary" type="button" id="btn-toggle-openai-key" title="Afficher/Masquer">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted" id="openai-api-key-hint">Chargement...</small>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch" id="maintenance_mode" name="maintenance_mode">
                                    <label class="form-check-label" for="maintenance_mode">Activer le Mode Maintenance</label>
                                </div>

                                <button type="button" class="btn btn-primary" id="btn-save-ia-system">
                                    <i class="fa-regular fa-floppy-disk me-1"></i>Enregistrer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php $firstPane = false; ?>
                <?php endif; ?>

                <?php if ($canSeeSecurity): ?>
                <div class="tab-pane fade <?= $firstPane ? 'show active' : ''; ?>" id="pane-security" role="tabpanel" aria-labelledby="tab-security" tabindex="0">
                    <div class="card border-0 shadow-sm rounded-4 settings-card">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-user-secret text-primary"></i>
                                    <h2 class="h6 mb-0">Sécurité &amp; Codification</h2>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" id="btn-add-codification">
                                    <i class="fa-solid fa-plus me-1"></i>Ajouter une règle
                                </button>
                            </div>
                            <p class="text-muted small mb-3">
                                Ces règles remplacent automatiquement les termes sensibles par des codes avant l'envoi à l'IA.
                                <strong>Exemple :</strong> "Groupe M23" → <code>GA001</code>
                            </p>

                            <div class="table-responsive">
                                <table class="table align-middle table-hover table-sm w-100" id="codification-rules-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Terme sensible</th>
                                            <th>Code de remplacement</th>
                                            <th class="text-end" style="min-width:160px">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-3">
                                                <i class="fa-solid fa-spinner fa-spin me-1"></i>Chargement des règles...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php $firstPane = false; ?>
                <?php endif; ?>

                <?php if ($canSeeBusiness): ?>
                <div class="tab-pane fade <?= $firstPane ? 'show active' : ''; ?>" id="pane-business" role="tabpanel" aria-labelledby="tab-business" tabindex="0">
                    <div class="card border-0 shadow-sm rounded-4 settings-card">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <i class="fa-solid fa-scale-balanced text-primary"></i>
                                <h2 class="h6 mb-0">Règles Métier</h2>
                            </div>

                            <form id="form-business-rules" novalidate>
                                <div class="mb-3">
                                    <label class="form-label" for="review_deadline_days">Délai d'expiration par défaut pour les demandes d'informations (en jours)</label>
                                    <input type="number" min="1" max="30" class="form-control" id="review_deadline_days" name="review_deadline_days" value="3">
                                </div>

                                <button type="button" class="btn btn-primary" id="btn-save-business-rules">
                                    <i class="fa-regular fa-floppy-disk me-1"></i>Enregistrer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.settings-shell {
    border: 1px solid #dbeafe;
}

.settings-nav {
    background: linear-gradient(160deg, #f8fbff 0%, #eef5ff 100%);
    border: 1px solid #dbeafe;
    gap: 6px;
}

.settings-nav .nav-link {
    color: #334155;
    border-radius: 12px;
    font-weight: 600;
}

.settings-nav .nav-link.active {
    background: #005BBB;
    color: #ffffff;
    box-shadow: 0 8px 18px rgba(0, 91, 187, 0.24);
}

.settings-card {
    border: 1px solid #e2e8f0;
}

/* ── Provider Radio Cards ─────────────────────────── */
.provider-card {
    cursor: pointer;
    min-width: 160px;
}
.provider-card-inner {
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    padding: 14px 16px;
    background: #f8fafc;
    transition: border-color .18s ease, background .18s ease, box-shadow .18s ease;
    position: relative;
}
.provider-card:hover .provider-card-inner {
    border-color: #93bfef;
    background: #f0f7ff;
    box-shadow: 0 4px 14px rgba(0,91,187,.10);
}
.provider-card-name {
    font-weight: 700;
    font-size: .95rem;
    color: #1e293b;
}
.provider-card-model {
    font-size: .78rem;
    margin-top: 2px;
}
.provider-card-desc {
    font-size: .78rem;
    line-height: 1.4;
}
.provider-card-check {
    color: #cbd5e1;
    font-size: 1.1rem;
    transition: color .18s ease;
}
/* État actif */
.provider-card.is-active .provider-card-inner {
    border-color: #005BBB;
    background: #eff6ff;
    box-shadow: 0 4px 18px rgba(0,91,187,.15);
}
.provider-card.is-active .provider-card-check {
    color: #005BBB;
}
/* ── Codification DataTable ───────────────────────── */
#codification-rules-table_wrapper .dataTables_filter input {
    border-radius: 8px;
    border: 1.5px solid #e2e8f0;
    padding: 4px 10px;
    font-size: .85rem;
}
#codification-rules-table td, #codification-rules-table th {
    vertical-align: middle;
    font-size: .85rem;
}
.codif-action-btn {
    padding: 3px 9px;
    font-size: .78rem;
    border-radius: 7px;
}
</style>

<script>
(function () {
    'use strict';

    var pageAccess = {
        role:        <?= json_encode($role, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>,
        canIaSystem: <?= $canSeeIaSystem ? 'true' : 'false'; ?>,
        canSecurity: <?= $canSeeSecurity ? 'true' : 'false'; ?>,
        canBusiness: <?= $canSeeBusiness ? 'true' : 'false'; ?>
    };

    if (!pageAccess.canIaSystem && !pageAccess.canSecurity && !pageAccess.canBusiness) {
        return;
    }

    var apiUrl     = 'api/save_settings.php';
    var codifUrl   = 'api/codification_handler.php';
    var csrfMeta   = document.querySelector('meta[name="csrf-token"]');
    var csrfToken  = csrfMeta ? String(csrfMeta.getAttribute('content') || '') : '';
    var codifTable = null; // Référence à l'instance DataTables

    // ── Toast Swal2 ────────────────────────────────────────────────────────
    function showToast(icon, title) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                toast: true, position: 'top-end', timer: 2500,
                timerProgressBar: true, showConfirmButton: false,
                icon: icon, title: title
            });
            return;
        }
        window.alert(title);
    }

    // ── Appel API générique ─────────────────────────────────────────────────
    function callApi(url, payload) {
        payload = payload || {};
        if (csrfToken !== '') { payload.csrf = csrfToken; }
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(payload)
        }).then(function (r) { return r.json(); });
    }

    // ── Mission 5 : Radio Cards Provider — sélection & AJAX immédiat ──────
    function activateProviderCard(value) {
        document.querySelectorAll('.provider-card').forEach(function (card) {
            card.classList.remove('is-active');
            var radio = card.querySelector('.provider-radio');
            if (radio && radio.value === value) {
                card.classList.add('is-active');
                radio.checked = true;
            }
        });
        // Sync le champ caché
        var hidden = document.getElementById('active_ai_provider');
        if (hidden) { hidden.value = value; }
    }

    document.querySelectorAll('.provider-radio').forEach(function (radio) {
        radio.addEventListener('change', function () {
            var selectedProvider = this.value;
            activateProviderCard(selectedProvider);
            // Enregistrement AJAX immédiat
            callApi(apiUrl, { action: 'save_settings', settings: { active_ai_provider: selectedProvider } })
                .then(function (data) {
                    if (!data || data.ok !== true) { throw new Error(data.message || 'Erreur'); }
                    showToast('success', 'Fournisseur IA mis à jour : ' + selectedProvider.toUpperCase());
                })
                .catch(function (err) {
                    showToast('error', err.message || 'Impossible de changer le fournisseur.');
                });
        });
    });

    // ── Chargement des paramètres ─────────────────────────────────────────
    function loadSettings() {
        callApi(apiUrl, { action: 'load' })
            .then(function (data) {
                if (!data || data.ok !== true) {
                    throw new Error((data && data.message) ? data.message : 'Chargement impossible.');
                }
                var settings = data.settings || {};

                if (pageAccess.canIaSystem) {
                    // Activer la bonne radio card
                    activateProviderCard(String(settings.active_ai_provider || 'groq'));

                    var maintenanceInput = document.getElementById('maintenance_mode');
                    if (maintenanceInput) {
                        maintenanceInput.checked = String(settings.maintenance_mode || '0') === '1';
                    }

                    var groqHint = document.getElementById('groq-api-key-hint');
                    if (groqHint) {
                        groqHint.innerHTML = data.has_groq_api_key
                            ? '<i class="fa-solid fa-circle-check text-success me-1"></i>Clé active : <code>' + (data.groq_key_masked || '••••••••') + '</code>'
                            : '<i class="fa-solid fa-circle-xmark text-danger me-1"></i>Aucune clé configurée.';
                    }
                    var openaiHint = document.getElementById('openai-api-key-hint');
                    if (openaiHint) {
                        openaiHint.innerHTML = data.has_openai_api_key
                            ? '<i class="fa-solid fa-circle-check text-success me-1"></i>Clé active : <code>' + (data.openai_key_masked || '••••••••') + '</code>'
                            : '<i class="fa-solid fa-circle-xmark text-danger me-1"></i>Aucune clé configurée.';
                    }
                }

                if (pageAccess.canBusiness) {
                    var reviewInput = document.getElementById('review_deadline_days');
                    if (reviewInput && settings.review_deadline_days) {
                        reviewInput.value = String(settings.review_deadline_days);
                    }
                }

                if (pageAccess.canSecurity) {
                    loadCodificationTable();
                }
            })
            .catch(function (error) {
                showToast('error', error.message || 'Erreur de chargement des paramètres.');
            });
    }

    // ── Mission 6 : CRUD Codification avec DataTables ─────────────────────

    function loadCodificationTable() {
        callApi(codifUrl, { action: 'list' })
            .then(function (data) {
                if (!data || data.ok !== true) { throw new Error(data.message || 'Chargement impossible.'); }
                renderCodificationDataTable(data.rules || []);
            })
            .catch(function (err) {
                showToast('error', err.message || 'Erreur chargement codification.');
            });
    }

    function renderCodificationDataTable(rules) {
        var tableEl = document.getElementById('codification-rules-table');
        if (!tableEl) { return; }

        // Détruire l'ancienne instance DataTables si elle existe
        if (codifTable) {
            codifTable.destroy();
            codifTable = null;
        }

        var tbody = tableEl.querySelector('tbody');
        if (!tbody) { return; }
        tbody.innerHTML = '';

        if (rules.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">Aucune règle de codification enregistrée.</td></tr>';
            return;
        }

        rules.forEach(function (rule) {
            var id   = rule.id ? String(rule.id) : '';
            var term = String(rule.sensitive_term    || '').replace(/</g, '&lt;');
            var code = String(rule.replacement_code  || '').replace(/</g, '&lt;');
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td><strong>' + term + '</strong></td>' +
                '<td><code class="text-primary">' + code + '</code></td>' +
                '<td class="text-end">' +
                    '<button class="btn btn-outline-primary btn-sm codif-action-btn me-1" ' +
                        'data-action="edit" data-id="' + id + '" data-term="' + term + '" data-code="' + code + '">' +
                        '<i class="fa-solid fa-pen me-1"></i>Modifier' +
                    '</button>' +
                    '<button class="btn btn-outline-danger btn-sm codif-action-btn" ' +
                        'data-action="delete" data-id="' + id + '" data-term="' + term + '">' +
                        '<i class="fa-solid fa-trash me-1"></i>Suppr.' +
                    '</button>' +
                '</td>';
            tbody.appendChild(tr);
        });

        // Init DataTables (jQuery disponible via pied_de_page.php)
        if (window.$ && $.fn.DataTable) {
            codifTable = $(tableEl).DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/fr-FR.json'
                },
                pageLength: 10,
                order: [[0, 'asc']],
                columnDefs: [{ orderable: false, targets: 2 }]
            });
        }

        // Délégation d'événements sur les boutons (fonctionne même avec DataTables paginé)
        tableEl.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-action]');
            if (!btn) { return; }
            var action = btn.getAttribute('data-action');
            var id     = btn.getAttribute('data-id');
            var term   = btn.getAttribute('data-term');
            var code   = btn.getAttribute('data-code');

            if (action === 'edit')   { openEditModal(id, term, code); }
            if (action === 'delete') { openDeleteConfirm(id, term); }
        });
    }

    // Bouton "Ajouter une règle"
    var btnAddCodif = document.getElementById('btn-add-codification');
    if (btnAddCodif) {
        btnAddCodif.addEventListener('click', function () { openAddModal(); });
    }

    function openAddModal() {
        if (!window.Swal) { return; }
        Swal.fire({
            title: '<i class="fa-solid fa-plus-circle text-primary me-2"></i>Ajouter une règle',
            html:
                '<div class="mb-3 text-start">' +
                    '<label class="form-label fw-semibold">Terme sensible</label>' +
                    '<input id="swal-term" class="form-control" placeholder="Ex: Groupe armé M23">' +
                '</div>' +
                '<div class="text-start">' +
                    '<label class="form-label fw-semibold">Code de remplacement</label>' +
                    '<input id="swal-code" class="form-control" placeholder="Ex: GA001">' +
                '</div>',
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-save me-1"></i>Enregistrer',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#005BBB',
            focusConfirm: false,
            preConfirm: function () {
                var term = String(document.getElementById('swal-term').value || '').trim();
                var code = String(document.getElementById('swal-code').value || '').trim();
                if (!term || !code) {
                    Swal.showValidationMessage('Les deux champs sont obligatoires.');
                    return false;
                }
                return { term: term, code: code };
            }
        }).then(function (result) {
            if (!result.isConfirmed) { return; }
            callApi(codifUrl, { action: 'add', term: result.value.term, code: result.value.code })
                .then(function (data) {
                    if (!data || data.ok !== true) { throw new Error(data.message || 'Erreur'); }
                    showToast('success', 'Règle ajoutée avec succès');
                    loadCodificationTable();
                })
                .catch(function (err) { showToast('error', err.message); });
        });
    }

    function openEditModal(id, term, code) {
        if (!window.Swal) { return; }
        Swal.fire({
            title: '<i class="fa-solid fa-pen text-primary me-2"></i>Modifier la règle',
            html:
                '<div class="mb-3 text-start">' +
                    '<label class="form-label fw-semibold">Terme sensible</label>' +
                    '<input id="swal-term" class="form-control" value="' + term.replace(/"/g, '&quot;') + '">' +
                '</div>' +
                '<div class="text-start">' +
                    '<label class="form-label fw-semibold">Code de remplacement</label>' +
                    '<input id="swal-code" class="form-control" value="' + code.replace(/"/g, '&quot;') + '">' +
                '</div>',
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-save me-1"></i>Enregistrer',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#005BBB',
            focusConfirm: false,
            preConfirm: function () {
                var newTerm = String(document.getElementById('swal-term').value || '').trim();
                var newCode = String(document.getElementById('swal-code').value || '').trim();
                if (!newTerm || !newCode) {
                    Swal.showValidationMessage('Les deux champs sont obligatoires.');
                    return false;
                }
                return { term: newTerm, code: newCode };
            }
        }).then(function (result) {
            if (!result.isConfirmed) { return; }
            callApi(codifUrl, { action: 'update', id: id, term: result.value.term, code: result.value.code })
                .then(function (data) {
                    if (!data || data.ok !== true) { throw new Error(data.message || 'Erreur'); }
                    showToast('success', 'Règle modifiée avec succès');
                    loadCodificationTable();
                })
                .catch(function (err) { showToast('error', err.message); });
        });
    }

    function openDeleteConfirm(id, term) {
        if (!window.Swal) { return; }
        Swal.fire({
            icon: 'warning',
            title: 'Supprimer cette règle ?',
            html: 'Le terme <strong>' + term + '</strong> sera définitivement supprimé.',
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-trash me-1"></i>Supprimer',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#e74c3c',
        }).then(function (result) {
            if (!result.isConfirmed) { return; }
            callApi(codifUrl, { action: 'delete', id: id })
                .then(function (data) {
                    if (!data || data.ok !== true) { throw new Error(data.message || 'Erreur'); }
                    showToast('success', 'Règle supprimée');
                    loadCodificationTable();
                })
                .catch(function (err) { showToast('error', err.message); });
        });
    }

    // ── Toggle visibilité clés API ─────────────────────────────────────────
    ['groq_api_key', 'openai_api_key'].forEach(function (fieldId) {
        var prefix = fieldId === 'groq_api_key' ? 'groq' : 'openai';
        var btn    = document.getElementById('btn-toggle-' + prefix + '-key');
        var field  = document.getElementById(fieldId);
        if (!btn || !field) { return; }
        btn.addEventListener('click', function () {
            var isPassword = field.type === 'password';
            field.type = isPassword ? 'text' : 'password';
            var icon = btn.querySelector('i');
            if (icon) { icon.className = isPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'; }
        });
    });

    // ── Sauvegarder les clés API + maintenance ─────────────────────────────
    var btnSaveIaSystem = document.getElementById('btn-save-ia-system');
    if (btnSaveIaSystem) {
        btnSaveIaSystem.addEventListener('click', function () {
            var groqInput       = document.getElementById('groq_api_key');
            var openaiInput     = document.getElementById('openai_api_key');
            var providerHidden  = document.getElementById('active_ai_provider');
            var maintenanceInput = document.getElementById('maintenance_mode');

            var settings = {
                maintenance_mode:    maintenanceInput && maintenanceInput.checked ? '1' : '0',
                active_ai_provider:  providerHidden ? String(providerHidden.value || 'groq') : 'groq'
            };

            if (groqInput) {
                var gv = String(groqInput.value || '').trim();
                if (gv !== '') { settings.groq_api_key = gv; }
            }
            if (openaiInput) {
                var ov = String(openaiInput.value || '').trim();
                if (ov !== '') { settings.openai_api_key = ov; }
            }

            callApi(apiUrl, { action: 'save_settings', settings: settings })
                .then(function (data) {
                    if (!data || data.ok !== true) { throw new Error(data.message || 'Enregistrement impossible.'); }
                    if (groqInput)   { groqInput.value = ''; }
                    if (openaiInput) { openaiInput.value = ''; }
                    showToast('success', 'Paramètres enregistrés avec succès');
                    loadSettings();
                })
                .catch(function (error) {
                    showToast('error', error.message || 'Erreur pendant la sauvegarde.');
                });
        });
    }

    // ── Règles métier ──────────────────────────────────────────────────────
    var btnSaveBusiness = document.getElementById('btn-save-business-rules');
    if (btnSaveBusiness) {
        btnSaveBusiness.addEventListener('click', function () {
            var input = document.getElementById('review_deadline_days');
            if (!input) { return; }
            var value = Number(input.value || 0);
            if (!Number.isFinite(value) || value < 1 || value > 30) {
                showToast('warning', 'Le délai doit être compris entre 1 et 30 jours.');
                return;
            }
            callApi(apiUrl, { action: 'save_settings', settings: { review_deadline_days: String(Math.round(value)) } })
                .then(function (data) {
                    if (!data || data.ok !== true) { throw new Error(data.message || 'Enregistrement impossible.'); }
                    showToast('success', 'Paramètres enregistrés avec succès');
                    loadSettings();
                })
                .catch(function (error) { showToast('error', error.message || 'Erreur.'); });
        });
    }

    loadSettings();
})();
</script>

