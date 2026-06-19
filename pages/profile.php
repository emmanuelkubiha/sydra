<?php
/** @var array<string, mixed> $authUser */
if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return '';
    }
}

$orgName = trim((string) ($authUser['organization_name'] ?? $authUser['full_name'] ?? 'Organisation'));
$orgBio = trim((string) ($authUser['bio_organisation'] ?? $authUser['bio'] ?? ''));
$orgPhone = trim((string) ($authUser['telephone_organisation'] ?? $authUser['phone'] ?? ''));
$orgWebsite = trim((string) ($authUser['site_web'] ?? ''));
$orgLogo = trim((string) ($authUser['logo_path'] ?? $authUser['avatar_path'] ?? ''));

$orgInitials = 'OG';
if ($orgName !== '') {
    $parts = preg_split('/\s+/', $orgName) ?: [];
    $first = isset($parts[0][0]) ? strtoupper((string) $parts[0][0]) : '';
    $second = isset($parts[1][0]) ? strtoupper((string) $parts[1][0]) : '';
    $orgInitials = ($first . $second) !== '' ? ($first . $second) : 'OG';
}

$currentLang = function_exists('current_lang') ? current_lang() : ($lang ?? 'fr');
?>

<style>
/* Forcer la couleur bleue de marque SyDRA (#005bbb) sur la page profil */
.profile-shell .btn-primary {
    background: #005bbb !important;
    border-color: #005bbb !important;
    color: #ffffff !important;
}
.profile-shell .btn-primary:hover {
    background: #004a96 !important;
    border-color: #004a96 !important;
}
.profile-shell .btn-outline-primary {
    color: #005bbb !important;
    border-color: #005bbb !important;
}
.profile-shell .btn-outline-primary:hover {
    color: #ffffff !important;
    background: #005bbb !important;
    border-color: #005bbb !important;
}
.profile-shell .text-primary {
    color: #005bbb !important;
}
.profile-shell .btn-outline-secondary {
    color: #005bbb !important;
    border-color: #e2e8f0 !important;
}
.profile-shell .btn-outline-secondary:hover {
    background-color: #f8fafc !important;
    border-color: #cbd5e1 !important;
    color: #004a96 !important;
}
</style>

<div class="container profile-shell py-4">
    <!-- MUST COMPLETE PROFILE BANNER -->
    <?php if (isset($_GET['must_complete_profile']) && $_GET['must_complete_profile'] === '1'): ?>
        <div class="alert alert-warning border-0 rounded-4 shadow-sm p-3 mb-4 d-flex align-items-center gap-3">
            <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;">
                <i class="fa-solid fa-triangle-exclamation fs-5"></i>
            </div>
            <div>
                <strong class="text-dark d-block">Configuration requise</strong>
                <span class="text-secondary small">Veuillez compléter le nom d'affichage de l'organisation, le numéro de téléphone et la biographie pour activer pleinement votre compte.</span>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- COLONNE GAUCHE: IDENTITÉ DE L'ORGANISATION -->
        <div class="col-lg-4">
            <div class="card profile-org-card shadow-sm border-0 rounded-4 overflow-hidden h-100 bg-white">
                <!-- Cover Banner -->
                <div style="height: 120px; background: linear-gradient(135deg, #005bbb 0%, #004a96 100%);"></div>
                
                <div class="card-body pt-0 text-center px-4 pb-4 position-relative">
                    <!-- Logo Floating -->
                    <div class="position-relative d-inline-block" style="margin-top: -60px; z-index: 5;">
                        <div class="profile-org-logo-wrap-lg rounded-circle overflow-hidden border border-4 border-white shadow-sm bg-white" style="width: 110px; height: 110px; margin: 0 auto;">
                            <?php if ($orgLogo !== ''): ?>
                                <img id="profile-org-logo-view" class="w-100 h-100" style="object-fit: cover;" src="<?= htmlspecialchars($orgLogo, ENT_QUOTES, 'UTF-8'); ?>" alt="Logo organisation">
                                <div id="profile-org-fallback-view" class="profile-org-fallback rounded-circle d-none"><?= htmlspecialchars($orgInitials, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php else: ?>
                                <div id="profile-org-fallback-view" class="profile-org-fallback rounded-circle"><?= htmlspecialchars($orgInitials, ENT_QUOTES, 'UTF-8'); ?></div>
                                <img id="profile-org-logo-view" class="w-100 h-100 d-none" style="object-fit: cover;" src="" alt="Logo organisation">
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <h3 class="fw-bold text-dark mt-3 mb-1" id="profile-org-name-sidebar"><?= htmlspecialchars($orgName, ENT_QUOTES, 'UTF-8'); ?></h3>
                    <span class="badge rounded-pill bg-light text-primary px-3 py-2 border mb-3" style="font-size: 0.75rem; font-weight: 600; border-color: #cbd5e1 !important;"><i class="fa-solid fa-building me-1"></i> Organisation partenaire</span>
                    
                    <p class="text-secondary small mb-4 px-2" id="profile-org-bio-sidebar" style="line-height: 1.4; min-height: 50px;">
                        <?= $orgBio !== '' ? nl2br(htmlspecialchars($orgBio, ENT_QUOTES, 'UTF-8')) : 'Aucune biographie organisationnelle renseignée.'; ?>
                    </p>
                    
                    <hr class="text-muted opacity-25">
                    
                    <!-- Contacts & Links -->
                    <div class="text-start mt-4 px-2">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; color: #005bbb; flex-shrink: 0;">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <div>
                                <small class="text-secondary d-block" style="font-size: 0.75rem; font-weight: 500;">Téléphone</small>
                                <span class="fw-semibold text-dark small" id="profile-org-phone-sidebar"><?= htmlspecialchars($orgPhone !== '' ? $orgPhone : '-', ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; color: #005bbb; flex-shrink: 0;">
                                <i class="fa-solid fa-globe"></i>
                            </div>
                            <div>
                                <small class="text-secondary d-block" style="font-size: 0.75rem; font-weight: 500;">Site web</small>
                                <?php if ($orgWebsite !== ''): ?>
                                    <a id="profile-org-web-sidebar" href="<?= htmlspecialchars($orgWebsite, ENT_QUOTES, 'UTF-8'); ?>" class="fw-semibold text-primary text-decoration-none small text-break" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($orgWebsite, ENT_QUOTES, 'UTF-8'); ?></a>
                                <?php else: ?>
                                    <span id="profile-org-web-sidebar" class="fw-semibold text-secondary small">-</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- COLONNE DROITE: INTERFACES DYNAMIQUES -->
        <div class="col-lg-8">
            <!-- MODE VISUALISATION (Sécurité + Préférences) -->
            <div id="profile-view" class="d-flex flex-column gap-4">
                <!-- Banner bascule édition -->
                <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
                    <div class="row align-items-center g-3">
                        <div class="col-md-8 text-center text-md-start">
                            <h4 class="fw-bold text-dark mb-1">Coordonnées de l'organisation</h4>
                            <p class="text-secondary small mb-0">Modifiez le nom, la biographie, les contacts et le logo de l'organisation.</p>
                        </div>
                        <div class="col-md-4 text-center text-md-end">
                            <button type="button" id="btn-edit-profile" class="btn btn-primary rounded-pill px-4 shadow-sm"><i class="fa-solid fa-pen-to-square me-2"></i>Modifier le profil</button>
                        </div>
                    </div>
                </div>
                
                <!-- Mot de passe / Sécurité -->
                <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
                    <h4 class="profile-section-title fw-bold mb-3 d-flex align-items-center gap-2"><i class="fa-solid fa-shield-halved text-primary"></i> Sécurité du compte</h4>
                    <p class="text-secondary small mb-4">Mettez à jour le mot de passe de votre compte régulièrement pour sécuriser l'accès aux rapports.</p>
                    
                    <?php if (isset($_GET['must_change_password']) && $_GET['must_change_password'] === '1'): ?>
                        <div class="alert alert-danger border-0 rounded-3 small mb-3"><i class="fa-solid fa-triangle-exclamation me-2"></i> Action requise: vous devez changer votre mot de passe pour continuer en toute sécurité.</div>
                    <?php endif; ?>
                    
                    <form method="post" action="?page=profil">
                        <input type="hidden" name="action" value="change_password">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Mot de passe actuel</label>
                            <div class="password-field">
                                <input class="form-control rounded-pill px-3" type="password" name="current_password" id="current-password" required>
                                <button class="password-toggle" type="button" data-toggle-password="current-password" aria-label="Afficher le mot de passe">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>
                
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-semibold text-secondary">Nouveau mot de passe</label>
                                <div class="password-field">
                                    <input class="form-control rounded-pill px-3" type="password" name="new_password" id="new-password" required>
                                    <button class="password-toggle" type="button" data-toggle-password="new-password" aria-label="Afficher le mot de passe">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                    
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-semibold text-secondary">Confirmer le nouveau mot de passe</label>
                                <div class="password-field">
                                    <input class="form-control rounded-pill px-3" type="password" name="new_password_confirmation" id="new-password-confirmation" required>
                                    <button class="password-toggle" type="button" data-toggle-password="new-password-confirmation" aria-label="Afficher le mot de passe">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                
                        <button class="btn btn-primary rounded-pill px-4 shadow-sm mt-2" type="submit">Changer le mot de passe</button>
                    </form>

                    <hr class="my-4 text-muted opacity-25">
                    
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div style="flex: 1; min-width: 250px;">
                            <strong class="text-dark d-block small fw-bold">Vous avez oublié votre mot de passe actuel ?</strong>
                            <span class="text-secondary small">Vous pouvez recevoir un lien de réinitialisation sécurisé par email pour le redéfinir sans saisir votre mot de passe actuel.</span>
                            <?php if (isset($config['mail']['smtp_host']) && trim((string) $config['mail']['smtp_host']) === ''): ?>
                                <div class="text-danger small mt-1"><i class="fa-solid fa-triangle-exclamation me-1"></i> SMTP non configuré : les emails de réinitialisation ne pourront pas être envoyés.</div>
                            <?php endif; ?>
                        </div>
                        <form method="post" action="?page=profil" style="margin: 0;">
                            <input type="hidden" name="action" value="request_password_reset_from_profile">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="btn btn-outline-primary rounded-pill px-4 btn-sm fw-semibold">
                                <i class="fa-regular fa-envelope me-1"></i> Recevoir un lien
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Préférences -->
                <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
                    <h4 class="profile-section-title fw-bold mb-3 d-flex align-items-center gap-2"><i class="fa-solid fa-globe text-primary"></i> Préférences linguistiques</h4>
                    <form action="" method="get">
                        <input type="hidden" name="page" value="profil">
                        <div class="mb-1">
                            <label class="form-label small fw-semibold text-secondary">Langue de l'interface / Interface Language</label>
                            <select name="lang" class="form-select rounded-pill px-3" onchange="this.form.submit()">
                                <option value="fr" <?= $currentLang === 'fr' ? 'selected' : ''; ?>>Français</option>
                                <option value="en" <?= $currentLang === 'en' ? 'selected' : ''; ?>>English</option>
                            </select>
                            <small class="text-muted mt-2 d-block">La sélection sera appliquée immédiatement à l'ensemble du système.</small>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- MODE ÉDITION (Formulaire) -->
            <div id="profile-edit" class="d-none">
                <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-4 border-bottom pb-3">
                        <h3 class="fw-bold mb-0 text-dark h5"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i>Modifier les informations</h3>
                        <button type="button" id="btn-cancel-edit" class="btn btn-outline-secondary rounded-pill px-4">Retour</button>
                    </div>
            
                    <!-- Modifier Logo / Image de profil -->
                    <div class="profile-logo-current p-3 border rounded-4 bg-light mb-4 d-flex align-items-center gap-3">
                        <div class="profile-logo-current-media rounded-circle overflow-hidden bg-white shadow-sm border" style="width: 90px; height: 90px; flex-shrink: 0;">
                            <?php if ($orgLogo !== ''): ?>
                                <img id="profile-org-logo-edit" class="w-100 h-100" style="object-fit: cover;" src="<?= htmlspecialchars($orgLogo, ENT_QUOTES, 'UTF-8'); ?>" alt="Logo organisation actuel">
                                <div id="profile-org-fallback-edit" class="profile-org-fallback rounded-circle d-none" style="font-size: 24px;"><?= htmlspecialchars($orgInitials, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php else: ?>
                                <div id="profile-org-fallback-edit" class="profile-org-fallback rounded-circle" style="font-size: 24px;"><?= htmlspecialchars($orgInitials, ENT_QUOTES, 'UTF-8'); ?></div>
                                <img id="profile-org-logo-edit" class="w-100 h-100 d-none" style="object-fit: cover;" src="" alt="Logo organisation actuel">
                            <?php endif; ?>
                        </div>
            
                        <div class="profile-logo-current-actions flex-grow-1">
                            <span class="profile-logo-current-label d-block small fw-bold text-secondary text-uppercase mb-1">Image ou Logo de l'organisation</span>
                            <button type="button" id="btn-select-logo" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm"><i class="fa-solid fa-camera me-1"></i> Sélectionner une image</button>
                            <input class="d-none" type="file" id="logo-input" accept="image/jpeg,image/png,image/webp">
                            <div class="text-muted mt-2" style="font-size: 0.72rem; line-height: 1.3;">Le rognage s'ouvre automatiquement. Le logo sera enregistré directement après validation.</div>
                            <small class="text-success small fw-semibold" id="logo-upload-status"></small>
                        </div>
                    </div>
            
                    <!-- Formulaire -->
                    <form method="post" action="?page=profil" id="profile-edit-form">
                        <input type="hidden" name="action" value="update_profile">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-semibold text-secondary">Nom d'affichage (Français)</label>
                                <input class="form-control rounded-pill px-3" name="organization_display_name" value="<?= htmlspecialchars($orgName, ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-semibold text-secondary">Nom administratif / Abrégé</label>
                                <input class="form-control rounded-pill px-3" name="organization_name" value="<?= htmlspecialchars((string) ($authUser['organization_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-semibold text-secondary">Téléphone officiel de l'organisation</label>
                                <input class="form-control rounded-pill px-3" name="telephone_organisation" value="<?= htmlspecialchars($orgPhone, ENT_QUOTES, 'UTF-8'); ?>" placeholder="+243...">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-semibold text-secondary">Site web officiel</label>
                                <input class="form-control rounded-pill px-3" name="site_web" value="<?= htmlspecialchars($orgWebsite, ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://...">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label small fw-semibold text-secondary">Biographie et Mission de l'organisation</label>
                                <textarea class="form-control rounded-4 p-3" name="bio_organisation" rows="4" placeholder="Présentation, mandat, zones d'intervention..."><?= htmlspecialchars($orgBio, ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                        </div>
            
                        <div class="d-flex gap-2 mt-3 justify-content-end">
                            <button type="button" class="btn btn-light rounded-pill px-4 border js-btn-cancel-edit">Annuler</button>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Enregistrer les informations</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="cropperModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Recadrer le logo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="cropper-container-wrap">
                    <img id="cropper-image" src="" alt="Image à rogner" style="max-width:100%; display:block;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="btn-confirm-crop" class="btn btn-primary rounded-pill px-4">Valider et téléverser</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var view = document.getElementById('profile-view');
    var edit = document.getElementById('profile-edit');
    var btnEdit = document.getElementById('btn-edit-profile');
    var btnSelectLogo = document.getElementById('btn-select-logo');
    var logoInput = document.getElementById('logo-input');
    var logoStatus = document.getElementById('logo-upload-status');

    function showEditMode() {
        if (view) {
            view.classList.add('d-none');
        }
        if (edit) {
            edit.classList.remove('d-none');
        }
    }

    function showViewMode() {
        if (edit) {
            edit.classList.add('d-none');
        }
        if (view) {
            view.classList.remove('d-none');
        }
    }

    if (btnEdit) {
        btnEdit.addEventListener('click', showEditMode);
    }
    
    // Bind all cancel buttons
    var cancels = document.querySelectorAll('.js-btn-cancel-edit, #btn-cancel-edit');
    cancels.forEach(function(btn) {
        btn.addEventListener('click', showViewMode);
    });

    if (btnSelectLogo && logoInput) {
        btnSelectLogo.addEventListener('click', function () {
            logoInput.click();
        });
    }

    var cropperImage = document.getElementById('cropper-image');
    var btnConfirmCrop = document.getElementById('btn-confirm-crop');
    var logoView = document.getElementById('profile-org-logo-view');
    var logoEdit = document.getElementById('profile-org-logo-edit');
    var fallbackView = document.getElementById('profile-org-fallback-view');
    var fallbackEdit = document.getElementById('profile-org-fallback-edit');

    var cropper = null;
    var cropperModalEl = document.getElementById('cropperModal');
    var cropperModal = null;

    function getCropperModal() {
        if (!cropperModalEl) {
            return null;
        }
        if (cropperModal) {
            return cropperModal;
        }
        if (window.bootstrap && window.bootstrap.Modal) {
            cropperModal = new window.bootstrap.Modal(cropperModalEl);
        }
        return cropperModal;
    }

    function destroyCropper() {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
    }

    function setStatus(message, isError) {
        if (!logoStatus) {
            return;
        }
        logoStatus.textContent = message || '';
        logoStatus.classList.toggle('text-danger', !!isError);
    }

    function applyLogo(path) {
        var src = path + '?v=' + Date.now();
        if (logoView) {
            logoView.src = src;
            logoView.classList.remove('d-none');
        }
        if (logoEdit) {
            logoEdit.src = src;
            logoEdit.classList.remove('d-none');
        }
        if (fallbackView) {
            fallbackView.classList.add('d-none');
        }
        if (fallbackEdit) {
            fallbackEdit.classList.add('d-none');
        }
    }

    function uploadCroppedBlob(blob) {
        var csrfInput = document.querySelector('#profile-edit-form input[name="csrf"]');
        var csrf = csrfInput ? csrfInput.value : '';
        if (!csrf) {
            setStatus('Jeton CSRF introuvable.', true);
            return;
        }

        setStatus('Téléversement du logo en cours...', false);
        if (btnConfirmCrop) {
            btnConfirmCrop.disabled = true;
        }

        var formData = new FormData();
        formData.append('csrf', csrf);
        formData.append('logo', blob, 'logo-cropped.png');

        fetch('actions/upload_logo.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (!data || !data.success) {
                    throw new Error((data && data.message) ? data.message : 'Échec de l\'upload du logo.');
                }

                if (typeof data.logo_path === 'string' && data.logo_path !== '') {
                    applyLogo(data.logo_path);
                }

                setStatus('Logo téléversé avec succès.', false);
                if (window.Swal && typeof window.Swal.fire === 'function') {
                    window.Swal.fire({
                        icon: 'success',
                        title: 'Logo mis à jour',
                        text: data.message || 'Le logo a été enregistré avec succès.',
                        timer: 1800,
                        showConfirmButton: false
                    });
                }
            })
            .catch(function (error) {
                setStatus(error.message || 'Impossible de téléverser le logo.', true);
                if (window.Swal && typeof window.Swal.fire === 'function') {
                    window.Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: error.message || 'Impossible de téléverser le logo.'
                    });
                }
            })
            .finally(function () {
                if (btnConfirmCrop) {
                    btnConfirmCrop.disabled = false;
                }
            });
    }

    if (logoInput) {
        logoInput.addEventListener('change', function (event) {
            var file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
            var runtimeModal = getCropperModal();
            if (!file || !runtimeModal || !cropperImage) {
                if (!runtimeModal) {
                    setStatus('Le module de rognage n\'est pas disponible. Rechargez la page.', true);
                }
                return;
            }

            setStatus('', false);
            var reader = new FileReader();
            reader.onload = function (e) {
                var result = e.target && typeof e.target.result === 'string' ? e.target.result : '';
                if (!result) {
                    return;
                }
                if (typeof window.Cropper !== 'function') {
                    setStatus('Le module de rognage n\'est pas disponible. Rechargez la page.', true);
                    return;
                }
                cropperImage.src = result;
                runtimeModal.show();
                destroyCropper();
                cropper = new Cropper(cropperImage, {
                    aspectRatio: 1 / 1,
                    viewMode: 1,
                    autoCropArea: 1,
                    responsive: true,
                    background: false
                });
            };
            reader.readAsDataURL(file);
        });
    }

    if (btnConfirmCrop) {
        btnConfirmCrop.addEventListener('click', function () {
            if (!cropper) {
                return;
            }

            cropper.getCroppedCanvas({ width: 512, height: 512, fillColor: '#ffffff' }).toBlob(function (blob) {
                if (!blob) {
                    setStatus('Le rognage a échoué.', true);
                    return;
                }

                var runtimeModal = getCropperModal();
                if (runtimeModal) {
                    runtimeModal.hide();
                }
                uploadCroppedBlob(blob);
            }, 'image/png', 0.95);
        });
    }

    if (cropperModalEl) {
        cropperModalEl.addEventListener('hidden.bs.modal', function () {
            destroyCropper();
            if (logoInput) {
                logoInput.value = '';
            }
        });
    }

    Array.prototype.forEach.call(document.querySelectorAll('.password-toggle[data-toggle-password]'), function (button) {
        button.addEventListener('click', function () {
            var targetId = button.getAttribute('data-toggle-password') || '';
            var input = targetId ? document.getElementById(targetId) : null;
            if (!input) {
                return;
            }

            var isPassword = input.getAttribute('type') === 'password';
            input.setAttribute('type', isPassword ? 'text' : 'password');
            button.setAttribute('aria-label', isPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
            button.innerHTML = isPassword
                ? '<i class="fa-regular fa-eye-slash"></i>'
                : '<i class="fa-regular fa-eye"></i>';
        });
    });
})();
</script>
