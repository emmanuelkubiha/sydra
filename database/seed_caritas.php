<?php
// Seed: CARITAS-UVIRA (organisation de test #2)
// Usage: php database/seed_caritas.php

$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';
$pdo = db($config);

// 1. Organisation
$pdo->exec("INSERT IGNORE INTO organizations (name, email, is_active) VALUES ('CARITAS-UVIRA', 'contact@caritas-uvira.cd', 1)");
$orgId = $pdo->lastInsertId();
if (!$orgId) {
    $orgId = $pdo->query("SELECT id FROM organizations WHERE name='CARITAS-UVIRA'")->fetchColumn();
}
echo "org_id=$orgId\n";

// 2. User reporter
$pwdHash = password_hash('SyDRA@2025', PASSWORD_BCRYPT, ['cost' => 12]);
$pdo->prepare("INSERT IGNORE INTO users (organization_id, role_id, full_name, email, password_hash, is_active, statut, must_change_password) VALUES (?, 4, 'Jean-Baptiste Murhula', 'reporter@caritas-uvira.cd', ?, 1, 'Actif', 0)")->execute([$orgId, $pwdHash]);
$userId = $pdo->lastInsertId();
if (!$userId) {
    $userId = $pdo->query("SELECT id FROM users WHERE email='reporter@caritas-uvira.cd'")->fetchColumn();
}
echo "user_id=$userId\n";

// 3. Alertes
// Statuts: DRAFT=1, SUBMITTED=2, UNDER_REVIEW=3, APPROVED=4
// Gravité: LOW=1, MEDIUM=2, HIGH=3, CRITICAL=4
// Urgence: IMMEDIATE=1, URGENT=2, NORMAL=3
// Incident: SECURITY=1, DISPLACEMENT=2, VIOLATION=3, NATURAL_DISASTER=4
$alertes = [
    [
        'ref'              => 'SY-2025-CARITAS-001',
        'status_id'        => 2,  // SUBMITTED
        'incident_type_id' => 1,  // SECURITY
        'severity_id'      => 4,  // CRITICAL
        'urgency_id'       => 1,  // IMMEDIATE
        'province'         => 'Nord-Kivu',
        'territory'        => 'Nyiragongo',
        'locality'         => 'Goma',
        'gps_lat'          => -1.6735,
        'gps_lng'          => 29.2236,
        'urgency_level'    => 'Critique',
        'workflow_status'  => 'Soumis',
        'incident_label'   => 'Attaque armée – quartier Karisimbi',
        'incident_type'    => 'SECURITY',
        'facts_text'       => "Une attaque armée survenue dans le quartier Karisimbi a provoqué des déplacements massifs. Environ 800 ménages ont fui vers le centre de Goma.",
        'victims_count'    => 12,
        'displaced_households' => 800,
        'needs_text'       => "Abris d'urgence, soins médicaux, eau potable",
    ],
    [
        'ref'              => 'SY-2025-CARITAS-002',
        'status_id'        => 3,  // UNDER_REVIEW
        'incident_type_id' => 2,  // DISPLACEMENT
        'severity_id'      => 3,  // HIGH
        'urgency_id'       => 2,  // URGENT
        'province'         => 'Sud-Kivu',
        'territory'        => 'Fizi',
        'locality'         => 'Baraka',
        'gps_lat'          => -4.0879,
        'gps_lng'          => 29.0791,
        'urgency_level'    => 'Elevee',
        'workflow_status'  => 'En revue',
        'incident_label'   => 'Déplacement massif – axe Baraka-Minembwe',
        'incident_type'    => 'DISPLACEMENT',
        'facts_text'       => "Suite aux affrontements intercommunautaires, plus de 1 200 ménages ont été déplacés sur l'axe Baraka-Minembwe. Les familles sont sans abri depuis 10 jours.",
        'victims_count'    => 0,
        'displaced_households' => 1200,
        'needs_text'       => "Vivres, NFI, protection enfants non accompagnés",
    ],
    [
        'ref'              => 'SY-2025-CARITAS-003',
        'status_id'        => 4,  // APPROVED
        'incident_type_id' => 3,  // VIOLATION
        'severity_id'      => 2,  // MEDIUM
        'urgency_id'       => 2,  // URGENT
        'province'         => 'Sud-Kivu',
        'territory'        => 'Shabunda',
        'locality'         => 'Shabunda centre',
        'gps_lat'          => -2.5253,
        'gps_lng'          => 27.3293,
        'urgency_level'    => 'Elevee',
        'workflow_status'  => 'Approuve',
        'incident_label'   => 'Violations droits humains – zone de Shabunda',
        'incident_type'    => 'VIOLATION',
        'facts_text'       => "Des violations graves des droits humains ont été documentées dans la zone de Shabunda, incluant des arrestations arbitraires et des confiscations de biens.",
        'victims_count'    => 35,
        'displaced_households' => 0,
        'needs_text'       => "Assistance juridique, documentation, soutien psychosocial",
    ],
    [
        'ref'              => 'SY-2025-CARITAS-004',
        'status_id'        => 1,  // DRAFT
        'incident_type_id' => 4,  // NATURAL_DISASTER
        'severity_id'      => 2,  // MEDIUM
        'urgency_id'       => 3,  // NORMAL
        'province'         => 'Nord-Kivu',
        'territory'        => 'Lubero',
        'locality'         => 'Butembo',
        'gps_lat'          => -0.1408,
        'gps_lng'          => 29.2903,
        'urgency_level'    => 'Moyenne',
        'workflow_status'  => 'Brouillon',
        'incident_label'   => 'Inondations – périphérie de Butembo',
        'incident_type'    => 'NATURAL_DISASTER',
        'facts_text'       => "Des pluies torrentielles ont provoqué des glissements de terrain dans les quartiers périphériques de Butembo. Plusieurs maisons ont été détruites.",
        'victims_count'    => 3,
        'displaced_households' => 90,
        'needs_text'       => "Abris temporaires, eau propre, kits hygiène",
    ],
];

$sql = 'INSERT INTO reports
    (reference_code, organization_id, reporter_user_id, report_type,
     status_id, incident_type_id, severity_id, urgency_id,
     province, territory, locality, gps_lat, gps_lng,
     urgency_level, workflow_status, incident_label, incident_type,
     facts_text, victims_count, displaced_households, needs_text, created_at)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, NOW())';
$stmt = $pdo->prepare($sql);
$ids = [];
foreach ($alertes as $a) {
    try {
        $stmt->execute([
            $a['ref'], $orgId, $userId, 'FLASH',
            $a['status_id'], $a['incident_type_id'], $a['severity_id'], $a['urgency_id'],
            $a['province'], $a['territory'], $a['locality'], $a['gps_lat'], $a['gps_lng'],
            $a['urgency_level'], $a['workflow_status'], $a['incident_label'], $a['incident_type'],
            $a['facts_text'], $a['victims_count'], $a['displaced_households'], $a['needs_text'],
        ]);
        $ids[] = $pdo->lastInsertId();
    } catch (Exception $e) {
        echo "ERR [{$a['ref']}]: " . $e->getMessage() . "\n";
    }
}
echo "Alertes insérées IDs: " . implode(',', $ids) . "\n";
echo "DONE\n";
