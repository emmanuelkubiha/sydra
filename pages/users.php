<?php
/** @var array<int, array<string, mixed>> $users */
/** @var array<string, mixed>|null $authUser */
if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return '';
    }
}

$isAdmin = is_array($authUser) && strtoupper((string) ($authUser['role'] ?? '')) === 'ADMIN';

if (!function_exists('format_fr_datetime_readable')) {
    function format_fr_datetime_readable(?string $raw): string
    {
        if (!is_string($raw) || trim($raw) === '') {
            return 'Jamais connecté';
        }

        $ts = strtotime($raw);
        if ($ts === false) {
            return 'Date indisponible';
        }

        $months = [
            '01' => 'Janvier',
            '02' => 'Février',
            '03' => 'Mars',
            '04' => 'Avril',
            '05' => 'Mai',
            '06' => 'Juin',
            '07' => 'Juillet',
            '08' => 'Août',
            '09' => 'Septembre',
            '10' => 'Octobre',
            '11' => 'Novembre',
            '12' => 'Décembre',
        ];

        $monthKey = date('m', $ts);
        $month = $months[$monthKey] ?? date('m', $ts);

        return 'Le ' . date('d', $ts) . ' ' . $month . ' ' . date('Y', $ts) . ' à ' . date('H', $ts) . 'h' . date('i', $ts);
    }
}

if (!function_exists('field_info_tip')) {
    function field_info_tip(string $text): string
    {
        return '<i class="bi bi-info-circle-fill info-tip" data-bs-toggle="tooltip" data-bs-placement="top" title="'
            . htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
            . '"></i>';
    }
}
?>

<div class="card users-list-card">
    <div class="users-header-row">
        <div>
            <h1>Ajout d'organisation</h1>
            <p class="hero-intro">Gestion des utilisateurs, accès et statuts.</p>
        </div>
        <button type="button" class="btn btn-small users-add-btn" data-bs-toggle="modal" data-bs-target="#addUserModal" title="Ajouter une nouvelle organisation dans SyDRA">
            <i class="fa-solid fa-user-plus"></i>
            Ajouter une organisation
        </button>
    </div>

    <div class="users-filters-bar" id="users-filters-bar">
        <div class="users-filter-item users-filter-search">
            <label for="filter-search">Rechercher</label>
            <input type="search" id="filter-search" class="users-search-input" placeholder="Organisation, email, rôle...">
        </div>
        <div class="users-filter-item">
            <label for="filter-role">Filtrer par rôle</label>
            <select id="filter-role">
                <option value="">Tous les rôles</option>
                <option value="ADMIN">Admin</option>
                <option value="CLUSTER_LEADER">Lead GTMP</option>
                <option value="LEAD_GTMP">Lead GTMP (Legacy)</option>
                <option value="GTMP_LEAD">GTMP Lead</option>
                <option value="CLUSTER_CO_LEAD">Co-Lead</option>
                <option value="REPORTER">Reporteur</option>
            </select>
        </div>
        <div class="users-filter-item">
            <label for="filter-status">Filtrer par statut</label>
            <select id="filter-status">
                <option value="">Tous les statuts</option>
                <option value="Actif">Actif</option>
                <option value="Inactif">Inactif</option>
                <option value="En attente de validation email">En attente validation</option>
            </select>
        </div>
    </div>

    <table class="table table-users" id="users-table">
        <thead>
        <tr>
            <th>Profil</th>
            <th>Nom de l'organisation</th>
            <th>Rôle</th>
            <th>Statut</th>
            <th>Dernière connexion</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <?php
            $status = (string) ($u['statut'] ?? (((int) ($u['is_active'] ?? 0) === 1) ? 'Actif' : 'Bloque'));
            $isBlocked = strtolower($status) === 'bloque';
            $pendingMailValidation = (int) ($u['pending_mail_validation'] ?? 0) === 1;
            $isAwaitingValidation = $pendingMailValidation || (int) ($u['is_active'] ?? 0) !== 1;

            $displayName = trim((string) ($u['organization_name'] ?? ''));
            if ($displayName === '') {
                $displayName = (string) ($u['full_name'] ?? 'Organisation');
            }

            $orgAcronym = trim((string) ($u['full_name'] ?? ''));
            $profilePath = trim((string) ($u['logo_path'] ?? $u['avatar_path'] ?? ''));

            $initials = 'OG';
            $parts = preg_split('/\s+/', $displayName) ?: [];
            $first = isset($parts[0][0]) ? strtoupper((string) $parts[0][0]) : '';
            $second = isset($parts[1][0]) ? strtoupper((string) $parts[1][0]) : '';
            if (($first . $second) !== '') {
                $initials = $first . $second;
            }

            $phone = trim((string) ($u['telephone_organisation'] ?? $u['phone'] ?? ''));
            $website = trim((string) ($u['site_web'] ?? ''));
            $bio = trim((string) ($u['bio_organisation'] ?? $u['bio'] ?? ''));
            $lastLogin = format_fr_datetime_readable((string) ($u['last_login_at'] ?? ''));
            $monthlyReports = (int) ($u['monthly_reports'] ?? 0);
            $statusLabel = $isAwaitingValidation ? 'En attente de validation email' : ($isBlocked ? 'Inactif' : 'Actif');
            ?>
            <tr>
                <td>
                    <?php if ($profilePath !== ''): ?>
                        <img src="<?= htmlspecialchars($profilePath, ENT_QUOTES, 'UTF-8'); ?>" alt="Profil" class="user-mini-avatar rounded-circle">
                    <?php else: ?>
                        <span class="user-mini-fallback rounded-circle"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="org-name-cell"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php if ($orgAcronym !== ''): ?>
                        <small class="muted d-block">Acronyme: <?= htmlspecialchars($orgAcronym, ENT_QUOTES, 'UTF-8'); ?></small>
                    <?php endif; ?>
                    <small class="muted"><?= htmlspecialchars((string) ($u['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
                </td>
                <td><?= htmlspecialchars((string) $u['role'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <span class="status-chip <?= $isAwaitingValidation ? 'pending' : ($isBlocked ? 'blocked' : 'active'); ?>">
                        <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($lastLogin, ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <div class="users-actions">
                        <button
                            type="button"
                            class="btn-icon btn-icon-soft js-user-detail"
                            title="Détails"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            data-user-id="<?= (int) $u['id']; ?>"
                            data-user-name="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>"
                            data-user-email="<?= htmlspecialchars((string) ($u['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            data-user-role="<?= htmlspecialchars((string) ($u['role'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            data-user-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>"
                            data-user-bio="<?= htmlspecialchars($bio, ENT_QUOTES, 'UTF-8'); ?>"
                            data-user-phone="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?>"
                            data-user-site="<?= htmlspecialchars($website, ENT_QUOTES, 'UTF-8'); ?>"
                            data-user-logo="<?= htmlspecialchars($profilePath, ENT_QUOTES, 'UTF-8'); ?>"
                            data-user-monthly="<?= (int) $monthlyReports; ?>"
                        >
                            <i class="fa-solid fa-circle-info"></i>
                        </button>

                        <a
                            href="?page=profil&user_id=<?= (int) $u['id']; ?>"
                            class="btn-icon btn-icon-primary"
                            title="Modifier"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                        >
                            <i class="fa-solid fa-pen"></i>
                        </a>

                        <?php if ($isAdmin): ?>
                            <button
                                type="button"
                                class="btn-icon btn-icon-warning js-email-change"
                                title="Modifier l'adresse email"
                                data-bs-toggle="modal"
                                data-bs-target="#emailChangeModal"
                                data-user-id="<?= (int) $u['id']; ?>"
                                data-user-name="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>"
                                data-user-email="<?= htmlspecialchars((string) ($u['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            >
                                <i class="fa-solid fa-envelope-circle-check"></i>
                            </button>

                            <button
                                type="button"
                                class="btn-icon btn-icon-danger js-delete-user"
                                title="Supprimer définitivement"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                data-user-id="<?= (int) $u['id']; ?>"
                                data-user-name="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>"
                                data-csrf="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>"
                            >
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        <?php endif; ?>

                        <button
                            type="button"
                            class="btn-icon <?= $isBlocked ? 'btn-icon-success' : 'btn-icon-danger'; ?> js-toggle-status"
                            title="<?= $isBlocked ? 'Débloquer' : 'Bloquer'; ?>"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            data-user-id="<?= (int) $u['id']; ?>"
                            data-user-status="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>"
                            data-csrf="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>"
                        >
                            <i class="fa-solid <?= $isBlocked ? 'fa-lock-open' : 'fa-user-lock'; ?>"></i>
                        </button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-sm">
            <div class="modal-header">
                <h5 class="modal-title">Ajout d'organisation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p class="users-section-note">
                    <strong>Invitation 48h :</strong> saisissez uniquement l'essentiel. L'organisation finalise son profil à la première connexion.
                </p>

                <form method="post" action="?page=utilisateurs" class="js-create-confirm-form" data-flow-label="Invitation 48h express">
                    <input type="hidden" name="action" value="invite_user">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="grid">
                        <div>
                            <label>Acronyme court de l'organisation <?= field_info_tip('Identifiant court affiché dans les listes.'); ?></label>
                            <input name="org_acronym" required>
                        </div>
                        <div>
                            <label>Email de rapportage <?= field_info_tip('Un lien de validation 48h sera envoyé à cette adresse.'); ?></label>
                            <input type="email" name="email" required>
                        </div>
                    </div>

                    <label>Nom complet long de l'organisation</label>
                    <input name="organization_name" required>

                    <label>Rôle</label>
                    <select name="role" required>
                        <option value="REPORTER">Reporteur</option>
                        <option value="CLUSTER_LEADER">Lead GTMP</option>
                        <option value="CLUSTER_CO_LEAD">Co-Lead</option>
                    </select>

                    <button type="submit">Création du compte</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
<div class="modal fade" id="emailChangeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-sm">
            <div class="modal-header">
                <h5 class="modal-title">Modifier l'adresse email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p class="muted mb-2" id="email-change-target"></p>

                <form method="post" action="?page=utilisateurs">
                    <input type="hidden" name="action" value="request_email_change">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="target_user_id" id="email-change-user-id" value="">

                    <label>Nouvelle adresse email</label>
                    <input type="email" name="new_email" id="email-change-new-email" required>

                    <p class="inline-hint">Un email de confirmation sera envoyé à la nouvelle adresse. Le lien expirera dans quelques heures.</p>

                    <button type="submit">Envoyer la demande de confirmation</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="modal fade" id="userDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-sm">
            <div class="modal-header">
                <h5 class="modal-title">Profil organisation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="user-detail-card">
                    <div class="user-detail-logo-wrap" id="user-detail-logo-wrap"></div>
                    <h3 id="user-detail-name" class="mt-2 mb-1">Organisation</h3>
                    <p class="muted mb-2" id="user-detail-email"></p>

                    <div class="user-detail-meta-grid">
                        <div>
                            <strong>Rôle</strong>
                            <p id="user-detail-role" class="mb-0"></p>
                        </div>
                        <div>
                            <strong>Statut</strong>
                            <p id="user-detail-status" class="mb-0"></p>
                        </div>
                        <div>
                            <strong>Téléphone</strong>
                            <p id="user-detail-phone" class="mb-0"></p>
                        </div>
                        <div>
                            <strong>Site web</strong>
                            <p id="user-detail-site" class="mb-0"></p>
                        </div>
                    </div>

                    <div class="mt-3">
                        <strong>Bio</strong>
                        <p id="user-detail-bio" class="mb-0"></p>
                    </div>

                    <div class="user-detail-kpi mt-3">
                        <span>Statistique rapide</span>
                        <strong id="user-detail-kpi"></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
