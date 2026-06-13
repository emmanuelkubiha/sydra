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

try {
    $pdo = db($config);
    $userId = (int) $_SESSION['auth_user_id'];
    $draftId = (int) ($_POST['draft_id'] ?? 0);

    if ($draftId <= 0) {
        echo json_encode(['ok' => false, 'message' => 'ID de brouillon invalide.']);
        exit;
    }

    // Vérifier si la table a user_id ou reporter_user_id
    $stmtCol = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "reports" AND COLUMN_NAME = "reporter_user_id"');
    $stmtCol->execute();
    $hasReporterCol = (int) $stmtCol->fetchColumn() > 0;
    
    $userCol = $hasReporterCol ? 'reporter_user_id' : 'user_id';
    $statusExpr = 'LOWER(REPLACE(REPLACE(REPLACE(COALESCE(NULLIF(TRIM(workflow_status), ""), "brouillon"), "é", "e"), "è", "e"), "ê", "e"))';

    // Vérifier que le brouillon appartient à l'utilisateur et qu'il est bien en statut "brouillon"
    $checkStmt = $pdo->prepare('SELECT id FROM reports WHERE id = :id AND ' . $userCol . ' = :user_id AND ' . $statusExpr . ' = "brouillon" LIMIT 1');
    $checkStmt->execute(['id' => $draftId, 'user_id' => $userId]);
    
    if (!$checkStmt->fetch()) {
        echo json_encode(['ok' => false, 'message' => 'Brouillon introuvable ou non autorisé.']);
        exit;
    }

    // Supprimer
    $delStmt = $pdo->prepare('DELETE FROM reports WHERE id = :id');
    $delStmt->execute(['id' => $draftId]);

    echo json_encode(['ok' => true, 'message' => 'Brouillon supprimé.']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Erreur serveur.']);
}
