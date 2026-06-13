<?php

/**
 * api/save_report.php
 *
 * ROLE DU FICHIER:
 * - Reçoit la soumission AJAX du Wizard Rapportage (FLASH).
 * - Valide les champs obligatoires, applique la codification sensible,
 *   puis insère le rapport dans la table reports.
 * - Enregistre l'historique de statut et gère l'upload des pièces jointes.
 *
 * Entrées principales:
 * - Localisation: province, territory, health_zone, groupement, village
 * - Incident: incident_type, urgency_level, victims_count, displaced_households
 * - Contenu: description, analyse, priority_needs, recommandations
 * - Contrôle: status_action (Brouillon/Soumis), csrf
 *
 * Sortie:
 * - JSON { ok, report_id, status, message }
 */

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

    // Colonnes correctes : facts_text, analysis_text, recommendations_text
    // On accepte aussi les anciens noms (description/analyse) pour compatibilité IA prefill
    $factsText       = appliquerCodification(trim((string) ($_POST['facts_text']          ?? $_POST['description'] ?? '')));
    $analyse         = appliquerCodification(trim((string) ($_POST['analysis_text']       ?? $_POST['analyse']     ?? '')));
    $priorityNeeds   = trim((string) ($_POST['priority_needs'] ?? ''));
    $recommandations = appliquerCodification(trim((string) ($_POST['recommendations_text'] ?? $_POST['recommandations'] ?? '')));

    $gpsLatRaw = trim((string) ($_POST['gps_lat'] ?? ''));
    $gpsLngRaw = trim((string) ($_POST['gps_lng'] ?? ''));
    $gpsLat = is_numeric($gpsLatRaw) ? (float) $gpsLatRaw : null;
    $gpsLng = is_numeric($gpsLngRaw) ? (float) $gpsLngRaw : null;

    $statusInput = trim((string) ($_POST['status_action'] ?? 'Brouillon'));
    $status = strtolower($statusInput) === 'soumis' ? 'Soumis' : 'Brouillon';

    if ($status === 'Soumis') {
        if ($province === '' || $village === '') {
            echo json_encode(['ok' => false, 'message' => 'Veuillez renseigner au minimum la Province et le Village.']);
            exit;
        }

        if ($incidentType === '' || $factsText === '' || $analyse === '' || $recommandations === '') {
            echo json_encode(['ok' => false, 'message' => 'Veuillez compléter les sections Faits et Analyse.']);
            exit;
        }
    }

    if (!in_array($urgencyLevel, ['Faible', 'Moyenne', 'Elevee', 'Critique'], true)) {
        $urgencyLevel = 'Moyenne';
    }

    $userId = (int) $_SESSION['auth_user_id'];
    // organization_id : récupéré depuis la session (stocké au login)
    $orgId = (int) ($_SESSION['organization_id'] ?? $_SESSION['org_id'] ?? 0);

    // Si pas en session, on le récupère depuis la table users
    if ($orgId <= 0) {
        $orgStmt = $pdo->prepare('SELECT organization_id FROM users WHERE id = :id LIMIT 1');
        $orgStmt->execute(['id' => $userId]);
        $orgRow = $orgStmt->fetch(PDO::FETCH_ASSOC);
        $orgId = (int) ($orgRow['organization_id'] ?? 0);
    }

    // status_id : résoudre DRAFT=1 / SUBMITTED=2 depuis report_statuses
    $statusCode  = $status === 'Soumis' ? 'SUBMITTED' : 'DRAFT';
    $statusIdStmt = $pdo->prepare('SELECT id FROM report_statuses WHERE code = :code LIMIT 1');
    $statusIdStmt->execute(['code' => $statusCode]);
    $statusId = (int) ($statusIdStmt->fetchColumn() ?: 1); // fallback : 1 = DRAFT

    $requestedDraftId = (int) ($_POST['draft_id'] ?? $_POST['id_brouillon'] ?? $_POST['report_id'] ?? 0);
    $titleIncident = $incidentType !== '' ? $incidentType : 'Incident en cours';
    $titleVillage = $village !== '' ? $village : 'localisation a completer';
    $title = 'Incident - ' . $titleIncident . ' - ' . $titleVillage;
    $locationText = trim($province . ' / ' . $territory . ' / ' . $village, ' /');

    // Génération du reference_code unique (format : SY-YYYYMMDD-XXXX)
    // On s'assure de l'unicité en ajoutant un suffixe aléatoire hexadécimal
    do {
        $refCode = 'SY-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
        $refCheckStmt = $pdo->prepare('SELECT COUNT(*) FROM reports WHERE reference_code = :ref');
        $refCheckStmt->execute(['ref' => $refCode]);
        $refExists = (int) $refCheckStmt->fetchColumn() > 0;
    } while ($refExists);

    $dataMap = [
        // Colonnes NOT NULL obligatoires
        'reporter_user_id' => $userId,
        'organization_id'  => $orgId,
        'status_id'        => $statusId,
        // Fallback si ancienne colonne user_id existe encore
        'user_id'          => $userId,
        'title'            => $title,
        'report_type'      => 'FLASH',
        // Référence unique (générée uniquement pour les nouveaux rapports)
        'reference_code'   => $refCode,
        // Colonnes texte correctes (facts_text, analysis_text, recommendations_text)
        'facts_text'            => $factsText,
        'analysis_text'         => $analyse,
        'priority_needs_text'   => $priorityNeeds,
        'recommendations_text'  => $recommandations,
        // Champs legacy pour compatibilité ancienne structure
        'content'               => $factsText,
        'additional_notes'      => 'Besoins prioritaires:' . PHP_EOL . $priorityNeeds,
        // Localisation
        'location_text'    => $locationText,
        'province'         => $province,
        'territory'        => $territory,
        'health_zone'      => $healthZone,
        'groupement'       => $groupement,
        'village'          => $village,
        // Incident
        'urgency_level'    => $urgencyLevel,
        'workflow_status'  => $status,
        'incident_label'   => $incidentType,
        'incident_type'    => $incidentType,
        // GPS
        'gps_lat'          => $gpsLat,
        'gps_lng'          => $gpsLng,
        // Bilan
        'victims_count'       => $victimsCount,
        'displaced_households' => $displacedHouseholds,
    ];

    if (isset($_POST['is_ai_generated'])) {
        $dataMap['is_ai_generated'] = (int) $_POST['is_ai_generated'];
    }

    if ($status === 'Soumis') {
        $dataMap['submitted_at'] = date('Y-m-d H:i:s');
    }

    $columns = [];
    $params = [];

    foreach ($dataMap as $col => $value) {
        if (columnExists($pdo, 'reports', $col)) {
            $columns[] = $col;
            $params[$col] = $value;
        }
    }

    if ($columns === []) {
        echo json_encode(['ok' => false, 'message' => 'Structure de table reports invalide.']);
        exit;
    }

    $statusExpr = 'LOWER(REPLACE(REPLACE(REPLACE(COALESCE(NULLIF(TRIM(workflow_status), ""), "brouillon"), "é", "e"), "è", "e"), "ê", "e"))';
    $existingDraftId = 0;

    // Recherche du brouillon existant — on utilise reporter_user_id (vrai nom)
    // avec fallback sur user_id si l'ancienne colonne existe encore
    $userColExists = columnExists($pdo, 'reports', 'reporter_user_id') ? 'reporter_user_id' : 'user_id';

    if ($requestedDraftId > 0) {
        $targetStmt = $pdo->prepare(
            'SELECT id FROM reports
             WHERE id = :id
               AND ' . $userColExists . ' = :user_id
               AND ' . $statusExpr . ' = "brouillon"
             LIMIT 1'
        );
        $targetStmt->execute(['id' => $requestedDraftId, 'user_id' => $userId]);
        $target = $targetStmt->fetch();
        if (is_array($target)) {
            $existingDraftId = (int) ($target['id'] ?? 0);
        }
    }

    if ($existingDraftId <= 0) {
        $draftStmt = $pdo->prepare(
            'SELECT id FROM reports
             WHERE ' . $userColExists . ' = :user_id
               AND ' . $statusExpr . ' = "brouillon"
             ORDER BY created_at DESC
             LIMIT 1'
        );
        $draftStmt->execute(['user_id' => $userId]);
        $draft = $draftStmt->fetch();
        if (is_array($draft)) {
            $existingDraftId = (int) ($draft['id'] ?? 0);
        }
    }

    $reportId = 0;
    $didUpdate = false;

    if ($existingDraftId > 0) {
        $setParts = [];
        foreach ($columns as $col) {
            // Ne pas écraser la FK utilisateur ni le code de référence lors d'un UPDATE
            if ($col === 'user_id' || $col === 'reporter_user_id' || $col === 'reference_code') {
                continue;
            }
            $setParts[] = $col . ' = :' . $col;
        }

        if ($setParts === []) {
            echo json_encode(['ok' => false, 'message' => 'Aucune colonne modifiable disponible.']);
            exit;
        }

        $params['id'] = $existingDraftId;
        $updateSql = 'UPDATE reports SET ' . implode(', ', $setParts) . ' WHERE id = :id LIMIT 1';
        $stmt = $pdo->prepare($updateSql);

        // Ne binder que les colonnes effectivement dans le SET + :id
        $usedInUpdate = array_merge(
            array_map(fn (string $part): string => explode(' ', $part)[0], $setParts),
            ['id']
        );
        foreach ($usedInUpdate as $key) {
            $value = $params[$key] ?? null;
            $paramType = PDO::PARAM_STR;
            if (is_int($value))   { $paramType = PDO::PARAM_INT; }
            elseif ($value === null) { $paramType = PDO::PARAM_NULL; }
            $stmt->bindValue(':' . $key, $value, $paramType);
        }
        $stmt->execute();

        $reportId = $existingDraftId;
        $didUpdate = true;
    } else {
        $placeholders = array_map(static fn (string $col): string => ':' . $col, $columns);
        $insertSql = 'INSERT INTO reports (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = $pdo->prepare($insertSql);
        // Ne binder que les colonnes effectivement dans l'INSERT
        foreach ($columns as $col) {
            $value = $params[$col] ?? null;
            $paramType = PDO::PARAM_STR;
            if (is_int($value))    { $paramType = PDO::PARAM_INT; }
            elseif ($value === null) { $paramType = PDO::PARAM_NULL; }
            $stmt->bindValue(':' . $col, $value, $paramType);
        }
        $stmt->execute();
        $reportId = (int) $pdo->lastInsertId();
    }

    if (tableExists($pdo, 'report_status_history')) {
        $historyStmt = $pdo->prepare('INSERT INTO report_status_history (report_id, status_label, event_note, changed_by)
                                      VALUES (:report_id, :status_label, :event_note, :changed_by)');
        $historyStmt->execute([
            'report_id' => $reportId,
            'status_label' => $status,
            'event_note' => $status === 'Soumis'
                ? ($didUpdate ? 'Brouillon finalisé et soumis au Cluster.' : 'Rapport soumis au Cluster.')
                : ($didUpdate ? 'Brouillon mis à jour.' : 'Brouillon enregistré.'),
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
