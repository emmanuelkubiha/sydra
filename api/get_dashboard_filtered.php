<?php
/**
 * api/get_dashboard_filtered.php
 * SyDRA – Hub Rapportage : statistiques et marqueurs filtrés
 *
 * Mission 3 : Backend AJAX sécurisé
 * Accepte : POST application/x-www-form-urlencoded
 * Paramètres : date_debut, date_fin, organisation_id
 * Retourne  : JSON { stats: {...}, markers: [...] }
 *
 * ROLE DU FICHIER:
 * - Endpoint AJAX du Hub Rapportage.
 * - Applique les filtres (dates + organisation) et les règles RBAC.
 * - Retourne les KPI, les marqueurs carte et les séries des graphiques.
 */

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=UTF-8');

// --- Sécurité : session requise ---
if (!isset($_SESSION['auth_user_id']) || (int) $_SESSION['auth_user_id'] <= 0) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Session expirée.']);
    exit;
}

// --- Méthode ---
$method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method !== 'POST' && $method !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

// --- Bootstrap config + PDO ---
$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';
$pdo = db($config);

// --- Utilisateur authentifié ---
$userId = (int) $_SESSION['auth_user_id'];
$usersHasRoleColumn = false;
$usersHasRoleIdColumn = false;
$usersHasOrgIdColumn = false;

$colStmt = $pdo->prepare('SELECT COLUMN_NAME
                          FROM information_schema.COLUMNS
                          WHERE TABLE_SCHEMA = DATABASE()
                            AND TABLE_NAME = :table_name');
$colStmt->execute(['table_name' => 'users']);
foreach ($colStmt->fetchAll(PDO::FETCH_COLUMN) as $columnName) {
    $col = strtolower((string) $columnName);
    if ($col === 'role') {
        $usersHasRoleColumn = true;
    }
    if ($col === 'role_id') {
        $usersHasRoleIdColumn = true;
    }
    if ($col === 'organization_id') {
        $usersHasOrgIdColumn = true;
    }
}

$reportColsStmt = $pdo->prepare('SELECT COLUMN_NAME
                                 FROM information_schema.COLUMNS
                                 WHERE TABLE_SCHEMA = DATABASE()
                                   AND TABLE_NAME = :table_name');
$reportColsStmt->execute(['table_name' => 'reports']);
$reportCols = array_map('strtolower', $reportColsStmt->fetchAll(PDO::FETCH_COLUMN) ?: []);

$reporterFk = null;
foreach (['reporter_user_id', 'user_id', 'author_id', 'created_by', 'reporter_id'] as $candidate) {
    if (in_array($candidate, $reportCols, true)) {
        $reporterFk = $candidate;
        break;
    }
}

if ($reporterFk === null) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Colonne de rattachement utilisateur introuvable dans reports.']);
    exit;
}

$hasReportOrgId = in_array('organization_id', $reportCols, true);
$hasLocality = in_array('locality', $reportCols, true);
$hasProvince = in_array('province', $reportCols, true);
$hasPlaceSearchText = in_array('place_search_text', $reportCols, true);
$hasLocationText = in_array('location_text', $reportCols, true);
$hasTerritory = in_array('territory', $reportCols, true);
$hasGpsLat = in_array('gps_lat', $reportCols, true);
$hasGpsLng = in_array('gps_lng', $reportCols, true);
$hasLatitude = in_array('latitude', $reportCols, true);
$hasLongitude = in_array('longitude', $reportCols, true);

$locationCandidates = [];
if ($hasLocationText) {
    $locationCandidates[] = 'NULLIF(TRIM(r.location_text), "")';
}
if ($hasLocality) {
    $locationCandidates[] = 'NULLIF(TRIM(r.locality), "")';
}
if ($hasProvince) {
    $locationCandidates[] = 'NULLIF(TRIM(r.province), "")';
}
if ($hasTerritory) {
    $locationCandidates[] = 'NULLIF(TRIM(r.territory), "")';
}
if ($hasPlaceSearchText) {
    $locationCandidates[] = 'NULLIF(TRIM(r.place_search_text), "")';
}
$locationExpr = $locationCandidates !== []
    ? ('COALESCE(' . implode(', ', $locationCandidates) . ', "")')
    : '""';

$statusExpr = 'COALESCE(NULLIF(TRIM(r.workflow_status), ""), "Brouillon")';
$statusNormExpr = 'LOWER(REPLACE(REPLACE(REPLACE(' . $statusExpr . ', "é", "e"), "è", "e"), "ê", "e"))';

$gpsLatExpr = $hasGpsLat
    ? ($hasLatitude ? 'COALESCE(r.gps_lat, r.latitude)' : 'r.gps_lat')
    : ($hasLatitude ? 'r.latitude' : 'NULL');

$gpsLngExpr = $hasGpsLng
    ? ($hasLongitude ? 'COALESCE(r.gps_lng, r.longitude)' : 'r.gps_lng')
    : ($hasLongitude ? 'r.longitude' : 'NULL');

if ($usersHasRoleIdColumn) {
    $stmt = $pdo->prepare('SELECT u.id, COALESCE(r.code, "ORG_REPORTER") AS role, u.organization_id
                           FROM users u
                           LEFT JOIN roles r ON r.id = u.role_id
                           WHERE u.id = :id
                           LIMIT 1');
} elseif ($usersHasRoleColumn) {
    $stmt = $pdo->prepare('SELECT u.id, COALESCE(u.role, "ORG_REPORTER") AS role, u.organization_id
                           FROM users u
                           WHERE u.id = :id
                           LIMIT 1');
} else {
    $stmt = $pdo->prepare('SELECT u.id, "ORG_REPORTER" AS role, u.organization_id
                           FROM users u
                           WHERE u.id = :id
                           LIMIT 1');
}

$stmt->execute(['id' => $userId]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!is_array($currentUser)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Accès refusé.']);
    exit;
}

$userRole      = strtoupper((string) ($currentUser['role'] ?? 'ORG_REPORTER'));
$isLeadOrAdmin = in_array($userRole, ['ADMIN', 'CLUSTER_LEADER', 'GTMP_LEAD', 'GTMP_COLEAD', 'CLUSTER_PROTECTION', 'LEAD_GTMP'], true);

// --- Lecture des filtres (entrées) ---
$rawDateDebut  = trim((string) ($_POST['date_debut']    ?? $_GET['date_debut']    ?? ''));
$rawDateFin    = trim((string) ($_POST['date_fin']      ?? $_GET['date_fin']      ?? ''));
$rawOrgId      = trim((string) ($_POST['organisation_id'] ?? $_GET['organisation_id'] ?? ''));

// Valider les dates (format YYYY-MM-DD uniquement)
$dateDebut = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDateDebut) === 1) ? $rawDateDebut : null;
$dateFin   = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDateFin)   === 1) ? $rawDateFin   : null;

// Organisation: les rôles non lead/admin sont strictement bornés à leurs propres alertes
$filterOrgId = ($rawOrgId !== '' && ctype_digit($rawOrgId)) ? (int) $rawOrgId : null;

// --- Construction des clauses WHERE dynamiques ---
$conditions = [];
$params     = [];

if ($dateDebut !== null) {
    $conditions[] = 'r.created_at >= :date_debut';
    $params['date_debut'] = $dateDebut . ' 00:00:00';
}
if ($dateFin !== null) {
    $conditions[] = 'r.created_at <= :date_fin';
    $params['date_fin'] = $dateFin . ' 23:59:59';
}
if ($filterOrgId !== null && $isLeadOrAdmin) {
    if ($hasReportOrgId) {
        $conditions[] = 'r.organization_id = :org_id';
        $params['org_id'] = $filterOrgId;
    } elseif ($usersHasOrgIdColumn) {
        $conditions[] = 'u.organization_id = :org_id';
        $params['org_id'] = $filterOrgId;
    }
}

if ($isLeadOrAdmin) {
    // Vue globale decisionnelle: aucun brouillon visible.
    $conditions[] = $statusNormExpr . ' <> "brouillon"';
} else {
    // Vue organisation: ses alertes + alertes publiées/validées des autres.
    $conditions[] = '(r.' . $reporterFk . ' = :reporter_uid OR ' . $statusNormExpr . ' IN ("publie", "published", "approuve", "approved", "valide", "validee"))';
    $params['reporter_uid'] = $userId;
}

$whereSql = $conditions !== [] ? ' WHERE ' . implode(' AND ', $conditions) : '';

// --- Requête 1 : Statistiques KPI ---
$statsSql = '
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN LOWER(COALESCE(r.urgency_level, "")) LIKE "%crit%" THEN 1 ELSE 0 END) AS critiques,
        SUM(CASE WHEN ' . $statusNormExpr . ' IN ("soumis", "submitted", "en revue", "under_review") THEN 1 ELSE 0 END) AS attente,
        SUM(CASE WHEN ' . $statusNormExpr . ' IN ("approuve", "approved", "publie", "published", "valide", "validee") THEN 1 ELSE 0 END) AS valides
    FROM reports r' . $whereSql;

$statsStmt = $pdo->prepare($statsSql);
$statsStmt->execute($params);
$rawStats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$stats = [
    'total'     => (int) ($rawStats['total']     ?? 0),
    'critiques' => (int) ($rawStats['critiques']  ?? 0),
    'attente'   => (int) ($rawStats['attente']    ?? 0),
    'valides'   => (int) ($rawStats['valides']    ?? 0),
];

// --- Requête 2 : Marqueurs géolocalisés ---
$markersSql = '
    SELECT
        r.id,
        COALESCE(r.report_type, "FLASH") AS report_type,
        COALESCE(r.urgency_level, "Moyenne") AS urgency_level,
        ' . $locationExpr . ' AS location_text,
        ' . ($hasProvince ? 'r.province' : '""') . ' AS province,
        ' . ($hasLocality ? 'r.locality' : '""') . ' AS locality,
        ' . $gpsLatExpr . ' AS gps_lat,
        ' . $gpsLngExpr . ' AS gps_lng,
        ' . $statusExpr . ' AS workflow_status,
        r.created_at,
        COALESCE(r.incident_label, "") AS incident_label,
        COALESCE(NULLIF(TRIM(u.organization_name), ""), u.full_name, "Organisation inconnue") AS organization_name,
        r.' . $reporterFk . ' AS owner_user_id
    FROM reports r
    LEFT JOIN users u ON u.id = r.' . $reporterFk . $whereSql . '
    ORDER BY r.created_at DESC
    LIMIT 300';

$markersStmt = $pdo->prepare($markersSql);
$markersStmt->execute($params);
$rawMarkers = $markersStmt->fetchAll(PDO::FETCH_ASSOC);

// Nettoyage léger des valeurs en sortie (pas de données sensibles)
$markers = array_map(static function (array $row): array {
    return [
        'id'                => (int)   ($row['id']                ?? 0),
        'report_type'       => (string)($row['report_type']       ?? 'FLASH'),
        'urgency_level'     => (string)($row['urgency_level']     ?? 'Moyenne'),
        'location_text'     => (string)($row['location_text']     ?? ''),
        'province'          => (string)($row['province']          ?? ''),
        'locality'          => (string)($row['locality']          ?? ''),
        'gps_lat'           => $row['gps_lat']  !== null ? (float) $row['gps_lat']  : null,
        'gps_lng'           => $row['gps_lng']  !== null ? (float) $row['gps_lng']  : null,
        'workflow_status'   => (string)($row['workflow_status']   ?? ''),
        'created_at'       => (string)($row['created_at']       ?? ''),
        'incident_label'    => (string)($row['incident_label']    ?? ''),
        'organization_name' => (string)($row['organization_name'] ?? ''),
        'owner_user_id'     => (int)   ($row['owner_user_id']     ?? 0),
        'can_view_details'  => ((int) ($row['owner_user_id'] ?? 0)) === $userId,
    ];
}, $rawMarkers);

// --- Requête 3 : Tendance incidents (mois) ---
$trendSql = '
    SELECT DATE_FORMAT(r.created_at, "%Y-%m") AS period_label, COUNT(*) AS total
    FROM reports r'
    . $whereSql . '
    GROUP BY DATE_FORMAT(r.created_at, "%Y-%m")
    ORDER BY DATE_FORMAT(r.created_at, "%Y-%m") ASC
    LIMIT 18';

$trendStmt = $pdo->prepare($trendSql);
$trendStmt->execute($params);
$trendRows = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

$trend = [
    'labels' => [],
    'values' => [],
];

foreach ($trendRows as $row) {
    $trend['labels'][] = (string) ($row['period_label'] ?? '');
    $trend['values'][] = (int) ($row['total'] ?? 0);
}

// --- Requête 4 : Répartition par gravité ---
$severitySql = '
    SELECT
        CASE
            WHEN LOWER(COALESCE(r.urgency_level, "")) LIKE "%crit%" THEN "Critique"
            WHEN LOWER(COALESCE(r.urgency_level, "")) LIKE "%ele%" OR LOWER(COALESCE(r.urgency_level, "")) LIKE "%high%" THEN "Élevée"
            WHEN LOWER(COALESCE(r.urgency_level, "")) LIKE "%moy%" OR LOWER(COALESCE(r.urgency_level, "")) LIKE "%medium%" THEN "Moyenne"
            ELSE "Faible"
        END AS severity_bucket,
        COUNT(*) AS total
    FROM reports r'
    . $whereSql . '
    GROUP BY severity_bucket
    ORDER BY total DESC';

$severityStmt = $pdo->prepare($severitySql);
$severityStmt->execute($params);
$severityRows = $severityStmt->fetchAll(PDO::FETCH_ASSOC);

$severity = [
    'labels' => [],
    'values' => [],
];

foreach ($severityRows as $row) {
    $severity['labels'][] = (string) ($row['severity_bucket'] ?? 'Faible');
    $severity['values'][] = (int) ($row['total'] ?? 0);
}

// --- Réponse JSON ---
echo json_encode([
    'ok'      => true,
    'stats'   => $stats,
    'markers' => $markers,
    'charts'  => [
        'trend' => $trend,
        'severity' => $severity,
    ],
], JSON_UNESCAPED_UNICODE);
