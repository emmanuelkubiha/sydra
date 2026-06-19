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

$pdo = db($config);

// Charger les règles de codification
$codificationRules = [];
if ($canSeeSecurity) {
    try {
        $rulesStmt = $pdo->query('SELECT * FROM codification_rules ORDER BY term ASC');
        $codificationRules = $rulesStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Ignorer ou logguer
    }
}

// Charger l'historique des logs d'interception
$codificationLogs = [];
if ($canSeeSecurity) {
    try {
        $logsStmt = $pdo->query('
            SELECT l.*, r.reference_code, r.title 
            FROM codification_logs l 
            JOIN reports r ON l.report_id = r.id 
            ORDER BY l.created_at DESC 
            LIMIT 50
        ');
        $codificationLogs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Si la table n'a pas encore de logs, on continue sans bloquer
    }
}
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
                    
                    <!-- Dictionnaire des règles de codification -->
                    <div class="card border-0 shadow-sm rounded-4 settings-card mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-shield-halved text-primary"></i>
                                    <h2 class="h6 mb-0 fw-bold">Dictionnaire de codification</h2>
                                </div>
                                <button type="button" class="btn btn-sydra-primary rounded-pill px-4 btn-sm" data-bs-toggle="modal" data-bs-target="#addRuleModal">
                                    <i class="fa-solid fa-plus me-1"></i> Ajouter une règle
                                </button>
                            </div>
                            <p class="text-muted small mb-3">
                                Les termes sensibles définis ci-dessous sont automatiquement interceptés et anonymisés à la volée avant écriture en base de données.
                            </p>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle w-100" id="rules-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Terme sensible</th>
                                            <th>Code de remplacement</th>
                                            <th>Statut</th>
                                            <th class="text-end" style="min-width:110px">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($codificationRules as $rule): ?>
                                            <tr>
                                                <td><strong class="text-danger"><?= htmlspecialchars($rule['term'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                                <td><span class="badge bg-secondary px-2.5 py-1.5 rounded-3"><?= htmlspecialchars($rule['replacement_code'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                                <td>
                                                    <?php if ((int)$rule['is_active'] === 1): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5">Actif</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-muted border rounded-pill px-2.5">Inactif</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-outline-sydra-primary btn-sm rounded-3 px-2.5 py-1 me-1 js-edit-rule-btn" 
                                                            data-id="<?= (int)$rule['id']; ?>" 
                                                            data-term="<?= htmlspecialchars($rule['term'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                            data-code="<?= htmlspecialchars($rule['replacement_code'], ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-active="<?= (int)$rule['is_active']; ?>"
                                                            title="Modifier la règle">
                                                        <i class="fa-solid fa-pencil me-1"></i> Modifier
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-3 px-2.5 py-1 js-delete-rule-btn" 
                                                            data-id="<?= (int)$rule['id']; ?>" 
                                                            data-term="<?= htmlspecialchars($rule['term'], ENT_QUOTES, 'UTF-8'); ?>"
                                                            title="Supprimer la règle">
                                                        <i class="fa-solid fa-trash-can me-1"></i> Supprimer
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Audits Logs d'interception -->
                    <div class="card border-0 shadow-sm rounded-4 settings-card">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="fa-solid fa-clock-rotate-left text-primary"></i>
                                <h2 class="h6 mb-0 fw-bold">Interceptions récentes (Audit logs)</h2>
                            </div>
                            <p class="text-muted small mb-3">Historique des modifications apportées aux rapports avant leur stockage permanent.</p>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle table-sm w-100" id="logs-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Réf</th>
                                            <th>Rubrique</th>
                                            <th>Original</th>
                                            <th>Codifié</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($codificationLogs as $log): ?>
                                            <tr>
                                                <td>
                                                    <a href="?page=rapportage-details&id=<?= (int)$log['report_id']; ?>" class="fw-semibold small">
                                                        <?= htmlspecialchars($log['reference_code'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </a>
                                                </td>
                                                <td><code class="small text-primary" style="font-size: 0.72rem;"><?= htmlspecialchars($log['field_name'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                                <td><div class="text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($log['original_excerpt'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($log['original_excerpt'], ENT_QUOTES, 'UTF-8'); ?></div></td>
                                                <td><div class="text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($log['coded_excerpt'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($log['coded_excerpt'], ENT_QUOTES, 'UTF-8'); ?></div></td>
                                            </tr>
                                        <?php endforeach; ?>
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

<!-- Modal Bootstrap 5 pour l'ajout de règle -->
<div class="modal fade" id="addRuleModal" tabindex="-1" aria-labelledby="addRuleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold text-dark" id="addRuleModalLabel">
                    <i class="fa-solid fa-shield-halved text-primary me-2"></i>Ajouter une règle de codification
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="addRuleForm" novalidate>
                <div class="modal-body px-4 py-3">
                    <p class="text-muted small">Les termes correspondants insensibles à la casse seront anonymisés.</p>
                    
                    <div id="modalAlert" class="alert alert-danger border-0 rounded-3 small py-2 px-3 mb-3 d-none">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i><span id="modalAlertText"></span>
                    </div>

                    <div class="mb-3">
                        <label for="modal-term" class="form-label small fw-semibold">Terme sensible (ex: FARDC, Wazalendo)*</label>
                        <input type="text" class="form-control rounded-3" name="term" id="modal-term" required placeholder="Entrez le mot à anonymiser...">
                        <div class="invalid-feedback">Veuillez spécifier le terme sensible.</div>
                    </div>

                    <div class="mb-3">
                        <label for="modal-code" class="form-label small fw-semibold">Code de remplacement (ex: GA003, GA002)*</label>
                        <input type="text" class="form-control rounded-3" name="replacement_code" id="modal-code" required placeholder="Entrez le code de substitution...">
                        <div class="invalid-feedback">Veuillez spécifier le code de remplacement.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-1">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-sydra-primary rounded-pill px-4" id="btnSubmitRule">
                        <i class="fa-solid fa-check me-1"></i>Enregistrer la règle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Bootstrap 5 pour la modification de règle -->
<div class="modal fade" id="editRuleModal" tabindex="-1" aria-labelledby="editRuleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold text-dark" id="editRuleModalLabel">
                    <i class="fa-solid fa-pencil text-primary me-2"></i>Modifier la règle de codification
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="editRuleForm" novalidate>
                <input type="hidden" name="id" id="edit-rule-id">
                <div class="modal-body px-4 py-3">
                    <p class="text-muted small">Modifiez le terme sensible ou son code de substitution.</p>
                    
                    <div id="editModalAlert" class="alert alert-danger border-0 rounded-3 small py-2 px-3 mb-3 d-none">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i><span id="editModalAlertText"></span>
                    </div>

                    <div class="mb-3">
                        <label for="edit-term" class="form-label small fw-semibold">Terme sensible (ex: FARDC, Wazalendo)*</label>
                        <input type="text" class="form-control rounded-3" name="term" id="edit-term" required placeholder="Entrez le mot à anonymiser...">
                        <div class="invalid-feedback">Veuillez spécifier le terme sensible.</div>
                    </div>

                    <div class="mb-3">
                        <label for="edit-code" class="form-label small fw-semibold">Code de remplacement (ex: GA003, GA002)*</label>
                        <input type="text" class="form-control rounded-3" name="replacement_code" id="edit-code" required placeholder="Entrez le code de substitution...">
                        <div class="invalid-feedback">Veuillez spécifier le code de remplacement.</div>
                    </div>

                    <div class="mb-3">
                        <label for="edit-active" class="form-label small fw-semibold">Statut*</label>
                        <select class="form-select rounded-3" name="is_active" id="edit-active" required>
                            <option value="1">Actif</option>
                            <option value="0">Inactif</option>
                        </select>
                        <div class="invalid-feedback">Veuillez spécifier le statut de la règle.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-1">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-sydra-primary rounded-pill px-4" id="btnSubmitEditRule">
                        <i class="fa-solid fa-check me-1"></i>Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Bootstrap 5 pour la suppression de règle -->
<div class="modal fade" id="deleteRuleModal" tabindex="-1" aria-labelledby="deleteRuleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold text-dark" id="deleteRuleModalLabel">
                    <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>Confirmer la suppression
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <div class="alert alert-warning border-0 rounded-3 small mb-3">
                    <h6 class="fw-bold mb-1"><i class="fa-solid fa-circle-exclamation me-1"></i>Attention : Action irréversible</h6>
                    <p class="mb-0">Vous êtes sur le point de supprimer la règle de codification pour le terme : <strong id="delete-rule-term" class="text-danger"></strong>.</p>
                </div>
                
                <div id="deleteModalAlert" class="alert alert-danger border-0 rounded-3 small py-2 px-3 mb-3 d-none">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i><span id="deleteModalAlertText"></span>
                </div>

                <div class="small text-muted mb-3">
                    <p class="mb-2"><strong>Ce que cela implique :</strong></p>
                    <ul class="ps-3 mb-0">
                        <li class="mb-1"><strong>Non-rétroactivité :</strong> Les rapports existants déjà codifiés avec cette règle restent anonymisés. Leurs données ne seront pas rétablies.</li>
                        <li class="mb-1"><strong>Nouvelles données :</strong> Les futurs rapports soumis contenant ce terme ne seront plus interceptés ni masqués par cette règle.</li>
                        <li><strong>Synchronisation PWA Hors-Ligne :</strong> Les terminaux mobiles utilisant le mode hors-ligne doivent actualiser leur cache (connexion requise) pour appliquer ce retrait lors des saisies.</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-1">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger rounded-pill px-4" id="btnConfirmDeleteRule">
                    <i class="fa-solid fa-trash-can me-1"></i>Supprimer définitivement
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.btn-sydra-primary {
    background-color: #005bbb !important;
    border-color: #005bbb !important;
    color: #ffffff !important;
}
.btn-sydra-primary:hover, .btn-sydra-primary:focus, .btn-sydra-primary:active {
    background-color: #004799 !important;
    border-color: #004799 !important;
    color: #ffffff !important;
}
.btn-outline-sydra-primary {
    color: #005bbb !important;
    border-color: #005bbb !important;
    background-color: transparent !important;
}
.btn-outline-sydra-primary:hover, .btn-outline-sydra-primary:focus, .btn-outline-sydra-primary:active {
    background-color: #005bbb !important;
    border-color: #005bbb !important;
    color: #ffffff !important;
}
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
/* Layout et alignement des contrôles */
#rules-table_wrapper,
#logs-table_wrapper {
    position: relative;
    clear: both;
}

#rules-table_wrapper::after,
#logs-table_wrapper::after {
    content: "";
    display: table;
    clear: both;
}

#rules-table_wrapper .dataTables_length,
#rules-table_wrapper .dt-length,
#logs-table_wrapper .dataTables_length,
#logs-table_wrapper .dt-length {
    float: left;
    margin-bottom: 1rem;
    display: inline-flex;
    align-items: center;
}

#rules-table_wrapper .dataTables_filter,
#rules-table_wrapper .dt-search,
#logs-table_wrapper .dataTables_filter,
#logs-table_wrapper .dt-search {
    float: right;
    margin-bottom: 1rem;
    display: inline-flex;
    align-items: center;
}

/* Style du champ de recherche */
#rules-table_wrapper .dataTables_filter label,
#rules-table_wrapper .dt-search label,
#logs-table_wrapper .dataTables_filter label,
#logs-table_wrapper .dt-search label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    font-size: 0.85rem;
    color: #475569;
    font-weight: 500;
}

#rules-table_wrapper .dataTables_filter input,
#rules-table_wrapper .dt-search input,
#logs-table_wrapper .dataTables_filter input,
#logs-table_wrapper .dt-search input {
    border-radius: 30px;
    border: 1.5px solid #d1d5db;
    padding: 7px 14px 7px 38px;
    font-size: 0.85rem;
    outline: none;
    transition: all 0.25s ease-in-out;
    background-color: #f8fafc;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: 14px center;
    background-size: 16px 16px;
    width: 240px;
    color: #1e293b;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
}

#rules-table_wrapper .dataTables_filter input:focus,
#rules-table_wrapper .dt-search input:focus,
#logs-table_wrapper .dataTables_filter input:focus,
#logs-table_wrapper .dt-search input:focus {
    border-color: #005bbb;
    background-color: #ffffff;
    box-shadow: 0 0 0 3px rgba(0, 91, 187, 0.15);
    width: 290px;
}

/* Style du select de longueur (page length) */
#rules-table_wrapper .dataTables_length label,
#rules-table_wrapper .dt-length label,
#logs-table_wrapper .dataTables_length label,
#logs-table_wrapper .dt-length label {
    display: inline-flex;
    align-items: center;
    margin: 0;
    font-size: 0.85rem;
    color: #475569;
    font-weight: 500;
}

#rules-table_wrapper .dataTables_length select,
#rules-table_wrapper .dt-length select,
#logs-table_wrapper .dataTables_length select,
#logs-table_wrapper .dt-length select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 12px 12px;
    padding: 6px 28px 6px 12px;
    border-radius: 8px;
    border: 1.5px solid #d1d5db;
    font-size: 0.85rem;
    outline: none;
    background-color: #f8fafc;
    transition: all 0.2s ease-in-out;
    cursor: pointer;
    color: #1e293b;
    margin: 0 8px;
    min-width: 65px;
}

#rules-table_wrapper .dataTables_length select:focus,
#rules-table_wrapper .dt-length select:focus,
#logs-table_wrapper .dataTables_length select:focus,
#logs-table_wrapper .dt-length select:focus {
    border-color: #005bbb;
    background-color: #ffffff;
    box-shadow: 0 0 0 3px rgba(0, 91, 187, 0.15);
}

/* Harmonisation des tables et de la pagination */
#rules-table td, #rules-table th,
#logs-table td, #logs-table th {
    vertical-align: middle;
    font-size: 0.85rem;
    padding: 10px 12px;
}

/* Alignement du footer (info & pagination) */
#rules-table_wrapper .dataTables_info,
#rules-table_wrapper .dt-info,
#logs-table_wrapper .dataTables_info,
#logs-table_wrapper .dt-info {
    float: left;
    margin-top: 1rem;
    font-size: 0.8rem;
    color: #64748b;
}

#rules-table_wrapper .dataTables_paginate,
#rules-table_wrapper .dt-paging,
#logs-table_wrapper .dataTables_paginate,
#logs-table_wrapper .dt-paging {
    float: right;
    margin-top: 1rem;
}

/* Pagination modernisée */
.dt-paging, .dataTables_paginate {
    display: inline-flex;
    gap: 4px;
}

.dt-paging .dt-paging-button,
.dataTables_paginate .paginate_button {
    border-radius: 8px !important;
    border: 1px solid #d1d5db !important;
    padding: 6px 12px !important;
    font-size: 0.8rem !important;
    background: #ffffff !important;
    color: #475569 !important;
    transition: all 0.15s ease !important;
    cursor: pointer !important;
}

.dt-paging .dt-paging-button:hover,
.dataTables_paginate .paginate_button:hover {
    background: #f1f5f9 !important;
    color: #0f172a !important;
    border-color: #cbd5e1 !important;
}

.dt-paging .dt-paging-button.current,
.dt-paging .dt-paging-button.active,
.dataTables_paginate .paginate_button.current {
    background: #005bbb !important;
    color: #ffffff !important;
    border-color: #005bbb !important;
}

.dt-paging .dt-paging-button.disabled,
.dataTables_paginate .paginate_button.disabled {
    background: #f8fafc !important;
    color: #cbd5e1 !important;
    border-color: #e2e8f0 !important;
    cursor: not-allowed !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
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
    var csrfMeta   = document.querySelector('meta[name="csrf-token"]');
    var csrfToken  = csrfMeta ? String(csrfMeta.getAttribute('content') || '') : '';

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

    // ── Radio Cards Provider ────────────────────────────────────────────────
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
            })
            .catch(function (error) {
                showToast('error', error.message || 'Erreur de chargement des paramètres.');
            });
    }

    // ── Initialisation des tables de codification ─────────────────────────
    if (pageAccess.canSecurity && window.$ && $.fn.DataTable) {
        $('#rules-table').DataTable({
            language: {
                emptyTable: "Aucune règle disponible dans le dictionnaire",
                info: "Affichage de la règle _START_ à _END_ sur _TOTAL_ règles",
                infoEmpty: "Affichage de 0 à 0 sur 0 règle",
                infoFiltered: "(filtré à partir de _MAX_ règles au total)",
                lengthMenu: "Afficher _MENU_ règles",
                search: "",
                searchPlaceholder: "Rechercher une règle...",
                zeroRecords: "Aucune règle correspondante trouvée",
                paginate: {
                    first: "Premier",
                    last: "Dernier",
                    next: "Suivant",
                    previous: "Précédent"
                }
            },
            order: [[0, 'asc']],
            pageLength: 10,
            lengthMenu: [10, 25, 50]
        });
        $('#logs-table').DataTable({
            language: {
                emptyTable: "Aucune interception récente dans l'audit",
                info: "Affichage du log _START_ à _END_ sur _TOTAL_ logs",
                infoEmpty: "Affichage de 0 à 0 sur 0 log",
                infoFiltered: "(filtré à partir de _MAX_ logs au total)",
                lengthMenu: "Afficher _MENU_ logs",
                search: "",
                searchPlaceholder: "Rechercher un log...",
                zeroRecords: "Aucune interception correspondante trouvée",
                paginate: {
                    first: "Premier",
                    last: "Dernier",
                    next: "Suivant",
                    previous: "Précédent"
                }
            },
            order: [[0, 'desc']],
            pageLength: 5,
            searching: true,
            lengthChange: false
        });
    }

    // --- GESTION DE L'AJOUT ---
    const addForm = document.getElementById('addRuleForm');
    const addModalEl = document.getElementById('addRuleModal');
    const addSubmitBtn = document.getElementById('btnSubmitRule');
    const addAlert = document.getElementById('modalAlert');
    const addAlertText = document.getElementById('modalAlertText');

    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!addForm.checkValidity()) {
                addForm.classList.add('was-validated');
                return;
            }

            addSubmitBtn.disabled = true;
            addSubmitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Enregistrement...';
            addAlert.classList.add('d-none');

            const termVal = document.getElementById('modal-term').value.trim();
            const codeVal = document.getElementById('modal-code').value.trim();

            $.ajax({
                url: 'api/add_codification_rule.php',
                type: 'POST',
                data: {
                    term: termVal,
                    replacement_code: codeVal,
                    csrf: csrfToken
                },
                dataType: 'json',
                success: function (res) {
                    addSubmitBtn.disabled = false;
                    addSubmitBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i>Enregistrer la règle';

                    if (res && res.success === true) {
                        if (typeof toastr !== 'undefined') {
                            toastr.success(res.message || 'Règle ajoutée avec succès.');
                        }
                        
                        if (window.bootstrap && window.bootstrap.Modal) {
                            const modal = window.bootstrap.Modal.getInstance(addModalEl) || new window.bootstrap.Modal(addModalEl);
                            modal.hide();
                        }
                        
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        addAlertText.textContent = res.message || 'Une erreur est survenue.';
                        addAlert.classList.remove('d-none');
                    }
                },
                error: function (xhr, status, error) {
                    addSubmitBtn.disabled = false;
                    addSubmitBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i>Enregistrer la règle';
                    addAlertText.textContent = 'Erreur réseau : ' + error;
                    addAlert.classList.remove('d-none');
                }
            });
        });
    }

    // --- GESTION DE LA MODIFICATION ---
    const editForm = document.getElementById('editRuleForm');
    const editModalEl = document.getElementById('editRuleModal');
    const editSubmitBtn = document.getElementById('btnSubmitEditRule');
    const editAlert = document.getElementById('editModalAlert');
    const editAlertText = document.getElementById('editModalAlertText');

    // Délégation d'événement pour le bouton de modification (compatible DataTables)
    $('#rules-table').on('click', '.js-edit-rule-btn', function () {
        const id = $(this).data('id');
        const term = $(this).data('term');
        const code = $(this).data('code');
        const active = $(this).data('active');

        document.getElementById('edit-rule-id').value = id;
        document.getElementById('edit-term').value = term;
        document.getElementById('edit-code').value = code;
        document.getElementById('edit-active').value = active;

        editAlert.classList.add('d-none');
        editForm.classList.remove('was-validated');

        if (window.bootstrap && window.bootstrap.Modal) {
            const modal = window.bootstrap.Modal.getInstance(editModalEl) || new window.bootstrap.Modal(editModalEl);
            modal.show();
        }
    });

    if (editForm) {
        editForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!editForm.checkValidity()) {
                editForm.classList.add('was-validated');
                return;
            }

            editSubmitBtn.disabled = true;
            editSubmitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Enregistrement...';
            editAlert.classList.add('d-none');

            const idVal = document.getElementById('edit-rule-id').value;
            const termVal = document.getElementById('edit-term').value.trim();
            const codeVal = document.getElementById('edit-code').value.trim();
            const activeVal = document.getElementById('edit-active').value;

            $.ajax({
                url: 'api/edit_codification_rule.php',
                type: 'POST',
                data: {
                    id: idVal,
                    term: termVal,
                    replacement_code: codeVal,
                    is_active: activeVal,
                    csrf: csrfToken
                },
                dataType: 'json',
                success: function (res) {
                    editSubmitBtn.disabled = false;
                    editSubmitBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i>Enregistrer les modifications';

                    if (res && res.success === true) {
                        if (typeof toastr !== 'undefined') {
                            toastr.success(res.message || 'Règle modifiée avec succès.');
                        }
                        
                        if (window.bootstrap && window.bootstrap.Modal) {
                            const modal = window.bootstrap.Modal.getInstance(editModalEl) || new window.bootstrap.Modal(editModalEl);
                            modal.hide();
                        }
                        
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        editAlertText.textContent = res.message || 'Une erreur est survenue.';
                        editAlert.classList.remove('d-none');
                    }
                },
                error: function (xhr, status, error) {
                    editSubmitBtn.disabled = false;
                    editSubmitBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i>Enregistrer les modifications';
                    editAlertText.textContent = 'Erreur réseau : ' + error;
                    editAlert.classList.remove('d-none');
                }
            });
        });
    }

    // --- GESTION DE LA SUPPRESSION ---
    let deleteRuleId = null;
    const deleteModalEl = document.getElementById('deleteRuleModal');
    const deleteConfirmBtn = document.getElementById('btnConfirmDeleteRule');
    const deleteAlert = document.getElementById('deleteModalAlert');
    const deleteAlertText = document.getElementById('deleteModalAlertText');

    // Délégation d'événement pour le bouton de suppression (compatible DataTables)
    $('#rules-table').on('click', '.js-delete-rule-btn', function () {
        deleteRuleId = $(this).data('id');
        const term = $(this).data('term');

        document.getElementById('delete-rule-term').textContent = term;
        deleteAlert.classList.add('d-none');

        if (window.bootstrap && window.bootstrap.Modal) {
            const modal = window.bootstrap.Modal.getInstance(deleteModalEl) || new window.bootstrap.Modal(deleteModalEl);
            modal.show();
        }
    });

    if (deleteConfirmBtn) {
        deleteConfirmBtn.addEventListener('click', function () {
            if (!deleteRuleId) return;

            deleteConfirmBtn.disabled = true;
            deleteConfirmBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Suppression...';
            deleteAlert.classList.add('d-none');

            $.ajax({
                url: 'api/delete_codification_rule.php',
                type: 'POST',
                data: {
                    id: deleteRuleId,
                    csrf: csrfToken
                },
                dataType: 'json',
                success: function (res) {
                    deleteConfirmBtn.disabled = false;
                    deleteConfirmBtn.innerHTML = '<i class="fa-solid fa-trash-can me-1"></i>Supprimer définitivement';

                    if (res && res.success === true) {
                        if (typeof toastr !== 'undefined') {
                            toastr.success(res.message || 'Règle supprimée avec succès.');
                        }
                        
                        if (window.bootstrap && window.bootstrap.Modal) {
                            const modal = window.bootstrap.Modal.getInstance(deleteModalEl) || new window.bootstrap.Modal(deleteModalEl);
                            modal.hide();
                        }
                        
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        deleteAlertText.textContent = res.message || 'Une erreur est survenue.';
                        deleteAlert.classList.remove('d-none');
                    }
                },
                error: function (xhr, status, error) {
                    deleteConfirmBtn.disabled = false;
                    deleteConfirmBtn.innerHTML = '<i class="fa-solid fa-trash-can me-1"></i>Supprimer définitivement';
                    deleteAlertText.textContent = 'Erreur réseau : ' + error;
                    deleteAlert.classList.remove('d-none');
                }
            });
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
});
</script>

