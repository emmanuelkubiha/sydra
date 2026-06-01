<?php

declare(strict_types=1);
?>
<!-- Section: Titre profil -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 fw-bold mb-1">Mon compte</h1>
        <p class="text-muted mb-0">Modifier votre mot de passe en respectant les exigences de securite.</p>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-7">
        <!-- Section: Formulaire changement mot de passe -->
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Changer mon mot de passe</h3></div>
            <div class="card-body">
                <form method="post" action="?r=profil/mot-de-passe">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(App\Core\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="mb-3">
                        <label class="form-label">Mot de passe actuel</label>
                        <input type="password" class="form-control" name="mot_de_passe_actuel" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nouveau mot de passe</label>
                        <input type="password" class="form-control" name="nouveau_mot_de_passe" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmation</label>
                        <input type="password" class="form-control" name="confirmation_mot_de_passe" required>
                    </div>
                    <button type="submit" class="btn btn-sydra">Mettre a jour</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <!-- Section: Exigences mot de passe -->
        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title">Exigences</h3></div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>Au moins 10 caracteres</li>
                    <li>Au moins 1 majuscule</li>
                    <li>Au moins 1 minuscule</li>
                    <li>Au moins 1 chiffre</li>
                    <li>Au moins 1 caractere special</li>
                </ul>
            </div>
        </div>
    </div>
</div>
