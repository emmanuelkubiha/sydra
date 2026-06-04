<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=UTF-8');

$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';

if ((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

if (!isset($_SESSION['auth_user_id']) || (int) $_SESSION['auth_user_id'] <= 0) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Session expirée.']);
    exit;
}

$csrf = (string) ($_POST['csrf'] ?? '');
if ($csrf === '' || !isset($_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], $csrf)) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'message' => 'Jeton CSRF invalide.']);
    exit;
}

/**
 * Remplace les termes sensibles par des codes neutres.
 */
function appliquerCodification(string $texte): string
{
    $map = [
        'AFC/M23' => 'GA001',
        'Wazalendo' => 'GA002',
    ];

    return str_ireplace(array_keys($map), array_values($map), $texte);
}

/**
 * Vérifie l existence d une table.
 */
function tableExists(PDO $pdo, string $tableName): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name');
    $stmt->execute(['table_name' => $tableName]);
    return (int) $stmt->fetchColumn() > 0;
}

/**
 * Vérifie l existence d une colonne.
 */
function columnExists(PDO $pdo, string $tableName, string $columnName): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name');
    $stmt->execute([
        'table_name' => $tableName,
        'column_name' => $columnName,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

try {
    $pdo = db($config);

    $province = trim((string) ($_POST['province'] ?? ''));
    $territory = trim((string) ($_POST['territory'] ?? ''));
    $healthZone = trim((string) ($_POST['health_zone'] ?? ''));
    $groupement = trim((string) ($_POST['groupement'] ?? ''));
    $village = trim((string) ($_POST['village'] ?? ''));

    $incidentType = trim((string) ($_POST['incident_type'] ?? ''));
    $urgencyLevel = trim((string) ($_POST['urgency_level'] ?? 'Moyenne'));
    $victimsCount = max(0, (int) ($_POST['victims_count'] ?? 0));
    $displacedHouseholds = max(0, (int) ($_POST['displaced_households'] ?? 0));

    $description = appliquerCodification(trim((string) ($_POST['description'] ?? '')));
    $analyse = appliquerCodification(trim((string) ($_POST['analyse'] ?? '')));
    $priorityNeeds = trim((string) ($_POST['priority_needs'] ?? ''));
    $recommandations = appliquerCodification(trim((string) ($_POST['recommandations'] ?? '')));

    $gpsLatRaw = trim((string) ($_POST['gps_lat'] ?? ''));
    $gpsLngRaw = trim((string) ($_POST['gps_lng'] ?? ''));
    $gpsLat = is_numeric($gpsLatRaw) ? (float) $gpsLatRaw : null;
    $gpsLng = is_numeric($gpsLngRaw) ? (float) $gpsLngRaw : null;

    $statusInput = trim((string) ($_POST['status_action'] ?? 'Brouillon'));
    $status = strtolower($statusInput) === 'soumis' ? 'Soumis' : 'Brouillon';

    if ($province === '' || $territory === '' || $healthZone === '' || $groupement === '' || $village === '') {
        echo json_encode(['ok' => false, 'message' => 'Veuillez renseigner toute la localisation obligatoire.']);
        exit;
    }

    if ($incidentType === '' || $description === '' || $analyse === '' || $priorityNeeds === '' || $recommandations === '') {
        echo json_encode(['ok' => false, 'message' => 'Veuillez compléter les sections Faits et Analyse.']);
        exit;
    }

    if (!in_array($urgencyLevel, ['Faible', 'Moyenne', 'Elevee', 'Critique'], true)) {
        $urgencyLevel = 'Moyenne';
    }

    $userId = (int) $_SESSION['auth_user_id'];
    $title = 'Incident - ' . $incidentType . ' - ' . $village;
    $locationText = $province . ' / ' . $territory . ' / ' . $village;

    $dataMap = [
        'user_id' => $userId,
        'title' => $title,
        'report_type' => 'FLASH',
        'content' => $description,
        'location_text' => $locationText,
        'urgency_level' => $urgencyLevel,
        'workflow_status' => $status,
        'incident_label' => $incidentType,
        'province' => $province,
        'territory' => $territory,
        'health_zone' => $healthZone,
        'groupement' => $groupement,
        'village' => $village,
        'incident_type' => $incidentType,
        'gps_lat' => $gpsLat,
        'gps_lng' => $gpsLng,
        'victims_count' => $victimsCount,
        'displaced_households' => $displacedHouseholds,
        'analysis_text' => $analyse,
        'priority_needs_text' => $priorityNeeds,
        'recommendations_text' => $recommandations,
        'additional_notes' => "Besoins prioritaires:\n" . $priorityNeeds,
    ];

    if ($status === 'Soumis') {
        $dataMap['submitted_at'] = date('Y-m-d H:i:s');
    }

    $columns = [];
    $placeholders = [];
    $params = [];

    foreach ($dataMap as $col => $value) {
        if (columnExists($pdo, 'reports', $col)) {
            $columns[] = $col;
            $placeholders[] = ':' . $col;
            $params[$col] = $value;
        }
    }

    if ($columns === []) {
        echo json_encode(['ok' => false, 'message' => 'Structure de table reports invalide.']);
        exit;
    }

    $sql = 'INSERT INTO reports (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $paramType = PDO::PARAM_STR;
        if (is_int($value)) {
            $paramType = PDO::PARAM_INT;
        } elseif ($value === null) {
            $paramType = PDO::PARAM_NULL;
        }
        $stmt->bindValue(':' . $key, $value, $paramType);
    }
    $stmt->execute();

    $reportId = (int) $pdo->lastInsertId();

    if (tableExists($pdo, 'report_status_history')) {
        $historyStmt = $pdo->prepare('INSERT INTO report_status_history (report_id, status_label, event_note, changed_by)
                                      VALUES (:report_id, :status_label, :event_note, :changed_by)');
        $historyStmt->execute([
            'report_id' => $reportId,
            'status_label' => $status,
            'event_note' => $status === 'Soumis' ? 'Rapport soumis au Cluster.' : 'Brouillon enregistré.',
            'changed_by' => $userId,
        ]);
    }

    $uploadDir = __DIR__ . '/../uploads/reports';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
    $maxFileSize = 10 * 1024 * 1024;
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    $files = $_FILES['files'] ?? null;
    if (is_array($files)
        && isset($files['name'], $files['tmp_name'], $files['error'], $files['size'])
        && is_array($files['name'])) {

        $fileCount = count($files['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            $error = (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($error !== UPLOAD_ERR_OK) {
                continue;
            }

            $originalName = (string) ($files['name'][$i] ?? 'fichier');
            $tmpPath = (string) ($files['tmp_name'][$i] ?? '');
            $size = (int) ($files['size'][$i] ?? 0);
            if ($size <= 0 || $size > $maxFileSize) {
                continue;
            }

            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExtensions, true)) {
                continue;
            }

            $mimeType = (string) $finfo->file($tmpPath);
            if ($mimeType === '') {
                continue;
            }

            $safeBase = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($originalName, PATHINFO_FILENAME)) ?: 'piece_jointe';
            $storedName = $safeBase . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $targetPath = $uploadDir . '/' . $storedName;

            if (!move_uploaded_file($tmpPath, $targetPath)) {
                continue;
            }

            if (tableExists($pdo, 'report_attachments')) {
                $insertAttach = $pdo->prepare('INSERT INTO report_attachments (report_id, original_name, storage_path, mime_type, file_size)
                                               VALUES (:report_id, :original_name, :storage_path, :mime_type, :file_size)');
                $insertAttach->execute([
                    'report_id' => $reportId,
                    'original_name' => $originalName,
                    'storage_path' => 'uploads/reports/' . $storedName,
                    'mime_type' => $mimeType,
                    'file_size' => $size,
                ]);
            }
        }
    }

    echo json_encode([
        'ok' => true,
        'report_id' => $reportId,
        'status' => $status,
        'message' => $status === 'Soumis'
            ? 'Rapport soumis au Cluster avec succès.'
            : 'Brouillon enregistré avec succès.',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
