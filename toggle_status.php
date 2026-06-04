<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=UTF-8');

$config = require __DIR__ . '/config/config.php';
require __DIR__ . '/config/database.php';

function respond_json(bool $ok, string $message, array $extra = []): void
{
    echo json_encode(array_merge([
        'ok' => $ok,
        'message' => $message,
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function has_users_column_local(PDO $pdo, string $columnName): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*)
                           FROM information_schema.COLUMNS
                           WHERE TABLE_SCHEMA = DATABASE()
                             AND TABLE_NAME = :table_name
                             AND COLUMN_NAME = :column_name');
    $stmt->execute([
        'table_name' => 'users',
        'column_name' => $columnName,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_json(false, 'Methode non autorisee.');
}

$csrf = (string) ($_POST['csrf'] ?? '');
if ($csrf === '' || !isset($_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], $csrf)) {
    respond_json(false, 'Jeton CSRF invalide.');
}

$authUserId = (int) ($_SESSION['auth_user_id'] ?? 0);
if ($authUserId <= 0) {
    respond_json(false, 'Session invalide.');
}

$targetUserId = (int) ($_POST['user_id'] ?? 0);
if ($targetUserId <= 0) {
    respond_json(false, 'Utilisateur invalide.');
}

if ($authUserId === $targetUserId) {
    respond_json(false, 'Vous ne pouvez pas vous bloquer vous-meme.');
}

$pdo = db($config);
$hasRoleColumn = has_users_column_local($pdo, 'role');
$hasRoleIdColumn = has_users_column_local($pdo, 'role_id');

if ($hasRoleColumn) {
    $authStmt = $pdo->prepare('SELECT id, role FROM users WHERE id = :id LIMIT 1');
} elseif ($hasRoleIdColumn) {
    $authStmt = $pdo->prepare('SELECT u.id, COALESCE(r.code, "REPORTER") AS role
                               FROM users u
                               LEFT JOIN roles r ON r.id = u.role_id
                               WHERE u.id = :id
                               LIMIT 1');
} else {
    $authStmt = $pdo->prepare('SELECT id, "REPORTER" AS role FROM users WHERE id = :id LIMIT 1');
}
$authStmt->execute(['id' => $authUserId]);
$authUser = $authStmt->fetch(PDO::FETCH_ASSOC);

if (!is_array($authUser)) {
    respond_json(false, 'Utilisateur authentifie introuvable.');
}

$authRole = strtoupper((string) ($authUser['role'] ?? 'REPORTER'));
$canManage = in_array($authRole, ['ADMIN', 'CLUSTER_LEADER', 'LEAD_GTMP', 'GTMP_LEAD'], true);
if (!$canManage) {
    respond_json(false, 'Acces interdit.');
}

$targetStmt = $pdo->prepare('SELECT id, statut FROM users WHERE id = :id LIMIT 1');
$targetStmt->execute(['id' => $targetUserId]);
$target = $targetStmt->fetch(PDO::FETCH_ASSOC);
if (!is_array($target)) {
    respond_json(false, 'Utilisateur introuvable.');
}

$currentStatus = strtolower((string) ($target['statut'] ?? 'Actif'));
$nextStatus = $currentStatus === 'bloque' ? 'Actif' : 'Bloque';
$isActive = $nextStatus === 'Actif' ? 1 : 0;

$update = $pdo->prepare('UPDATE users SET statut = :statut, is_active = :is_active WHERE id = :id');
$update->execute([
    'statut' => $nextStatus,
    'is_active' => $isActive,
    'id' => $targetUserId,
]);

respond_json(true, $nextStatus === 'Actif' ? 'Utilisateur debloque avec succes.' : 'Utilisateur rendu inactif avec succes.', [
    'new_status' => $nextStatus,
]);
