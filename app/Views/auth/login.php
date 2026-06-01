<?php

declare(strict_types=1);

use App\Helpers\Lang;
?>
<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <!-- Section: Carte de connexion -->
        <div class="card card-soft border-0">
            <div class="card-body p-4">
                <div class="row g-4 align-items-start">
                    <div class="col-md-6">
                        <!-- Section: Formulaire connexion -->
                        <h1 class="h4 fw-bold mb-2"><?= htmlspecialchars(Lang::tr('auth.login_title'), ENT_QUOTES, 'UTF-8'); ?></h1>
                        <p class="text-muted small mb-4">Acces securise au systeme de rapportage.</p>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger py-2"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>

                        <form method="post" action="?r=login" novalidate>
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">

                            <div class="mb-3">
                                <label for="email" class="form-label"><?= htmlspecialchars(Lang::tr('auth.email'), ENT_QUOTES, 'UTF-8'); ?></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label"><?= htmlspecialchars(Lang::tr('auth.password'), ENT_QUOTES, 'UTF-8'); ?></label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-sydra w-100"><?= htmlspecialchars(Lang::tr('auth.submit'), ENT_QUOTES, 'UTF-8'); ?></button>
                        </form>
                    </div>

                    <div class="col-md-6">
                        <!-- Section: Aide connexion discrete -->
                        <button class="btn btn-sm btn-outline-secondary mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#aideConnexion" aria-expanded="false" aria-controls="aideConnexion">
                            Besoin d'aide ?
                        </button>
                        <div class="collapse" id="aideConnexion">
                        <div class="help-panel">
                            <h2 class="h6 fw-bold mb-2">Aide Connexion</h2>
                            <ul class="small text-muted mb-3">
                                <li>Utilise ton email organisationnel autorise.</li>
                                <li>Le mot de passe est sensible aux majuscules.</li>
                                <li>Si la connexion echoue, contacte l'administrateur GTMP.</li>
                            </ul>
                            <div class="small">
                                <div><strong>Compte demo</strong></div>
                                <div>reporter@sydra.local</div>
                                <div>Mot de passe: password</div>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
