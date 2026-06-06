<?php

declare(strict_types=1);

session_start();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo 'Méthode non autorisée.';
    exit;
}

$userId = (int) ($_SESSION['auth_user_id'] ?? $_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(401);
    echo 'Session expirée.';
    exit;
}

$csrf = (string) ($_GET['csrf'] ?? '');
if ($csrf === '' || !isset($_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], $csrf)) {
    http_response_code(419);
    echo 'Token CSRF invalide.';
    exit;
}

$format = strtolower(trim((string) ($_GET['format'] ?? 'csv')));
if (!in_array($format, ['csv', 'xlsx', 'pdf'], true)) {
    http_response_code(422);
    echo 'Format non supporté.';
    exit;
}

$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoloadPath)) {
    require_once $autoloadPath;
}

$pdo = db($config);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$userColsStmt = $pdo->query('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "users"');
$userCols = array_map('strtolower', $userColsStmt->fetchAll(PDO::FETCH_COLUMN));

$reportColsStmt = $pdo->query('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "reports"');
$reportCols = array_map('strtolower', $reportColsStmt->fetchAll(PDO::FETCH_COLUMN));

$reporterFk = in_array('reporter_user_id', $reportCols, true)
    ? 'reporter_user_id'
    : (in_array('user_id', $reportCols, true) ? 'user_id' : null);

if ($reporterFk === null) {
    http_response_code(500);
    echo 'Colonne de rattachement utilisateur introuvable.';
    exit;
}

$roleCode = strtoupper((string) ($_SESSION['role'] ?? $_SESSION['role_code'] ?? ''));
if ($roleCode === '') {
    if (in_array('role', $userCols, true)) {
        $roleStmt = $pdo->prepare('SELECT UPPER(COALESCE(role, "")) FROM users WHERE id = :id LIMIT 1');
        $roleStmt->execute(['id' => $userId]);
        $roleCode = strtoupper((string) $roleStmt->fetchColumn());
    }

    if ($roleCode === '' && in_array('role_id', $userCols, true)) {
        $roleStmt = $pdo->prepare('SELECT UPPER(COALESCE(r.code, ""))
                                   FROM users u
                                   LEFT JOIN roles r ON r.id = u.role_id
                                   WHERE u.id = :id
                                   LIMIT 1');
        $roleStmt->execute(['id' => $userId]);
        $roleCode = strtoupper((string) $roleStmt->fetchColumn());
    }
}

$isLeadOrAdmin = in_array($roleCode, ['ADMIN', 'CLUSTER_LEADER', 'LEAD_GTMP', 'GTMP_LEAD', 'CLUSTER_PROTECTION'], true);

$scope = strtolower(trim((string) ($_GET['scope'] ?? '')));
$search = trim((string) ($_GET['search'] ?? ''));
$statusFilter = strtolower(trim((string) ($_GET['status'] ?? '')));
$typeFilter = strtolower(trim((string) ($_GET['type'] ?? '')));
$urgencyFilter = strtolower(trim((string) ($_GET['urgency'] ?? '')));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$mineOnly = ((string) ($_GET['mine_only'] ?? '0')) === '1';

$normalizeInput = static function (string $value): string {
    $value = strtolower(trim($value));
    return str_replace(['é', 'è', 'ê'], 'e', $value);
};

$statusFilter = $normalizeInput($statusFilter);
$typeFilter = $normalizeInput($typeFilter);
$urgencyFilter = $normalizeInput($urgencyFilter);

$statusExpr = in_array('workflow_status', $reportCols, true)
    ? 'COALESCE(NULLIF(TRIM(r.workflow_status), ""), "Brouillon")'
    : (in_array('status_id', $reportCols, true)
        ? 'COALESCE(NULLIF(TRIM(rs.label), ""), "Brouillon")'
        : '"Brouillon"');

$joinStatusSql = in_array('status_id', $reportCols, true)
    ? 'LEFT JOIN report_statuses rs ON rs.id = r.status_id'
    : '';

$provinceExpr = in_array('province', $reportCols, true)
    ? 'COALESCE(NULLIF(TRIM(r.province), ""), "-")'
    : '"-"';
$territoryExpr = in_array('territory', $reportCols, true)
    ? 'COALESCE(NULLIF(TRIM(r.territory), ""), "-")'
    : '"-"';
$incidentExpr = in_array('incident_label', $reportCols, true)
    ? 'COALESCE(NULLIF(TRIM(r.incident_label), ""), "-")'
    : (in_array('incident_type', $reportCols, true)
        ? 'COALESCE(NULLIF(TRIM(r.incident_type), ""), "-")'
        : (in_array('report_type', $reportCols, true) ? 'COALESCE(NULLIF(TRIM(r.report_type), ""), "-")' : '"-"'));
$severityExpr = in_array('urgency_level', $reportCols, true)
    ? 'COALESCE(NULLIF(TRIM(r.urgency_level), ""), "Moyenne")'
    : '"Moyenne"';
$typeExpr = in_array('report_type', $reportCols, true)
    ? 'COALESCE(NULLIF(TRIM(r.report_type), ""), "FLASH")'
    : '"FLASH"';
$locationExpr = in_array('location_text', $reportCols, true)
    ? 'COALESCE(NULLIF(TRIM(r.location_text), ""), "Non précisée")'
    : 'COALESCE(' . $provinceExpr . ', ' . $territoryExpr . ', "Non précisée")';

$statusNormalizedExpr = 'LOWER(REPLACE(REPLACE(REPLACE(' . $statusExpr . ', "é", "e"), "è", "e"), "ê", "e"))';
$typeNormalizedExpr = 'LOWER(REPLACE(REPLACE(REPLACE(' . $typeExpr . ', "é", "e"), "è", "e"), "ê", "e"))';
$urgencyNormalizedExpr = 'LOWER(REPLACE(REPLACE(REPLACE(' . $severityExpr . ', "é", "e"), "è", "e"), "ê", "e"))';

$orgExpr = 'COALESCE(NULLIF(TRIM(u.organization_name), ""), u.full_name, "Organisation")';

$sql = 'SELECT
            r.created_at AS report_date,
            ' . $orgExpr . ' AS organization_name,
            ' . $provinceExpr . ' AS province,
            ' . $territoryExpr . ' AS territory,
            ' . $incidentExpr . ' AS incident_type,
            ' . $severityExpr . ' AS severity,
            ' . $statusExpr . ' AS workflow_status
        FROM reports r
        LEFT JOIN users u ON u.id = r.' . $reporterFk . '
        ' . $joinStatusSql;

$params = [];
$where = [];

if (!$isLeadOrAdmin) {
    $where[] = 'r.' . $reporterFk . ' = :user_id';
    $params['user_id'] = $userId;
} else {
    // Confidentialité des brouillons: visibles uniquement par leur auteur.
    $where[] = '(' . $statusNormalizedExpr . ' <> "brouillon" OR r.' . $reporterFk . ' = :user_id)';
    $params['user_id'] = $userId;

    if ($scope === 'user' && $mineOnly) {
        $where[] = 'r.' . $reporterFk . ' = :mine_user_id';
        $params['mine_user_id'] = $userId;
    }
}

if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) === 1) {
    $where[] = 'r.created_at >= :date_from';
    $params['date_from'] = $dateFrom . ' 00:00:00';
}
if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) === 1) {
    $where[] = 'r.created_at <= :date_to';
    $params['date_to'] = $dateTo . ' 23:59:59';
}

if ($statusFilter !== '') {
    if ($statusFilter === 'approuve') {
        $where[] = $statusNormalizedExpr . ' IN ("approuve", "approuvé", "approved", "valide", "validee", "publie", "published")';
    } else {
        $where[] = $statusNormalizedExpr . ' LIKE :status_filter';
        $params['status_filter'] = '%' . $statusFilter . '%';
    }
}

if ($typeFilter !== '') {
    $where[] = $typeNormalizedExpr . ' = :type_filter';
    $params['type_filter'] = $typeFilter;
}

if ($urgencyFilter !== '') {
    $where[] = $urgencyNormalizedExpr . ' LIKE :urgency_filter';
    $params['urgency_filter'] = '%' . $urgencyFilter . '%';
}

if ($search !== '') {
    $where[] = '(
        LOWER(COALESCE(' . $orgExpr . ', "")) LIKE :search
        OR LOWER(COALESCE(' . $provinceExpr . ', "")) LIKE :search
        OR LOWER(COALESCE(' . $territoryExpr . ', "")) LIKE :search
        OR LOWER(COALESCE(' . $incidentExpr . ', "")) LIKE :search
        OR LOWER(COALESCE(' . $severityExpr . ', "")) LIKE :search
        OR LOWER(COALESCE(' . $statusExpr . ', "")) LIKE :search
        OR LOWER(COALESCE(' . $locationExpr . ', "")) LIKE :search
    )';
    $params['search'] = '%' . strtolower($search) . '%';
}

if ($where !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY r.created_at DESC LIMIT 5000';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$headers = ['Date', 'Organisation', 'Province', 'Territoire', 'Type Incident', 'Gravité', 'Statut'];
$fileDate = date('Ymd_His');

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="sydra_rapports_' . $fileDate . '.csv"');

    $out = fopen('php://output', 'wb');
    if ($out === false) {
        http_response_code(500);
        echo 'Impossible de générer le CSV.';
        exit;
    }

    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $headers, ';');

    foreach ($rows as $row) {
        fputcsv($out, [
            (string) ($row['report_date'] ?? ''),
            (string) ($row['organization_name'] ?? ''),
            (string) ($row['province'] ?? ''),
            (string) ($row['territory'] ?? ''),
            (string) ($row['incident_type'] ?? ''),
            (string) ($row['severity'] ?? ''),
            (string) ($row['workflow_status'] ?? ''),
        ], ';');
    }

    fclose($out);
    exit;
}

if ($format === 'xlsx') {
    if (!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
        http_response_code(500);
        echo 'PhpSpreadsheet indisponible. Installez les dépendances Composer.';
        exit;
    }

    if (!class_exists('PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate')) {
        http_response_code(500);
        echo 'Composants PhpSpreadsheet incomplets.';
        exit;
    }

    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Rapports SyDRA');

    foreach ($headers as $index => $label) {
        $cell = PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) . '1';
        $sheet->setCellValue($cell, $label);
    }

    $rowNum = 2;
    foreach ($rows as $row) {
        $sheet->setCellValue('A' . $rowNum, (string) ($row['report_date'] ?? ''));
        $sheet->setCellValue('B' . $rowNum, (string) ($row['organization_name'] ?? ''));
        $sheet->setCellValue('C' . $rowNum, (string) ($row['province'] ?? ''));
        $sheet->setCellValue('D' . $rowNum, (string) ($row['territory'] ?? ''));
        $sheet->setCellValue('E' . $rowNum, (string) ($row['incident_type'] ?? ''));
        $sheet->setCellValue('F' . $rowNum, (string) ($row['severity'] ?? ''));
        $sheet->setCellValue('G' . $rowNum, (string) ($row['workflow_status'] ?? ''));
        $rowNum++;
    }

    foreach (range('A', 'G') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="sydra_rapports_' . $fileDate . '.xlsx"');

    $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

if (!class_exists('TCPDF')) {
    http_response_code(500);
    echo 'TCPDF indisponible. Installez les dépendances Composer.';
    exit;
}

$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('SyDRA');
$pdf->SetAuthor('SyDRA');
$pdf->SetTitle('Export Rapports SyDRA');
$pdf->SetMargins(10, 12, 10);
$pdf->SetAutoPageBreak(true, 12);
$pdf->AddPage();

$logoPath = __DIR__ . '/../assets/img/sydra-logo/BLEU-PRIMARY-SYDRA-LOGO.png';
if (is_file($logoPath)) {
    $pdf->Image($logoPath, 10, 8, 26, 0, 'PNG');
}

$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(0, 91, 187);
$pdf->SetXY(40, 11);
$pdf->Cell(0, 8, 'SyDRA - Export des Rapports', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(90, 90, 90);
$pdf->SetX(40);
$pdf->Cell(0, 6, 'Généré le ' . date('d/m/Y H:i'), 0, 1, 'L');
$pdf->Ln(2);

$tableHtml = '<table border="1" cellpadding="4" cellspacing="0">';
$tableHtml .= '<thead><tr style="background-color:#005BBB;color:#ffffff;">';
foreach ($headers as $header) {
    $tableHtml .= '<th><b>' . htmlspecialchars($header, ENT_QUOTES, 'UTF-8') . '</b></th>';
}
$tableHtml .= '</tr></thead><tbody>';

foreach ($rows as $row) {
    $tableHtml .= '<tr>'
        . '<td>' . htmlspecialchars((string) ($row['report_date'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
        . '<td>' . htmlspecialchars((string) ($row['organization_name'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
        . '<td>' . htmlspecialchars((string) ($row['province'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
        . '<td>' . htmlspecialchars((string) ($row['territory'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
        . '<td>' . htmlspecialchars((string) ($row['incident_type'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
        . '<td>' . htmlspecialchars((string) ($row['severity'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
        . '<td>' . htmlspecialchars((string) ($row['workflow_status'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
        . '</tr>';
}

$tableHtml .= '</tbody></table>';

$pdf->SetTextColor(20, 20, 20);
$pdf->SetFont('helvetica', '', 8);
$pdf->writeHTML($tableHtml, true, false, true, false, '');
$pdf->Output('sydra_rapports_' . $fileDate . '.pdf', 'D');
exit;
