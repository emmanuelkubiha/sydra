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

$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';
require_once __DIR__ . '/advanced_stats_service.php';

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoloadPath)) {
    require_once $autoloadPath;
}

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

$pdo = db($config);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$context = advanced_stats_context($pdo);
$filters = advanced_stats_parse_filters();
$roleCode = advanced_stats_resolve_role($pdo, $userId);
$orgId = advanced_stats_resolve_org_id($pdo, $userId);

$rows = advanced_stats_fetch_raw_rows($pdo, $context, $filters, $roleCode, $userId, $orgId);

$headers = [
    'ID',
    'Date',
    'Organisation',
    'Territoire',
    'Incident',
    'Gravite',
    'Statut',
    'Victimes',
    'Menages deplaces',
];

$spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Stats Avancees');

foreach ($headers as $index => $label) {
    $cell = PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) . '1';
    $sheet->setCellValue($cell, $label);
}

$rowNum = 2;
foreach ($rows as $row) {
    $sheet->setCellValue('A' . $rowNum, (int) ($row['id'] ?? 0));
    $sheet->setCellValue('B' . $rowNum, (string) ($row['created_at'] ?? ''));
    $sheet->setCellValue('C' . $rowNum, (string) ($row['organization_name'] ?? ''));
    $sheet->setCellValue('D' . $rowNum, (string) ($row['territory'] ?? ''));
    $sheet->setCellValue('E' . $rowNum, (string) ($row['incident_type'] ?? ''));
    $sheet->setCellValue('F' . $rowNum, (string) ($row['severity'] ?? ''));
    $sheet->setCellValue('G' . $rowNum, (string) ($row['workflow_status'] ?? ''));
    $sheet->setCellValue('H' . $rowNum, (int) ($row['victims_count'] ?? 0));
    $sheet->setCellValue('I' . $rowNum, (int) ($row['displaced_households'] ?? 0));
    $rowNum++;
}

foreach (range('A', 'I') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$fileDate = date('Ymd_His');
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="sydra_stats_avancees_' . $fileDate . '.xlsx"');

$writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$writer->save('php://output');
exit;
