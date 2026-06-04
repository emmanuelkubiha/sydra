<?php
/**
 * Centre d'aide SyDRA
 * Page d'assistance structurée avec onglets Bootstrap 5.
 */
?>

<div class="card help-tabs-card shadow-sm rounded-4 border-0">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <div>
            <h1 class="mb-1">Centre d'aide SyDRA</h1>
            <p class="hero-intro mb-0">Ressources utiles pour les organisations du GTMP.</p>
        </div>
    </div>

    <ul class="nav nav-tabs help-nav-tabs mb-3" id="helpTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="about-process-tab" data-bs-toggle="tab" data-bs-target="#about-process" type="button" role="tab" aria-controls="about-process" aria-selected="true">À propos & Processus</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="features-tab" data-bs-toggle="tab" data-bs-target="#features" type="button" role="tab" aria-controls="features" aria-selected="false">Fonctionnalités clés</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="rapportage-tab" data-bs-toggle="tab" data-bs-target="#rapportage" type="button" role="tab" aria-controls="rapportage" aria-selected="false">Hub Rapportage</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="faq-tab" data-bs-toggle="tab" data-bs-target="#faq" type="button" role="tab" aria-controls="faq" aria-selected="false">FAQ</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="support-tab" data-bs-toggle="tab" data-bs-target="#support" type="button" role="tab" aria-controls="support" aria-selected="false">Support opérationnel</button>
        </li>
    </ul>

    <div class="tab-content" id="helpTabsContent">
        <div class="tab-pane fade show active" id="about-process" role="tabpanel" aria-labelledby="about-process-tab" tabindex="0">
            <p>
                SyDRA (Système de Documentation, de Rapportage et d'Alerte) est la plateforme provinciale
                de monitoring de protection du GTMP. Elle facilite la remontée rapide des informations terrain,
                la structuration des analyses et la coordination inter-organisationnelle.
            </p>

            <ul class="help-process-list mt-3">
                <li>
                    <i class="fa-solid fa-paper-plane"></i>
                    <div><strong>1. Soumission par l'organisation</strong><br>Transmission d'un rapport FLASH ou d'une NOTE selon le niveau d'urgence.</div>
                </li>
                <li>
                    <i class="fa-solid fa-robot"></i>
                    <div><strong>2. Vérification / Correction via IA</strong><br>L'assistant IA aide à structurer le texte brut et à harmoniser la qualité des données.</div>
                </li>
                <li>
                    <i class="fa-solid fa-user-check"></i>
                    <div><strong>3. Validation par le Lead GTMP</strong><br>Relecture, validation technique et décision de diffusion.</div>
                </li>
                <li>
                    <i class="fa-solid fa-bullhorn"></i>
                    <div><strong>4. Publication et alertes</strong><br>Mise à disposition des informations validées et notifications aux parties prenantes.</div>
                </li>
            </ul>
        </div>

        <div class="tab-pane fade" id="features" role="tabpanel" aria-labelledby="features-tab" tabindex="0">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="help-highlight h-100">
                        <h3>Rapport FLASH</h3>
                        <p>
                            Format d'alerte urgente, conçu pour une saisie rapide en 3 à 5 minutes.
                            Il contient les informations essentielles pour déclencher une réponse immédiate.
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="help-highlight h-100">
                        <h3>NOTE de Monitoring</h3>
                        <p>
                            Format détaillé, généralement consolidé après 48h, pour documenter le contexte,
                            les tendances de protection et les besoins d'action coordonnée.
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="help-highlight h-100">
                        <h3>Assistant IA</h3>
                        <p>
                            L'assistant propose une reformulation professionnelle, une meilleure structure narrative
                            et une clarification des points sensibles sans altérer les faits signalés.
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="help-highlight h-100">
                        <h3>Cartographie interactive</h3>
                        <p>
                            Visualisation géographique des alertes et rapports pour améliorer la priorisation,
                            la coordination multi-acteurs et le suivi des zones de risque.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="rapportage" role="tabpanel" aria-labelledby="rapportage-tab" tabindex="0">
            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="help-highlight h-100">
                        <h3>Nouveau Hub de Rapportage</h3>
                        <p>
                            Le module Rapportage dispose désormais d'une page d'accueil dédiée,
                            chaleureuse et orientée action. Elle centralise les modes de création,
                            les indicateurs clés et la carte des incidents récents.
                        </p>
                        <ul class="mt-2 mb-0">
                            <li><strong>Mode IA:</strong> parcours assisté (en cours de configuration).</li>
                            <li><strong>Mode Manuel (Wizard):</strong> formulaire structuré en 4 étapes.</li>
                            <li><strong>Cartographie Leaflet:</strong> vue verrouillée Sud-Kivu / Maniema pour le suivi opérationnel.</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="help-highlight h-100">
                        <h3>Chemins utiles</h3>
                        <p class="mb-2">Accès rapide aux écrans du module:</p>
                        <ul class="mb-0">
                            <li><a href="?page=rapportage">Accueil Rapportage</a></li>
                            <li><a href="?page=rapportage-creer-wizar">Création Wizard</a></li>
                            <li><a href="?page=rapportage-creer-AI">Assistant IA</a></li>
                            <li><a href="?page=rapportage-liste-user">Mes alertes</a></li>
                            <li><a href="?page=rapportage-admin-list">Coordination GTMP</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="faq" role="tabpanel" aria-labelledby="faq-tab" tabindex="0">
            <div class="accordion" id="helpFaqAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeadingOne">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseOne" aria-expanded="true" aria-controls="faqCollapseOne">
                            Qui peut créer un rapport dans SyDRA ?
                        </button>
                    </h2>
                    <div id="faqCollapseOne" class="accordion-collapse collapse show" aria-labelledby="faqHeadingOne" data-bs-parent="#helpFaqAccordion">
                        <div class="accordion-body">
                            Les organisations autorisées par le GTMP disposent d'un compte et peuvent soumettre des rapports selon leur rôle.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeadingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseTwo" aria-expanded="false" aria-controls="faqCollapseTwo">
                            Quelle est la différence entre FLASH et NOTE ?
                        </button>
                    </h2>
                    <div id="faqCollapseTwo" class="accordion-collapse collapse" aria-labelledby="faqHeadingTwo" data-bs-parent="#helpFaqAccordion">
                        <div class="accordion-body">
                            FLASH est destiné aux alertes urgentes immédiates. NOTE est destinée aux analyses détaillées et au suivi de situation.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeadingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseThree" aria-expanded="false" aria-controls="faqCollapseThree">
                            Que faire si le lien d'activation expire ?
                        </button>
                    </h2>
                    <div id="faqCollapseThree" class="accordion-collapse collapse" aria-labelledby="faqHeadingThree" data-bs-parent="#helpFaqAccordion">
                        <div class="accordion-body">
                            Contactez un administrateur SyDRA pour relancer une nouvelle invitation de création de compte.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeadingFour">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseFour" aria-expanded="false" aria-controls="faqCollapseFour">
                            L'assistant IA modifie-t-il mes données ?
                        </button>
                    </h2>
                    <div id="faqCollapseFour" class="accordion-collapse collapse" aria-labelledby="faqHeadingFour" data-bs-parent="#helpFaqAccordion">
                        <div class="accordion-body">
                            Non. Il propose une amélioration de forme. L'utilisateur conserve le contrôle final avant soumission.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeadingFive">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseFive" aria-expanded="false" aria-controls="faqCollapseFive">
                            Le Wizard permet-il d'enregistrer un brouillon ?
                        </button>
                    </h2>
                    <div id="faqCollapseFive" class="accordion-collapse collapse" aria-labelledby="faqHeadingFive" data-bs-parent="#helpFaqAccordion">
                        <div class="accordion-body">
                            Oui. L'étape finale du Wizard propose deux options: <strong>Enregistrer comme Brouillon</strong>
                            ou <strong>Soumettre au Cluster</strong>. Les pièces jointes sont gérées en glisser-déposer.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeadingSix">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseSix" aria-expanded="false" aria-controls="faqCollapseSix">
                            Pourquoi la carte est-elle limitée à l'Est de la RDC ?
                        </button>
                    </h2>
                    <div id="faqCollapseSix" class="accordion-collapse collapse" aria-labelledby="faqHeadingSix" data-bs-parent="#helpFaqAccordion">
                        <div class="accordion-body">
                            Le cadrage est volontairement verrouillé pour renforcer la lisibilité opérationnelle
                            sur les zones prioritaires du monitoring de protection (Sud-Kivu et Maniema).
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="support" role="tabpanel" aria-labelledby="support-tab" tabindex="0">
            <div class="help-highlight">
                <h3>Canaux d'assistance</h3>
                <p>
                    Pour tout incident technique, question de gouvernance des données ou difficulté d'accès,
                    contactez l'équipe support via l'administrateur GTMP de votre organisation.
                </p>
            </div>
            <ul class="help-process-list mt-3">
                <li>
                    <i class="fa-solid fa-envelope"></i>
                    <div><strong>Support fonctionnel</strong><br>Questions sur les workflows FLASH/NOTE, validation et publication.</div>
                </li>
                <li>
                    <i class="fa-solid fa-shield-heart"></i>
                    <div><strong>Support sécurité</strong><br>Problèmes de connexion, d'activation de compte ou de mot de passe.</div>
                </li>
                <li>
                    <i class="fa-solid fa-map-location-dot"></i>
                    <div><strong>Support cartographie</strong><br>Assistance sur la visualisation des alertes et l'interprétation spatiale.</div>
                </li>
            </ul>
        </div>
    </div>
</div>
