<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=UTF-8');

$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

if (!isset($_SESSION['auth_user_id']) || (int) $_SESSION['auth_user_id'] <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Session expirée.']);
    exit;
}

$csrf = (string) ($_POST['csrf'] ?? '');
if ($csrf === '' || !isset($_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], $csrf)) {
    echo json_encode(['ok' => false, 'message' => 'Jeton CSRF invalide.']);
    exit;
}

$reportId = (int)($_POST['id'] ?? 0);
if ($reportId <= 0) {
    echo json_encode(['ok' => false, 'message' => 'ID invalide.']);
    exit;
}

$userId = (int) $_SESSION['auth_user_id'];

try {
    $pdo = db($config);
    
    $stmtCol = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "reports" AND COLUMN_NAME = "reporter_user_id"');
    $stmtCol->execute();
    $userCol = ((int)$stmtCol->fetchColumn() > 0) ? 'reporter_user_id' : 'user_id';

    // Verify ownership and status
    $checkStmt = $pdo->prepare("SELECT id FROM reports WHERE id = :id AND $userCol = :user_id");
    $checkStmt->execute(['id' => $reportId, 'user_id' => $userId]);
    if (!$checkStmt->fetch()) {
        echo json_encode(['ok' => false, 'message' => 'Non autorisé ou introuvable.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE reports SET workflow_status = 'Brouillon', status_id = 1 WHERE id = :id AND $userCol = :user_id");
    $stmt->execute(['id' => $reportId, 'user_id' => $userId]);

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'message' => 'Erreur serveur.']);
}
