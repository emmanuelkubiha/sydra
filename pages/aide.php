<?php
/**
 * Centre d'aide SyDRA
 * Version refondue: structure premium, sections lisibles et navigation rapide.
 */
?>

<style>
.help-v3-shell {
    background: linear-gradient(180deg, #f7fbff 0%, #ffffff 42%);
    border: 1px solid #d9e5f3;
}

.help-v3-hero {
    border: 1px solid #d8e3f1;
    background:
        radial-gradient(circle at 100% 0%, rgba(14, 116, 144, 0.12), transparent 45%),
        linear-gradient(140deg, #ffffff 0%, #f2f8ff 100%);
}

.help-v3-kpis {
    display: flex;
    gap: 0.6rem;
    flex-wrap: wrap;
}

.help-v3-kpi-pill {
    border-radius: 999px;
    background: #eaf3ff;
    color: #0b3c7d;
    border: 1px solid #c6dcfa;
    padding: 0.35rem 0.75rem;
    font-weight: 600;
    font-size: 0.82rem;
}

.help-v3-quick-card {
    border: 1px solid #dbe8f6;
    border-radius: 14px;
    background: #ffffff;
    padding: 0.95rem;
    transition: all 0.22s ease;
    height: 100%;
}

.help-v3-quick-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(8, 47, 107, 0.08);
    border-color: #b9d4f5;
}

.help-v3-quick-card i {
    color: #005bbb;
}

.help-v3-tab-nav {
    border: 0;
    gap: 0.45rem;
}

.help-v3-tab-nav .nav-link {
    border: 1px solid #dbe8f7;
    border-radius: 12px;
    color: #1e3a5f;
    font-weight: 600;
    background: #ffffff;
}

.help-v3-tab-nav .nav-link.active {
    background: #0b62c8;
    border-color: #0b62c8;
    color: #ffffff;
}

.help-v3-pane {
    border: 1px solid #dde8f5;
    border-radius: 14px;
    background: #ffffff;
    padding: 1rem;
}

.help-v3-block {
    border: 1px solid #e4edf8;
    border-radius: 12px;
    padding: 0.9rem;
    background: #fbfdff;
    height: 100%;
}

.help-v3-block h3 {
    font-size: 1rem;
    margin-bottom: 0.45rem;
}

.help-v3-process {
    display: grid;
    gap: 0.65rem;
}

.help-v3-process-item {
    border: 1px solid #dfeafa;
    border-radius: 12px;
    padding: 0.7rem 0.8rem;
    display: flex;
    gap: 0.7rem;
    background: #ffffff;
}

.help-v3-process-item i {
    color: #005bbb;
    margin-top: 0.15rem;
}

.help-v3-support {
    border: 1px solid #d8e5f5;
    border-radius: 14px;
    background: linear-gradient(150deg, #f9fcff 0%, #eef5ff 100%);
}
</style>

<div class="card help-v3-shell shadow-sm rounded-4 border-0">
    <div class="help-v3-hero rounded-4 p-3 p-md-4 mb-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h1 class="mb-2">Centre d'aide SyDRA</h1>
                <p class="text-muted mb-2">Guide opérationnel pour saisir, valider et suivre les alertes de protection sans perte d'information.</p>
                <div class="help-v3-kpis">
                    <span class="help-v3-kpi-pill">Version v0.9.0</span>
                    <span class="help-v3-kpi-pill">Mise a jour: 2026-06-06</span>
                    <span class="help-v3-kpi-pill">FLASH & NOTE</span>
                    <span class="help-v3-kpi-pill">Validation Lead/Admin</span>
                    <span class="help-v3-kpi-pill">Notifications & Emails</span>
                    <span class="help-v3-kpi-pill">Cartographie opérationnelle</span>
                </div>
            </div>

            <a href="?page=rapportage" class="btn btn-primary">
                <i class="fa-solid fa-arrow-right me-1"></i>Accéder au Rapportage
            </a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6 col-xl-3">
            <a href="?page=rapportage-creer-wizar" class="text-decoration-none text-reset">
                <div class="help-v3-quick-card">
                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i>
                    <strong>Démarrer un Wizard</strong>
                    <p class="text-muted mb-0 mt-1">Créer un rapport pas à pas avec brouillon ou soumission.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-xl-3">
            <a href="?page=rapportage-liste-user" class="text-decoration-none text-reset">
                <div class="help-v3-quick-card">
                    <i class="fa-solid fa-folder-open me-1"></i>
                    <strong>Voir mes alertes</strong>
                    <p class="text-muted mb-0 mt-1">Retrouver l'historique et l'état de traitement de vos rapports.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-xl-3">
            <a href="?page=rapportage-admin-list" class="text-decoration-none text-reset">
                <div class="help-v3-quick-card">
                    <i class="fa-solid fa-tower-observation me-1"></i>
                    <strong>Vue coordination</strong>
                    <p class="text-muted mb-0 mt-1">Superviser les alertes soumises et traiter les décisions.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-xl-3">
            <a href="?page=profil" class="text-decoration-none text-reset">
                <div class="help-v3-quick-card">
                    <i class="fa-solid fa-id-badge me-1"></i>
                    <strong>Mon profil</strong>
                    <p class="text-muted mb-0 mt-1">Mettre à jour l'identité organisationnelle et les contacts.</p>
                </div>
            </a>
        </div>
    </div>

    <ul class="nav nav-pills help-v3-tab-nav mb-3" id="helpTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="about-process-tab" data-bs-toggle="tab" data-bs-target="#about-process" type="button" role="tab" aria-controls="about-process" aria-selected="true">À propos</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="features-tab" data-bs-toggle="tab" data-bs-target="#features" type="button" role="tab" aria-controls="features" aria-selected="false">Fonctionnalités</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="rapportage-tab" data-bs-toggle="tab" data-bs-target="#rapportage" type="button" role="tab" aria-controls="rapportage" aria-selected="false">Hub Rapportage</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="faq-tab" data-bs-toggle="tab" data-bs-target="#faq" type="button" role="tab" aria-controls="faq" aria-selected="false">FAQ</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="support-tab" data-bs-toggle="tab" data-bs-target="#support" type="button" role="tab" aria-controls="support" aria-selected="false">Support</button>
        </li>
    </ul>

    <div class="tab-content" id="helpTabsContent">
        <div class="tab-pane fade show active" id="about-process" role="tabpanel" aria-labelledby="about-process-tab" tabindex="0">
            <div class="help-v3-pane">
                <p>
                    SyDRA (Système de Documentation, de Rapportage et d'Alerte) est la plateforme provinciale
                    de monitoring de protection du GTMP. Elle structure la remontée terrain et sécurise la boucle
                    de décision entre organisations rapporteuses et coordination.
                </p>

                <div class="help-v3-process mt-3">
                    <div class="help-v3-process-item">
                        <i class="fa-solid fa-paper-plane"></i>
                        <div><strong>1. Soumission</strong><br>Création d'un rapport FLASH ou NOTE selon le niveau d'urgence.</div>
                    </div>
                    <div class="help-v3-process-item">
                        <i class="fa-solid fa-robot"></i>
                        <div><strong>2. Assistance IA</strong><br>Amélioration de la forme et de la lisibilité sans altération des faits.</div>
                    </div>
                    <div class="help-v3-process-item">
                        <i class="fa-solid fa-user-check"></i>
                        <div><strong>3. Validation coordination</strong><br>Décision Lead/Admin: valider, demander des compléments ou rejeter.</div>
                    </div>
                    <div class="help-v3-process-item">
                        <i class="fa-solid fa-bullhorn"></i>
                        <div><strong>4. Notification</strong><br>Diffusion interne, notifications et emails transactionnels automatiques.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="features" role="tabpanel" aria-labelledby="features-tab" tabindex="0">
            <div class="help-v3-pane">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="help-v3-block">
                            <h3>Rapport FLASH</h3>
                            <p class="mb-0">Alerte urgente en saisie rapide pour déclencher une action immédiate.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="help-v3-block">
                            <h3>NOTE de Monitoring</h3>
                            <p class="mb-0">Format consolidé pour analyse, suivi des tendances et coordination.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="help-v3-block">
                            <h3>Assistant IA</h3>
                            <p class="mb-0">Support rédactionnel pour clarifier le contexte et les recommandations.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="help-v3-block">
                            <h3>Cartographie interactive</h3>
                            <p class="mb-0">Lecture géographique des incidents pour prioriser les interventions.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="rapportage" role="tabpanel" aria-labelledby="rapportage-tab" tabindex="0">
            <div class="help-v3-pane">
                <div class="row g-3">
                    <div class="col-lg-7">
                        <div class="help-v3-block">
                            <h3>Module Rapportage</h3>
                            <p>
                                Le hub centralise la création, le suivi des alertes et la supervision coordination.
                                Les brouillons restent privés à leur auteur, même en vue Admin/Lead.
                            </p>
                            <ul class="mb-0">
                                <li>Mode IA: parcours assisté.</li>
                                <li>Mode manuel: Wizard en 4 étapes.</li>
                                <li>Carte Leaflet: vue opérationnelle incidents.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="help-v3-block">
                            <h3>Accès rapides</h3>
                            <ul class="mb-0">
                                <li><a href="?page=rapportage">Accueil Rapportage</a></li>
                                <li><a href="?page=rapportage-creer-wizar">Création Wizard</a></li>
                                <li><a href="?page=rapportage-creer-AI">Assistant IA</a></li>
                                <li><a href="?page=rapportage-liste-user">Mes alertes</a></li>
                                <li><a href="?page=rapportage-admin-list">Coordination GTMP</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="help-v3-block">
                            <h3>Rôle des API</h3>
                            <ul class="mb-0">
                                <li><strong>api/save_report.php</strong>: sauvegarde Wizard + pièces jointes.</li>
                                <li><strong>api/get_dashboard_filtered.php</strong>: filtres hub, KPI, carte et graphiques.</li>
                                <li><strong>api/change_status.php</strong>: décisions Lead/Admin, historique, notification, email.</li>
                                <li><strong>api/mark_notification_read.php</strong>: marquage des notifications lues.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="faq" role="tabpanel" aria-labelledby="faq-tab" tabindex="0">
            <div class="help-v3-pane">
                <div class="accordion" id="helpFaqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqHeadingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseOne" aria-expanded="true" aria-controls="faqCollapseOne">
                                Qui peut créer un rapport dans SyDRA ?
                            </button>
                        </h2>
                        <div id="faqCollapseOne" class="accordion-collapse collapse show" aria-labelledby="faqHeadingOne" data-bs-parent="#helpFaqAccordion">
                            <div class="accordion-body">Les organisations autorisées par le GTMP avec compte actif.</div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqHeadingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseTwo" aria-expanded="false" aria-controls="faqCollapseTwo">
                                Différence entre FLASH et NOTE ?
                            </button>
                        </h2>
                        <div id="faqCollapseTwo" class="accordion-collapse collapse" aria-labelledby="faqHeadingTwo" data-bs-parent="#helpFaqAccordion">
                            <div class="accordion-body">FLASH = urgent immédiat. NOTE = analyse détaillée et suivi.</div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqHeadingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseThree" aria-expanded="false" aria-controls="faqCollapseThree">
                                Le Wizard permet-il les brouillons ?
                            </button>
                        </h2>
                        <div id="faqCollapseThree" class="accordion-collapse collapse" aria-labelledby="faqHeadingThree" data-bs-parent="#helpFaqAccordion">
                            <div class="accordion-body">Oui. Vous pouvez enregistrer en brouillon ou soumettre au cluster.</div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqHeadingFour">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseFour" aria-expanded="false" aria-controls="faqCollapseFour">
                                Pourquoi je ne vois pas tous les brouillons en admin ?
                            </button>
                        </h2>
                        <div id="faqCollapseFour" class="accordion-collapse collapse" aria-labelledby="faqHeadingFour" data-bs-parent="#helpFaqAccordion">
                            <div class="accordion-body">Un brouillon reste privé: seul son auteur peut le voir tant qu'il n'est pas soumis.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="support" role="tabpanel" aria-labelledby="support-tab" tabindex="0">
            <div class="help-v3-pane help-v3-support">
                <h3 class="h5 mb-2">Support opérationnel</h3>
                <p class="mb-3">En cas d'incident technique, contactez l'administration GTMP de votre organisation.</p>
                <p class="mb-3">
                    <strong>Contacts support:</strong>
                    <a href="mailto:emmanuelkubiha@gmail.com">emmanuelkubiha@gmail.com</a>
                    ou
                    <a href="mailto:it@fosip-drc.org">it@fosip-drc.org</a>
                </p>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="help-v3-block bg-white">
                            <strong>Support fonctionnel</strong>
                            <p class="mb-0 text-muted">Workflow FLASH/NOTE, validation et publication.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="help-v3-block bg-white">
                            <strong>Support sécurité</strong>
                            <p class="mb-0 text-muted">Connexion, activation de compte, mot de passe.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="help-v3-block bg-white">
                            <strong>Support cartographie</strong>
                            <p class="mb-0 text-muted">Lecture des incidents et priorisation géographique.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
