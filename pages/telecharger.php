<?php
/** @var array $config */
/** @var array|null $authUser */
?>
<div class="container py-5">
    <div class="row justify-content-center text-center mb-5">
        <div class="col-lg-8">
            <div class="d-inline-flex align-items-center justify-content-center p-3 mb-3 bg-primary-subtle text-primary rounded-circle border border-primary-subtle animate-bounce-soft" style="width: 80px; height: 80px;">
                <i class="fa-solid fa-cloud-arrow-down fa-2x"></i>
            </div>
            <h1 class="fw-bold text-primary display-5 mb-3">Installer SyDRA</h1>
            <p class="text-muted fs-5 max-w-2xl mx-auto">Emportez le Système de Rapportage partout avec vous. Installez SyDRA sur votre appareil pour un accès rapide et sécurisé sur le terrain.</p>
        </div>
    </div>

    <div class="row g-4 justify-content-center">
        <!-- Android -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm premium-pwa-card" style="border-radius: 20px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); overflow: hidden; background: #ffffff; border: 1px solid rgba(226, 232, 240, 0.8) !important;">
                <div class="card-body text-center p-5">
                    <div class="pwa-icon-wrapper android-icon mb-4 d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 70px; height: 70px; background: rgba(25, 135, 84, 0.1); color: #198754;">
                        <i class="fa-brands fa-android fa-2x"></i>
                    </div>
                    <h5 class="fw-bold mb-3" style="color: #0f172a;">Sur Android</h5>
                    <p class="text-muted small mb-0" style="line-height: 1.6;">Ouvrez ce site dans <strong>Google Chrome</strong>. Appuyez sur les 3 petits points (menu) en haut à droite, puis sélectionnez <br><strong>"Ajouter à l'écran d'accueil"</strong> ou <strong>"Installer l'application"</strong>.</p>
                </div>
            </div>
        </div>
        
        <!-- iOS / iPhone -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm premium-pwa-card" style="border-radius: 20px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); overflow: hidden; background: #ffffff; border: 1px solid rgba(226, 232, 240, 0.8) !important;">
                <div class="card-body text-center p-5">
                    <div class="pwa-icon-wrapper apple-icon mb-4 d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 70px; height: 70px; background: rgba(33, 37, 41, 0.1); color: #212529;">
                        <i class="fa-brands fa-apple fa-2x"></i>
                    </div>
                    <h5 class="fw-bold mb-3" style="color: #0f172a;">Sur iPhone / iPad</h5>
                    <p class="text-muted small mb-0" style="line-height: 1.6;">Ouvrez ce site dans <strong>Safari</strong>. Appuyez sur l'icône de partage (le carré avec la flèche vers le haut) en bas de l'écran, puis choisissez <br><strong>"Sur l'écran d'accueil"</strong>.</p>
                </div>
            </div>
        </div>

        <!-- PC / Mac -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm premium-pwa-card" style="border-radius: 20px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); overflow: hidden; background: #ffffff; border: 1px solid rgba(226, 232, 240, 0.8) !important;">
                <div class="card-body text-center p-5">
                    <div class="pwa-icon-wrapper desktop-icon mb-4 d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 70px; height: 70px; background: rgba(13, 110, 253, 0.1); color: #0d6efd;">
                        <i class="fa-solid fa-laptop fa-2x"></i>
                    </div>
                    <h5 class="fw-bold mb-3" style="color: #0f172a;">Sur PC ou Mac</h5>
                    <p class="text-muted small mb-0" style="line-height: 1.6;">Ouvrez ce site dans <strong>Chrome ou Edge</strong>. Dans la barre d'adresse (à droite de l'URL), cliquez sur l'icône de téléchargement ou le petit '+' pour installer SyDRA sur votre bureau.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.premium-pwa-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02) !important;
    border-color: rgba(13, 110, 253, 0.3) !important;
}
.pwa-icon-wrapper {
    transition: transform 0.3s ease;
}
.premium-pwa-card:hover .pwa-icon-wrapper {
    transform: scale(1.1) rotate(5deg);
}
@keyframes bounceSoft {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
}
.animate-bounce-soft {
    animation: bounceSoft 2s ease-in-out infinite;
}
</style>
