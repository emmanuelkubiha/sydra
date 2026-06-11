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

                                <div class="mb-1">
                                    <label class="form-label fw-semibold" for="active_ai_provider">Fournisseur IA actif</label>
                                    <select class="form-select" id="active_ai_provider" name="active_ai_provider">
                                        <option value="xai">xAI (Grok)</option>
                                        <option value="openai">OpenAI (GPT-4o)</option>
                                    </select>
                                </div>

                                <div class="mb-3 mt-3 p-3 rounded-3 border" id="xai-key-block">
                                    <label class="form-label fw-semibold" for="xai_api_key">
                                        <i class="fa-solid fa-key me-1 text-primary"></i>Clé API xAI (Grok)
                                    </label>
                                    <div class="input-group mb-1">
                                        <input type="password"
                                               class="form-control"
                                               id="xai_api_key"
                                               name="xai_api_key"
                                               autocomplete="new-password"
                                               placeholder="Nouvelle clé (laisser vide pour conserver)">
                                        <button class="btn btn-outline-secondary" type="button" id="btn-toggle-xai-key" title="Afficher/Masquer">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted" id="xai-api-key-hint">Chargement...</small>
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
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <i class="fa-solid fa-user-secret text-primary"></i>
                                <h2 class="h6 mb-0">Sécurité & Codification</h2>
                            </div>

                            <div class="table-responsive mb-3">
                                <table class="table align-middle table-sm" id="codification-rules-table">
                                    <thead>
                                        <tr>
                                            <th>Terme sensible</th>
                                            <th>Code de remplacement</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="2" class="text-muted">Chargement des règles...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label" for="new_sensitive_term">Terme sensible</label>
                                    <input type="text" id="new_sensitive_term" class="form-control" placeholder="Ex: Groupe armé X">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label" for="new_replacement_code">Code de remplacement</label>
                                    <input type="text" id="new_replacement_code" class="form-control" placeholder="Ex: GA003">
                                </div>
                                <div class="col-md-2 d-grid">
                                    <button type="button" class="btn btn-outline-primary" id="btn-add-rule">
                                        <i class="fa-solid fa-plus me-1"></i>Ajouter
                                    </button>
                                </div>
                            </div>

                            <div class="mt-3 d-flex justify-content-end">
                                <button type="button" class="btn btn-primary" id="btn-save-codification">
                                    <i class="fa-regular fa-floppy-disk me-1"></i>Enregistrer les règles
                                </button>
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
</style>

<script>
(function () {
    var pageAccess = {
        role: <?= json_encode($role, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>,
        canIaSystem: <?= $canSeeIaSystem ? 'true' : 'false'; ?>,
        canSecurity: <?= $canSeeSecurity ? 'true' : 'false'; ?>,
        canBusiness: <?= $canSeeBusiness ? 'true' : 'false'; ?>
    };

    if (!pageAccess.canIaSystem && !pageAccess.canSecurity && !pageAccess.canBusiness) {
        return;
    }

    var apiUrl = 'api/save_settings.php';
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? String(csrfMeta.getAttribute('content') || '') : '';
    var pendingCodificationRules = [];

    function showToast(icon, title) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                toast: true,
                position: 'top-end',
                timer: 2500,
                timerProgressBar: true,
                showConfirmButton: false,
                icon: icon,
                title: title
            });
            return;
        }
        window.alert(title);
    }

    function callApi(payload) {
        payload = payload || {};
        if (csrfToken !== '') {
            payload.csrf = csrfToken;
        }

        return fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        }).then(function (response) {
            return response.json();
        });
    }

    function renderCodificationRules(rules) {
        var table = document.getElementById('codification-rules-table');
        if (!table) {
            return;
        }

        var tbody = table.querySelector('tbody');
        if (!tbody) {
            return;
        }

        tbody.innerHTML = '';
        var allRules = Array.isArray(rules) ? rules.slice() : [];

        pendingCodificationRules.forEach(function (item) {
            allRules.unshift({
                sensitive_term: item.sensitive_term,
                replacement_code: item.replacement_code,
                is_pending: true
            });
        });

        if (allRules.length === 0) {
            var emptyRow = document.createElement('tr');
            emptyRow.innerHTML = '<td colspan="2" class="text-muted">Aucune règle de codification enregistrée.</td>';
            tbody.appendChild(emptyRow);
            return;
        }

        allRules.forEach(function (rule) {
            var tr = document.createElement('tr');
            var sensitive = String(rule.sensitive_term || '');
            var replacement = String(rule.replacement_code || '');
            var pendingBadge = rule.is_pending ? ' <span class="badge text-bg-warning">en attente</span>' : '';

            tr.innerHTML = ''
                + '<td>' + sensitive.replace(/</g, '&lt;') + pendingBadge + '</td>'
                + '<td>' + replacement.replace(/</g, '&lt;') + '</td>';
            tbody.appendChild(tr);
        });
    }

    function loadSettings() {
        callApi({ action: 'load' })
            .then(function (data) {
                if (!data || data.ok !== true) {
                    throw new Error((data && data.message) ? data.message : 'Chargement impossible.');
                }

                var settings = data.settings || {};

                if (pageAccess.canIaSystem) {
                    var maintenanceInput = document.getElementById('maintenance_mode');
                    if (maintenanceInput) {
                        maintenanceInput.checked = String(settings.maintenance_mode || '0') === '1';
                    }

                    var providerSelect = document.getElementById('active_ai_provider');
                    if (providerSelect && settings.active_ai_provider) {
                        providerSelect.value = settings.active_ai_provider;
                    }

                    var xaiHint = document.getElementById('xai-api-key-hint');
                    if (xaiHint) {
                        if (data.has_xai_api_key) {
                            xaiHint.innerHTML = '<i class="fa-solid fa-circle-check text-success me-1"></i>Clé active : <code>' + (data.xai_key_masked || '••••••••') + '</code>';
                        } else {
                            xaiHint.innerHTML = '<i class="fa-solid fa-circle-xmark text-danger me-1"></i>Aucune clé configurée.';
                        }
                    }

                    var openaiHint = document.getElementById('openai-api-key-hint');
                    if (openaiHint) {
                        if (data.has_openai_api_key) {
                            openaiHint.innerHTML = '<i class="fa-solid fa-circle-check text-success me-1"></i>Clé active : <code>' + (data.openai_key_masked || '••••••••') + '</code>';
                        } else {
                            openaiHint.innerHTML = '<i class="fa-solid fa-circle-xmark text-danger me-1"></i>Aucune clé configurée.';
                        }
                    }
                }

                if (pageAccess.canBusiness) {
                    var reviewInput = document.getElementById('review_deadline_days');
                    if (reviewInput && settings.review_deadline_days) {
                        reviewInput.value = String(settings.review_deadline_days);
                    }
                }

                if (pageAccess.canSecurity) {
                    renderCodificationRules(data.codification_rules || []);
                }
            })
            .catch(function (error) {
                showToast('error', error.message || 'Erreur de chargement des paramètres.');
            });
    }

    // Toggle visibilité mot de passe pour les champs de clés
    ['xai_api_key', 'openai_api_key'].forEach(function (fieldId) {
        var btnId = 'btn-toggle-' + (fieldId === 'xai_api_key' ? 'xai' : 'openai') + '-key';
        var btn = document.getElementById(btnId);
        var field = document.getElementById(fieldId);
        if (!btn || !field) { return; }
        btn.addEventListener('click', function () {
            var isPassword = field.type === 'password';
            field.type = isPassword ? 'text' : 'password';
            var icon = btn.querySelector('i');
            if (icon) {
                icon.className = isPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
            }
        });
    });

    var btnSaveIaSystem = document.getElementById('btn-save-ia-system');
    if (btnSaveIaSystem) {
        btnSaveIaSystem.addEventListener('click', function () {
            var xaiInput = document.getElementById('xai_api_key');
            var openaiInput = document.getElementById('openai_api_key');
            var providerSelect = document.getElementById('active_ai_provider');
            var maintenanceInput = document.getElementById('maintenance_mode');

            var settings = {
                maintenance_mode: maintenanceInput && maintenanceInput.checked ? '1' : '0'
            };

            if (providerSelect) {
                settings.active_ai_provider = String(providerSelect.value || 'xai').trim();
            }

            if (xaiInput) {
                var xaiValue = String(xaiInput.value || '').trim();
                if (xaiValue !== '') {
                    settings.xai_api_key = xaiValue;
                }
            }

            if (openaiInput) {
                var openaiValue = String(openaiInput.value || '').trim();
                if (openaiValue !== '') {
                    settings.openai_api_key = openaiValue;
                }
            }

            callApi({ action: 'save_settings', settings: settings })
                .then(function (data) {
                    if (!data || data.ok !== true) {
                        throw new Error((data && data.message) ? data.message : 'Enregistrement impossible.');
                    }
                    if (xaiInput) { xaiInput.value = ''; }
                    if (openaiInput) { openaiInput.value = ''; }
                    showToast('success', 'Paramètres enregistrés avec succès');
                    loadSettings();
                })
                .catch(function (error) {
                    showToast('error', error.message || 'Erreur pendant la sauvegarde.');
                });
        });
    }

    var btnAddRule = document.getElementById('btn-add-rule');
    if (btnAddRule) {
        btnAddRule.addEventListener('click', function () {
            var sensitiveInput = document.getElementById('new_sensitive_term');
            var replacementInput = document.getElementById('new_replacement_code');
            if (!sensitiveInput || !replacementInput) {
                return;
            }

            var sensitive = String(sensitiveInput.value || '').trim();
            var replacement = String(replacementInput.value || '').trim();

            if (sensitive === '' || replacement === '') {
                showToast('warning', 'Veuillez renseigner le terme sensible et le code.');
                return;
            }

            pendingCodificationRules.push({
                sensitive_term: sensitive,
                replacement_code: replacement
            });

            sensitiveInput.value = '';
            replacementInput.value = '';
            loadSettings();
        });
    }

    var btnSaveCodification = document.getElementById('btn-save-codification');
    if (btnSaveCodification) {
        btnSaveCodification.addEventListener('click', function () {
            if (pendingCodificationRules.length === 0) {
                showToast('info', 'Aucune nouvelle règle à enregistrer.');
                return;
            }

            callApi({ action: 'save_codification_rules', rules: pendingCodificationRules })
                .then(function (data) {
                    if (!data || data.ok !== true) {
                        throw new Error((data && data.message) ? data.message : 'Enregistrement impossible.');
                    }
                    pendingCodificationRules = [];
                    showToast('success', 'Paramètres enregistrés avec succès');
                    loadSettings();
                })
                .catch(function (error) {
                    showToast('error', error.message || 'Erreur pendant la sauvegarde.');
                });
        });
    }

    var btnSaveBusiness = document.getElementById('btn-save-business-rules');
    if (btnSaveBusiness) {
        btnSaveBusiness.addEventListener('click', function () {
            var input = document.getElementById('review_deadline_days');
            if (!input) {
                return;
            }

            var value = Number(input.value || 0);
            if (!Number.isFinite(value) || value < 1 || value > 30) {
                showToast('warning', 'Le délai doit être compris entre 1 et 30 jours.');
                return;
            }

            callApi({ action: 'save_settings', settings: { review_deadline_days: String(Math.round(value)) } })
                .then(function (data) {
                    if (!data || data.ok !== true) {
                        throw new Error((data && data.message) ? data.message : 'Enregistrement impossible.');
                    }
                    showToast('success', 'Paramètres enregistrés avec succès');
                    loadSettings();
                })
                .catch(function (error) {
                    showToast('error', error.message || 'Erreur pendant la sauvegarde.');
                });
        });
    }

    loadSettings();
})();
</script>
