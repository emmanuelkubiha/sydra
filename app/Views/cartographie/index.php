<?php

declare(strict_types=1);
?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Cartographie des incidents</h3>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-5">
                <label class="form-label">Filtrer par territoire</label>
                <input id="filtre_territoire" class="form-control" placeholder="Ex: Kalehe">
            </div>
            <div class="col-md-3">
                <label class="form-label">Niveau gravite</label>
                <select id="filtre_gravite" class="form-select">
                    <option value="">Tous</option>
                    <option value="LOW">Faible</option>
                    <option value="MEDIUM">Moyenne</option>
                    <option value="HIGH">Elevee</option>
                    <option value="CRITICAL">Critique</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" id="btn_filtrer_carte">Filtrer</button>
            </div>
        </div>

        <div id="incident_map" style="height: 520px; border-radius: 10px;"></div>
    </div>
</div>
