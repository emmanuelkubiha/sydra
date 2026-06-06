<?php

declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$userId = (int) ($_SESSION['auth_user_id'] ?? $_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Session expirée']);
    exit;
}

try {
    $config = require __DIR__ . '/../config/config.php';
    require __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/advanced_stats_service.php';

    $pdo = db($config);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $context = advanced_stats_context($pdo);
    $filters = advanced_stats_parse_filters();
    $roleCode = advanced_stats_resolve_role($pdo, $userId);
    $orgId = advanced_stats_resolve_org_id($pdo, $userId);

    $payload = advanced_stats_fetch_payload($pdo, $context, $filters, $roleCode, $userId, $orgId);

    echo json_encode([
        'ok' => true,
        'data' => $payload,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Impossible de charger les statistiques avancées.',
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
