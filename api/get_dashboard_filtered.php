<?php
declare(strict_types=1);

// Mission 1: Bloquer l'affichage des erreurs PHP et forcer le JSON
error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=UTF-8');

try {
    session_start();

    if (!isset($_SESSION['auth_user_id']) || (int) $_SESSION['auth_user_id'] <= 0) {
        throw new Exception('Session expirée.');
    }

    $config = require __DIR__ . '/../config/config.php';
    require __DIR__ . '/../config/database.php';
    $pdo = db($config);
    $userId = (int) $_SESSION['auth_user_id'];

    // 1. Identifier le rôle de l'utilisateur
    $stmtRole = $pdo->prepare('SELECT u.id, u.organization_id, COALESCE(r.code, "ORG_REPORTER") AS role
                               FROM users u
                               LEFT JOIN roles r ON r.id = u.role_id
                               WHERE u.id = :id LIMIT 1');
    $stmtRole->execute(['id' => $userId]);
    $currentUser = $stmtRole->fetch(PDO::FETCH_ASSOC);

    if (!$currentUser) {
        throw new Exception('Utilisateur introuvable.');
    }

    $userRole = strtoupper((string) ($currentUser['role'] ?? 'ORG_REPORTER'));
    $userOrgId = (int) ($currentUser['organization_id'] ?? 0);
    $isDecisionRole = in_array($userRole, ['ADMIN', 'GTMP_LEAD', 'LEAD_GTMP', 'GTMP_COLEAD', 'CLUSTER_LEADER'], true);

    // Mission 2: Récupérer les paramètres POST ou GET
    $startDate = trim((string) ($_POST['start_date'] ?? $_GET['start_date'] ?? $_POST['date_debut'] ?? $_GET['date_debut'] ?? ''));
    $endDate   = trim((string) ($_POST['end_date']   ?? $_GET['end_date']   ?? $_POST['date_fin']   ?? $_GET['date_fin']   ?? ''));
    $filterOrgId = trim((string) ($_POST['organization_id'] ?? $_GET['organization_id'] ?? $_POST['organisation_id'] ?? $_GET['organisation_id'] ?? ''));

    // 1. Initialisation de la clause de base (exclure les brouillons)
    $whereSql = "WHERE LOWER(r.workflow_status) != 'brouillon'";
    $params = [];

    // 2. Gestion des dates (si elles sont envoyées)
    if ($startDate !== '' && $endDate !== '') {
        $whereSql .= " AND r.created_at BETWEEN :start AND :end";
        $params['start'] = $startDate . ' 00:00:00';
        $params['end'] = $endDate . ' 23:59:59';
    }

    // 3. LOGIQUE DES RÔLES ET FILTRE D'ORGANISATION
    $adminRoles = ['ADMIN', 'GTMP_LEAD', 'LEAD_GTMP', 'GTMP_COLEAD', 'CLUSTER_LEADER', 'CLUSTER_PROTECTION'];

    if (in_array($userRole, $adminRoles, true)) {
        // Cas A : L'utilisateur est un Lead/Admin
        // Il voit TOUT par défaut. On n'applique le filtre d'organisation QUE s'il en a choisi une spécifique.
        if ($filterOrgId !== '' && $filterOrgId !== 'all' && $filterOrgId !== 'Toutes les organisations' && $filterOrgId !== 'Toutes') {
            $whereSql .= " AND r.organization_id = :filter_org_id";
            $params['filter_org_id'] = (int) $filterOrgId;
        }
    } else {
        // Cas B : L'utilisateur est une Organisation Rapportante (ORG_REPORTER)
        // On ignore complètement ce qu'il choisit dans le filtre, on le VERROUILLE sur son organisation.
        $whereSql .= " AND r.organization_id = :session_org_id";
        $params['session_org_id'] = $userOrgId;
    }

    // -- KPI --
    $kpiSql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN LOWER(r.urgency_level) LIKE '%crit%' THEN 1 ELSE 0 END) as critiques,
        SUM(CASE WHEN LOWER(r.workflow_status) IN ('soumis', 'submitted', 'en revue') THEN 1 ELSE 0 END) as en_attente,
        SUM(CASE WHEN LOWER(r.workflow_status) IN ('validé', 'valide', 'approuvé', 'publié') THEN 1 ELSE 0 END) as valides
    FROM reports r $whereSql";
    
    $kpiStmt = $pdo->prepare($kpiSql);
    $kpiStmt->execute($params);
    $kpiRow = $kpiStmt->fetch(PDO::FETCH_ASSOC);

    // -- CARTE (Leaflet) --
    // Gestion robuste des colonnes pour la carte.
    // SyDRA utilise parfois gps_lat ou latitude, gps_lng ou longitude, incident_label ou incident_type, locality ou village.
    // Mission 1: Corriger la requête SQL avec COALESCE pour récupérer les anciennes et nouvelles colonnes
    $mapSql = "SELECT r.id, 
                      COALESCE(r.gps_lat, r.latitude) as lat, 
                      COALESCE(r.gps_lng, r.longitude) as lng, 
                      COALESCE(r.incident_label, r.incident_type) as type, 
                      COALESCE(r.locality, r.village) as village, 
                      r.urgency_level, 
                      r.report_type, r.created_at, r.workflow_status, r.reporter_user_id, 
                      o.name AS organization_name 
               FROM reports r 
               LEFT JOIN organizations o ON r.organization_id = o.id 
               $whereSql LIMIT 300";
    $mapStmt = $pdo->prepare($mapSql);
    $mapStmt->execute($params);
    
    $mapMarkers = [];
    foreach ($mapStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $urgency = trim((string) ($row['urgency_level'] ?? 'Normale'));
        $mapMarkers[] = [
            'id'    => (int) ($row['id'] ?? 0),
            'lat'   => $row['lat'] !== null ? (float) $row['lat'] : null,
            'lng'   => $row['lng'] !== null ? (float) $row['lng'] : null,
            'title' => (string) ($row['type'] ?? 'Incident') . ' - ' . (string) ($row['village'] ?? ''),
            'urgency_level' => $urgency,
            'gps_lat' => $row['lat'],
            'gps_lng' => $row['lng'],
            'location_text' => $row['village'],
            'incident_label' => $row['type'],
            // Mission 2: Corriger le mapping du JSON
            'report_type'       => $row['report_type'] ?? 'FLASH',
            'organization_name' => $row['organization_name'] ?? 'Organisation inconnue',
            'created_at'        => $row['created_at'],
            'workflow_status'   => $row['workflow_status'],
            'owner_user_id'     => (int) ($row['reporter_user_id'] ?? 0)
        ];
    }

    // -- GRAPHIQUES --
    $chartSql = "SELECT DATE_FORMAT(r.created_at, '%Y-%m') as period, COUNT(*) as total FROM reports r $whereSql GROUP BY period ORDER BY period ASC LIMIT 12";
    $chartStmt = $pdo->prepare($chartSql);
    $chartStmt->execute($params);
    $chartData = $chartStmt->fetchAll(PDO::FETCH_ASSOC);

    // Mission 3: Format de la réponse JSON stricte
    echo json_encode([
        'success'     => true,
        'kpi'         => [
            'total'      => (int) ($kpiRow['total'] ?? 0),
            'critiques'  => (int) ($kpiRow['critiques'] ?? 0),
            'en_attente' => (int) ($kpiRow['en_attente'] ?? 0),
            'valides'    => (int) ($kpiRow['valides'] ?? 0)
        ],
        'map_markers' => $mapMarkers,
        'chart_data'  => $chartData
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
