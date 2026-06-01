<?php

declare(strict_types=1);

$incidentTypes = $incidentTypes ?? [];
$severityLevels = $severityLevels ?? [];
$urgencies = $urgencies ?? [];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 fw-bold mb-1">Nouveau rapport FLASH / NOTE</h1>
        <p class="text-muted mb-0">Saisie rapide avec geolocalisation et recherche de lieu.</p>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<!-- Section: Guide de rapportage -->
<div class="card card-outline card-info mb-3">
    <div class="card-body py-2">
        <div class="small">
            <strong>Guide rapide:</strong> utilise <strong>FLASH</strong> pour une alerte immediate, puis complete en <strong>NOTE</strong> pour l'analyse detaillee.
            Renseigne la carte pour activer l'auto-remplissage de la localisation.
        </div>
    </div>
</div>

<form method="post" action="?r=reports/create" id="report-form">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">

    <!-- Section: Identification du rapport -->
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Type de rapport</label>
            <select class="form-select" name="report_type" required>
                <option value="FLASH">FLASH (rapide)</option>
                <option value="NOTE">NOTE (detaillee)</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Type d'incident</label>
            <select class="form-select" name="incident_type_id">
                <option value="">Selectionner...</option>
                <?php foreach ($incidentTypes as $it): ?>
                    <option value="<?= (int) $it['id']; ?>"><?= htmlspecialchars($it['label'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Gravite</label>
            <select class="form-select" name="severity_id">
                <option value="">Selectionner...</option>
                <?php foreach ($severityLevels as $s): ?>
                    <option value="<?= (int) $s['id']; ?>"><?= htmlspecialchars($s['label'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Urgence</label>
            <select class="form-select" name="urgency_id">
                <option value="">Selectionner...</option>
                <?php foreach ($urgencies as $u): ?>
                    <option value="<?= (int) $u['id']; ?>"><?= htmlspecialchars($u['label'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Section: Localisation administrative -->
        <div class="col-md-3"><label class="form-label">Province</label><input class="form-control" name="province" id="province_input"></div>
        <div class="col-md-3"><label class="form-label">Territoire</label><input class="form-control" name="territory" id="territory_input"></div>
        <div class="col-md-3"><label class="form-label">Zone de sante</label><input class="form-control" name="health_zone" id="health_zone_input"></div>
        <div class="col-md-3"><label class="form-label">Groupement</label><input class="form-control" name="groupement" id="groupement_input"></div>
        <div class="col-md-3"><label class="form-label">Village</label><input class="form-control" name="village" id="village_input"></div>
        <div class="col-md-3"><label class="form-label">Localite</label><input class="form-control" name="locality" id="locality_input"></div>

        <div class="col-md-8 position-relative">
            <label class="form-label">Rechercher un lieu (taper et choisir)</label>
            <input class="form-control" name="place_search_text" id="place_search_text" placeholder="Ex: Minova, Kalehe, Sud-Kivu">
            <div id="location_suggestions" class="list-group suggestions d-none"></div>
        </div>
        <div class="col-md-2"><label class="form-label">Latitude</label><input class="form-control" name="latitude" id="latitude"></div>
        <div class="col-md-2"><label class="form-label">Longitude</label><input class="form-control" name="longitude" id="longitude"></div>

        <div class="col-12">
            <div class="card border-0 card-soft">
                <div class="card-body">
                    <div id="map" class="report-map"></div>
                    <p class="small text-muted mt-2 mb-0">Astuce: tu peux cliquer sur la carte pour fixer les coordonnees.</p>
                </div>
            </div>
        </div>

        <!-- Section: Chiffres d'impact -->
        <div class="col-md-3"><label class="form-label">Menages affectes</label><input type="number" min="0" class="form-control" name="households_count"></div>
        <div class="col-md-3"><label class="form-label">Personnes affectees</label><input type="number" min="0" class="form-control" name="people_count"></div>

        <!-- Section: Categories vulnerables quantifiables -->
        <div class="col-12">
            <div class="card card-soft border-0">
                <div class="card-body">
                    <h2 class="h6 mb-3">Categories vulnerables (quantification rapide)</h2>
                    <div class="row g-3">
                        <div class="col-md-2"><label class="form-label">Enfants</label><input type="number" min="0" class="form-control" name="vulnerable_children_count" value="0"></div>
                        <div class="col-md-2"><label class="form-label">Personnes agees</label><input type="number" min="0" class="form-control" name="vulnerable_elderly_count" value="0"></div>
                        <div class="col-md-2"><label class="form-label">Femmes</label><input type="number" min="0" class="form-control" name="vulnerable_women_count" value="0"></div>
                        <div class="col-md-2"><label class="form-label">Hommes</label><input type="number" min="0" class="form-control" name="vulnerable_men_count" value="0"></div>
                        <div class="col-md-2"><label class="form-label">Handicap</label><input type="number" min="0" class="form-control" name="vulnerable_disability_count" value="0"></div>
                        <div class="col-md-2"><label class="form-label">Autres</label><input type="number" min="0" class="form-control" name="vulnerable_other_count" value="0"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section: Narratif du rapport -->
        <div class="col-md-6"><label class="form-label">Contexte</label><textarea class="form-control" rows="4" name="context_text"></textarea></div>
        <div class="col-md-6"><label class="form-label">Resume des faits</label><textarea class="form-control" rows="4" name="facts_text" required></textarea></div>
        <div class="col-md-6">
            <label class="form-label">Constats / Analyse <span class="text-info" data-bs-toggle="tooltip" title="Analyse des causes, risques, impacts et tendances observees."><i class="bi bi-info-circle"></i></span></label>
            <textarea class="form-control" rows="4" name="analysis_text"></textarea>
        </div>
        <div class="col-md-6"><label class="form-label">Impacts humanitaires</label><textarea class="form-control" rows="4" name="impacts_text"></textarea></div>
        <div class="col-md-6"><label class="form-label">Besoins prioritaires</label><textarea class="form-control" rows="4" name="needs_text"></textarea></div>
        <div class="col-md-6">
            <label class="form-label">Recommandations <span class="text-info" data-bs-toggle="tooltip" title="Actions proposees, acteurs cibles, delai et niveau de priorite."><i class="bi bi-info-circle"></i></span></label>
            <textarea class="form-control" rows="4" name="recommendations_text"></textarea>
        </div>

        <div class="col-12 d-flex gap-2 justify-content-end mt-2">
            <button type="submit" name="submit_report" value="0" class="btn btn-outline-secondary">Enregistrer brouillon</button>
            <button type="submit" name="submit_report" value="1" class="btn btn-sydra">Soumettre au cluster</button>
        </div>
    </div>
</form>
