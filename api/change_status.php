<?php

/**
 * api/change_status.php
 *
 * ROLE DU FICHIER:
 * - Endpoint backend de la boucle de décision Lead/Admin.
 * - Reçoit une décision (VALIDATE, REJECT, REQUEST_INFO) depuis les modals.
 * - Met à jour le statut dans reports.
 * - Enregistre la trace dans report_status_history.
 * - Crée une notification ciblée pour l'organisation qui a soumis l'alerte.
 * - Déclenche l'email transactionnel correspondant via envoyerNotificationEmail().
 *
 * Entrées POST:
 * - report_id (int)
 * - action (VALIDATE|REJECT|REQUEST_INFO)
 * - comment (string)
 * - csrf (token)
 *
 * Sortie:
 * - JSON { ok, report_id, action, status }
 */

declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$sessionUserId = (int) ($_SESSION['auth_user_id'] ?? $_SESSION['user_id'] ?? 0);
if ($sessionUserId <= 0) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Session expirée']);
    exit;
}

$csrf = (string) ($_POST['csrf'] ?? '');
if ($csrf === '' || !isset($_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], $csrf)) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'message' => 'Token CSRF invalide']);
    exit;
}

$reportId = (int) ($_POST['report_id'] ?? 0);
$action = strtoupper(trim((string) ($_POST['action'] ?? '')));
$comment = trim((string) ($_POST['comment'] ?? ''));

$allowed = ['VALIDATE', 'REJECT', 'REQUEST_INFO'];
if ($reportId <= 0 || !in_array($action, $allowed, true)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Paramètres invalides']);
    exit;
}

$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';
$pdo = db($config);

$reportColsStmt = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t');
$reportColsStmt->execute(['t' => 'reports']);
$reportCols = array_map('strtolower', $reportColsStmt->fetchAll(PDO::FETCH_COLUMN));

$historyColsStmt = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t');
$historyColsStmt->execute(['t' => 'report_status_history']);
$historyCols = array_map('strtolower', $historyColsStmt->fetchAll(PDO::FETCH_COLUMN));

$statusMap = [
    'VALIDATE' => ['id' => 3, 'label' => 'Approuvé', 'mail_type' => 'alerte_validee', 'notif_title' => 'Alerte validée', 'status_code' => 'APPROVED'],
    'REJECT' => ['id' => 4, 'label' => 'Rejeté', 'mail_type' => 'demande_correction', 'notif_title' => 'Alerte rejetée', 'status_code' => 'REJECTED'],
    'REQUEST_INFO' => ['id' => 2, 'label' => 'Demande information', 'mail_type' => 'demande_correction', 'notif_title' => 'Demande d\'information', 'status_code' => 'UNDER_REVIEW'],
];
$target = $statusMap[$action];

$workflowExpr = in_array('workflow_status', $reportCols, true);
$statusIdExpr = in_array('status_id', $reportCols, true);
$reporterCol = in_array('reporter_user_id', $reportCols, true) ? 'reporter_user_id' : (in_array('user_id', $reportCols, true) ? 'user_id' : null);
$locationExpr = in_array('location_text', $reportCols, true)
    ? 'location_text'
    : (in_array('province', $reportCols, true)
        ? 'province'
        : (in_array('locality', $reportCols, true) ? 'locality' : 'NULL'));
if ($reporterCol === null) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Colonne reporter introuvable']);
    exit;
}

// Si la table de référence existe, on résout le status_id par code pour éviter
// les erreurs sur les environnements où les IDs diffèrent.
if ($statusIdExpr) {
    try {
        $statusIdStmt = $pdo->prepare('SELECT id FROM report_statuses WHERE UPPER(code) = :code LIMIT 1');
        $statusIdStmt->execute(['code' => strtoupper((string) $target['status_code'])]);
        $resolvedStatusId = (int) ($statusIdStmt->fetchColumn() ?: 0);
        if ($resolvedStatusId > 0) {
            $target['id'] = $resolvedStatusId;
        }
    } catch (Throwable $e) {
        // Fallback silencieux vers l'ID mappé si la table report_statuses n'existe pas.
    }
}

$reportTypeExpr = in_array('report_type', $reportCols, true) ? 'report_type' : '"FLASH"';
$reportStmt = $pdo->prepare('SELECT id, ' . $reporterCol . ' AS reporter_user_id, COALESCE(' . $locationExpr . ', "") AS location_name, COALESCE(' . $reportTypeExpr . ', "FLASH") AS report_type FROM reports WHERE id = :id LIMIT 1');
$reportStmt->execute(['id' => $reportId]);
$report = $reportStmt->fetch(PDO::FETCH_ASSOC);

if (!is_array($report)) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Rapport introuvable']);
    exit;
}

$reporterUserId = (int) ($report['reporter_user_id'] ?? 0);
if ($reporterUserId <= 0) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Reporter introuvable']);
    exit;
}

$reportUrl = rtrim((string) ($config['app_url'] ?? ''), '/') . '/index.php?page=rapportage-details&id=' . $reportId;

$pdo->beginTransaction();

try {
    $set = [];
    $params = ['id' => $reportId];
    if ($workflowExpr) {
        $set[] = 'workflow_status = :workflow_status';
        $params['workflow_status'] = $target['label'];
    }
    if ($statusIdExpr) {
        $set[] = 'status_id = :status_id';
        $params['status_id'] = $target['id'];
    }
    if ($set === []) {
        throw new RuntimeException('Aucune colonne de statut disponible sur reports.');
    }

    $upd = $pdo->prepare('UPDATE reports SET ' . implode(', ', $set) . ' WHERE id = :id');
    $upd->execute($params);

    if ($historyCols !== []) {
        $insertCols = [];
        $insertVals = [];
        $insertParams = [];

        $map = [
            'report_id' => $reportId,
            'action' => $action,
            'status_label' => $target['label'],
            'comment' => $comment,
            'changed_by' => $sessionUserId,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        foreach ($map as $col => $val) {
            if (in_array($col, $historyCols, true)) {
                $insertCols[] = $col;
                $insertVals[] = ':' . $col;
                $insertParams[$col] = $val;
            }
        }

        if ($insertCols !== []) {
            $histSql = 'INSERT INTO report_status_history (' . implode(', ', $insertCols) . ') VALUES (' . implode(', ', $insertVals) . ')';
            $histStmt = $pdo->prepare($histSql);
            $histStmt->execute($insertParams);
        }
    }

    $notifColsStmt = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t');
    $notifColsStmt->execute(['t' => 'notifications']);
    $notifCols = array_map('strtolower', $notifColsStmt->fetchAll(PDO::FETCH_COLUMN));

    $notifData = [
        'user_id' => $reporterUserId,
        'report_id' => $reportId,
        'status_code' => (string) $target['status_code'],
        'title' => $target['notif_title'],
        'message' => $comment !== '' ? $comment : ('Statut mis à jour: ' . $target['label']),
        'target_url' => 'index.php?page=rapportage-details&id=' . $reportId,
        'created_at' => date('Y-m-d H:i:s'),
        'is_read' => 0,
    ];

    $cols = [];
    $vals = [];
    $bind = [];
    foreach ($notifData as $col => $val) {
        if (in_array($col, $notifCols, true)) {
            $cols[] = $col;
            $vals[] = ':' . $col;
            $bind[$col] = $val;
        }
    }
    if ($cols !== []) {
        $notifStmt = $pdo->prepare('INSERT INTO notifications (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')');
        $notifStmt->execute($bind);
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Echec traitement statut', 'error' => $e->getMessage()]);
    exit;
}

$userStmt = $pdo->prepare('SELECT email, COALESCE(organization_name, full_name, "Organisation") AS org_name FROM users WHERE id = :id LIMIT 1');
$userStmt->execute(['id' => $reporterUserId]);
$orga = $userStmt->fetch(PDO::FETCH_ASSOC) ?: ['email' => '', 'org_name' => 'Organisation'];

$mailResult = ['success' => false, 'error' => 'Destinataire email absent.'];
$email = trim((string) ($orga['email'] ?? ''));
if ($email !== '') {
    $payload = [
        'nom' => (string) ($orga['org_name'] ?? 'Organisation'),
        'commentaire' => $comment,
        'edit_url' => $reportUrl,
        'details_url' => $reportUrl,
        'lieu' => (string) ($report['location_name'] ?? ''),
        'type_incident' => (string) ($report['report_type'] ?? 'FLASH'),
    ];
    $mailResult = envoyerNotificationEmail((string) $target['mail_type'], $email, $payload);
}

echo json_encode([
    'ok' => true,
    'report_id' => $reportId,
    'action' => $action,
    'status' => $target['label'],
    'server_status' => 'updated',
    'mail' => [
        'attempted' => $email !== '',
        'success' => (bool) ($mailResult['success'] ?? false),
        'error' => (string) ($mailResult['error'] ?? ''),
    ],
]);
