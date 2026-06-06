<?php

declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!in_array(($_SERVER['REQUEST_METHOD'] ?? 'GET'), ['GET', 'POST'], true)) {
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

$csrf = (string) ($_POST['csrf'] ?? $_GET['csrf'] ?? '');
if ($csrf === '' || !isset($_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], $csrf)) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'message' => 'Token CSRF invalide']);
    exit;
}

$notifId = (int) ($_POST['notification_id'] ?? $_GET['id'] ?? 0);
if ($notifId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'id notification invalide']);
    exit;
}

$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';
$pdo = db($config);

$userId = $sessionUserId;

$colStmt = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table');
$colStmt->execute(['table' => 'notifications']);
$columns = array_map('strtolower', $colStmt->fetchAll(PDO::FETCH_COLUMN));

$hasIsRead = in_array('is_read', $columns, true);
$hasReadAt = in_array('read_at', $columns, true);

if (!$hasIsRead && !$hasReadAt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Aucune colonne de lecture trouvée']);
    exit;
}

if ($hasIsRead) {
    $sql = 'UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid';
} else {
    $sql = 'UPDATE notifications SET read_at = NOW() WHERE id = :id AND user_id = :uid';
}

$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $notifId, 'uid' => $userId]);

echo json_encode(['ok' => true]);
