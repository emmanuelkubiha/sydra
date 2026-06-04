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
?>

<section class="profile-shell" id="profile-view">
    <div class="card shadow-sm rounded-4 border-0 profile-hero-card">
        <div class="profile-hero">
            <div class="profile-hero-logo-wrap profile-org-logo-wrap-lg">
            <?php if ($orgLogo !== ''): ?>
                <img id="profile-org-logo-view" class="profile-org-logo rounded-4" src="<?= htmlspecialchars($orgLogo, ENT_QUOTES, 'UTF-8'); ?>" alt="Logo organisation">
                <div id="profile-org-fallback-view" class="profile-org-fallback rounded-4 d-none"><?= htmlspecialchars($orgInitials, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php else: ?>
                <div id="profile-org-fallback-view" class="profile-org-fallback rounded-4"><?= htmlspecialchars($orgInitials, ENT_QUOTES, 'UTF-8'); ?></div>
                <img id="profile-org-logo-view" class="profile-org-logo rounded-4 d-none" src="" alt="Logo organisation">
            <?php endif; ?>
        </div>

        <div class="profile-hero-content">
            <span class="profile-kicker">Profil organisation</span>
            <h1 class="mb-1"><?= htmlspecialchars($orgName, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="text-muted mb-0">
                <?= $orgBio !== '' ? nl2br(htmlspecialchars($orgBio, ENT_QUOTES, 'UTF-8')) : 'Aucune biographie organisationnelle renseignée.'; ?>
            </p>

            <div class="profile-org-meta mt-3">
                <div class="profile-meta-item"><strong>Téléphone</strong><span><?= htmlspecialchars($orgPhone !== '' ? $orgPhone : '-', ENT_QUOTES, 'UTF-8'); ?></span></div>
                <div class="profile-meta-item">
                    <strong>Site web</strong>
                    <span>
                        <?php if ($orgWebsite !== ''): ?>
                            <a href="<?= htmlspecialchars($orgWebsite, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($orgWebsite, ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <button type="button" id="btn-edit-profile" class="btn btn-primary mt-3 profile-edit-trigger">Modifier le profil de l'organisation</button>
        </div>
        </div>
    </div>
</section>

<section class="profile-shell d-none" id="profile-edit">
    <div class="card shadow-sm rounded-4 border-0 profile-org-card">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="mb-0"><i class="fa-solid fa-pen-to-square me-1 text-primary"></i>Modifier le profil de l'organisation</h2>
            <button type="button" id="btn-cancel-edit" class="btn btn-outline-secondary">Retour</button>
        </div>

        <div class="profile-logo-current">
            <div class="profile-logo-current-media">
                <?php if ($orgLogo !== ''): ?>
                    <img id="profile-org-logo-edit" class="profile-org-logo rounded-4" src="<?= htmlspecialchars($orgLogo, ENT_QUOTES, 'UTF-8'); ?>" alt="Logo organisation actuel">
                    <div id="profile-org-fallback-edit" class="profile-org-fallback rounded-4 d-none"><?= htmlspecialchars($orgInitials, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php else: ?>
                    <div id="profile-org-fallback-edit" class="profile-org-fallback rounded-4"><?= htmlspecialchars($orgInitials, ENT_QUOTES, 'UTF-8'); ?></div>
                    <img id="profile-org-logo-edit" class="profile-org-logo rounded-4 d-none" src="" alt="Logo organisation actuel">
                <?php endif; ?>
            </div>

            <div class="profile-logo-current-actions">
                <span class="profile-logo-current-label">Image actuelle</span>
                <button type="button" id="btn-select-logo" class="btn btn-outline-primary">Modifier l'image</button>
                <input class="d-none" type="file" id="logo-input" accept="image/jpeg,image/png,image/webp">
                <small class="text-muted">Après sélection, le rognage s'ouvre automatiquement puis le logo est téléversé directement après validation.</small>
                <small class="text-muted" id="logo-upload-status"></small>
            </div>
        </div>

        <form method="post" action="?page=profil" id="profile-edit-form" class="mt-3">
            <input type="hidden" name="action" value="update_profile">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nom de l'organisation</label>
                    <input class="form-control" name="organization_display_name" value="<?= htmlspecialchars($orgName, ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nom administratif (optionnel)</label>
                    <input class="form-control" name="organization_name" value="<?= htmlspecialchars((string) ($authUser['organization_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Téléphone organisation</label>
                    <input class="form-control" name="telephone_organisation" value="<?= htmlspecialchars($orgPhone, ENT_QUOTES, 'UTF-8'); ?>" placeholder="+243...">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Site web</label>
                    <input class="form-control" name="site_web" value="<?= htmlspecialchars($orgWebsite, ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://...">
                </div>
                <div class="col-12">
                    <label class="form-label">Biographie organisation</label>
                    <textarea class="form-control" name="bio_organisation" rows="4" placeholder="Présentation, mandat, zones d'intervention..."><?= htmlspecialchars($orgBio, ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Enregistrer les informations</button>
            </div>
        </form>
    </div>
</section>

<?php if (isset($_GET['must_complete_profile']) && $_GET['must_complete_profile'] === '1'): ?>
    <div class="card shadow-sm rounded-4 border-0 profile-org-card">
        <p class="mb-0"><small class="text-danger">Configuration requise: complétez le nom organisation, le téléphone et la biographie pour terminer l'activation du compte.</small></p>
    </div>
<?php endif; ?>

<div class="card shadow-sm rounded-4 border-0 profile-org-card">
    <h2 class="profile-section-title"><i class="fa-solid fa-shield-halved"></i>Sécurité du compte</h2>
    <?php if (isset($_GET['must_change_password']) && $_GET['must_change_password'] === '1'): ?>
        <p><small class="text-danger">Action requise: vous devez changer votre mot de passe pour continuer en toute sécurité.</small></p>
    <?php endif; ?>

    <form method="post" action="?page=profil">
        <input type="hidden" name="action" value="change_password">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

        <label class="form-label">Mot de passe actuel</label>
        <div class="password-field mb-2">
            <input class="form-control" type="password" name="current_password" id="current-password" required>
            <button class="password-toggle" type="button" data-toggle-password="current-password" aria-label="Afficher le mot de passe">
                <i class="fa-regular fa-eye"></i>
            </button>
        </div>

        <label class="form-label">Nouveau mot de passe</label>
        <div class="password-field mb-2">
            <input class="form-control" type="password" name="new_password" id="new-password" required>
            <button class="password-toggle" type="button" data-toggle-password="new-password" aria-label="Afficher le mot de passe">
                <i class="fa-regular fa-eye"></i>
            </button>
        </div>

        <label class="form-label">Confirmer le nouveau mot de passe</label>
        <div class="password-field mb-2">
            <input class="form-control" type="password" name="new_password_confirmation" id="new-password-confirmation" required>
            <button class="password-toggle" type="button" data-toggle-password="new-password-confirmation" aria-label="Afficher le mot de passe">
                <i class="fa-regular fa-eye"></i>
            </button>
        </div>

        <button class="btn btn-primary mt-3" type="submit">Changer le mot de passe</button>
    </form>
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
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="btn-confirm-crop" class="btn btn-primary">Valider et téléverser</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var view = document.getElementById('profile-view');
    var edit = document.getElementById('profile-edit');
    var btnEdit = document.getElementById('btn-edit-profile');
    var btnCancel = document.getElementById('btn-cancel-edit');
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
    if (btnCancel) {
        btnCancel.addEventListener('click', showViewMode);
    }
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
    var cropperModal = (cropperModalEl && window.bootstrap && window.bootstrap.Modal)
        ? new window.bootstrap.Modal(cropperModalEl)
        : null;

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
            if (!file || !cropperModal || !cropperImage) {
                return;
            }

            setStatus('', false);
            var reader = new FileReader();
            reader.onload = function (e) {
                var result = e.target && typeof e.target.result === 'string' ? e.target.result : '';
                if (!result) {
                    return;
                }
                cropperImage.src = result;
                cropperModal.show();
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

                if (cropperModal) {
                    cropperModal.hide();
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
