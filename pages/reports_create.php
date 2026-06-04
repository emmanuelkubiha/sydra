<?php
if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return '';
    }
}
?>
<div class="card">
    <h1>Créer un rapport</h1>
    <form method="post" action="?page=rapport_creer">
        <input type="hidden" name="action" value="create_report">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

        <label>Titre</label>
        <input name="title" required>

        <label>Type</label>
        <select name="report_type" required>
            <option value="FLASH">FLASH</option>
            <option value="NOTE">NOTE</option>
        </select>

        <label>Localisation</label>
        <input name="location_text" placeholder="Ville / territoire">

        <label>Niveau d'urgence</label>
        <select name="urgency_level" required>
            <option value="Faible">Faible</option>
            <option value="Moyenne" selected>Moyenne</option>
            <option value="Elevee">Elevee</option>
            <option value="Critique">Critique</option>
        </select>

        <label>Contenu</label>
        <textarea name="content" required></textarea>

        <button type="submit">Enregistrer</button>
    </form>
</div>
