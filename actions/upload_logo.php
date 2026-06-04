<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=UTF-8');

$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';

if (!isset($_SESSION['auth_user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session invalide.']);
    exit;
}

$csrf = (string) ($_POST['csrf'] ?? '');
if ($csrf === '' || !isset($_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], $csrf)) {
    http_response_code(419);
    echo json_encode(['success' => false, 'message' => 'Jeton CSRF invalide.']);
    exit;
}

if (!isset($_FILES['logo']) || !is_array($_FILES['logo'])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Fichier logo manquant.']);
    exit;
}

$file = $_FILES['logo'];
$error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
if ($error !== UPLOAD_ERR_OK) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Echec upload logo.']);
    exit;
}

if ((int) ($file['size'] ?? 0) > (6 * 1024 * 1024)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Logo trop lourd (max 6 Mo).']);
    exit;
}

$tmp = (string) ($file['tmp_name'] ?? '');
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = (string) $finfo->file($tmp);

$allowed = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

if (!isset($allowed[$mime])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Format non supporte (jpg/png/webp).']);
    exit;
}

$uploadDir = __DIR__ . '/../uploads/organizations/logos';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Impossible de preparer le dossier upload.']);
    exit;
}

$ext = $allowed[$mime];
$fileName = 'org_logo_' . (int) $_SESSION['auth_user_id'] . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$target = $uploadDir . '/' . $fileName;

if (!move_uploaded_file($tmp, $target)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Impossible de sauvegarder le logo.']);
    exit;
}

$relativePath = 'uploads/organizations/logos/' . $fileName;

$pdo = db($config);
$hasLogoPath = false;
$check = $pdo->prepare('SELECT COUNT(*)
                        FROM information_schema.COLUMNS
                        WHERE TABLE_SCHEMA = DATABASE()
                          AND TABLE_NAME = :table_name
                          AND COLUMN_NAME = :column_name');
$check->execute(['table_name' => 'users', 'column_name' => 'logo_path']);
$hasLogoPath = (int) $check->fetchColumn() > 0;

if ($hasLogoPath) {
    $stmt = $pdo->prepare('UPDATE users SET avatar_path = :path, logo_path = :path WHERE id = :id');
} else {
    $stmt = $pdo->prepare('UPDATE users SET avatar_path = :path WHERE id = :id');
}

$stmt->execute([
    'path' => $relativePath,
    'id' => (int) $_SESSION['auth_user_id'],
]);

echo json_encode([
    'success' => true,
    'message' => 'Logo mis a jour.',
    'logo_path' => $relativePath,
]);
