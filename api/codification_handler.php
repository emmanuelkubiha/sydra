<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=UTF-8');

$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';

// ── Authentification ──────────────────────────────────────────────────────────
if (!isset($_SESSION['auth_user_id']) || (int) ($_SESSION['auth_user_id'] ?? 0) <= 0) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Session expirée.']);
    exit;
}

if ((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

$raw     = file_get_contents('php://input');
$payload = json_decode((string) $raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Payload JSON invalide.']);
    exit;
}

// ── CSRF ──────────────────────────────────────────────────────────────────────
$csrf = (string) ($payload['csrf'] ?? '');
$action = strtolower(trim((string) ($payload['action'] ?? '')));

// action=list ne requiert pas de CSRF (lecture seule)
if ($action !== 'list') {
    if ($csrf === '' || !isset($_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], $csrf)) {
        http_response_code(419);
        echo json_encode(['ok' => false, 'message' => 'Jeton CSRF invalide.']);
        exit;
    }
}

// ── Contrôle d'accès RBAC ─────────────────────────────────────────────────────
$pdo    = db($config);
$userId = (int) $_SESSION['auth_user_id'];

function get_user_role_codif(PDO $pdo, int $userId): string
{
    // Détection adaptative : role_id (join roles) ou colonne role directe
    $cols = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t');
    $cols->execute(['t' => 'users']);
    $columns = array_map('strtolower', $cols->fetchAll(PDO::FETCH_COLUMN) ?: []);

    if (in_array('role_id', $columns, true)) {
        $stmt = $pdo->prepare('SELECT COALESCE(r.code,"") FROM users u LEFT JOIN roles r ON r.id=u.role_id WHERE u.id=:id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        return strtoupper(trim((string) ($stmt->fetchColumn() ?: '')));
    }
    if (in_array('role', $columns, true)) {
        $stmt = $pdo->prepare('SELECT COALESCE(role,"") FROM users WHERE id=:id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        return strtoupper(trim((string) ($stmt->fetchColumn() ?: '')));
    }
    return '';
}

$role = get_user_role_codif($pdo, $userId);

// Seuls ADMIN et GTMP_LEAD peuvent écrire
$canRead  = in_array($role, ['ADMIN', 'GTMP_LEAD', 'LEAD_GTMP', 'CLUSTER_LEADER', 'GTMP_COLEAD'], true);
$canWrite = in_array($role, ['ADMIN', 'GTMP_LEAD', 'LEAD_GTMP'], true);

if (!$canRead) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Accès refusé.']);
    exit;
}

if (in_array($action, ['add', 'update', 'delete'], true) && !$canWrite) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Droits insuffisants pour modifier la codification.']);
    exit;
}

// ── Détection adaptative des colonnes de codification_rules ───────────────────
function detectCodifColumns(PDO $pdo): array
{
    $stmt = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t');
    $stmt->execute(['t' => 'codification_rules']);
    $raw = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    if ($raw === []) { return []; }

    $map = [];
    foreach ($raw as $col) { $map[strtolower((string) $col)] = (string) $col; }

    $pick = static function (array $candidates) use ($map): ?string {
        foreach ($candidates as $c) {
            if (isset($map[strtolower($c)])) { return $map[strtolower($c)]; }
        }
        return null;
    };

    return [
        'id'   => $pick(['id']),
        'term' => $pick(['sensitive_term', 'source_term', 'term', 'mot_sensible', 'keyword']),
        'code' => $pick(['replacement_code', 'replacement_term', 'code', 'code_remplacement', 'value']),
    ];
}

$cols = detectCodifColumns($pdo);
if ($cols === [] || $cols['term'] === null || $cols['code'] === null) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Table codification_rules introuvable ou mal configurée.']);
    exit;
}

$termCol = $cols['term'];
$codeCol = $cols['code'];
$idCol   = $cols['id'] ?? 'id';

// ─────────────────────────────────────────────────────────────────────────────
// ACTIONS
// ─────────────────────────────────────────────────────────────────────────────

// ── LIST ──────────────────────────────────────────────────────────────────────
if ($action === 'list') {
    $stmt = $pdo->query(
        'SELECT ' . $idCol . ' AS id,
                ' . $termCol . ' AS sensitive_term,
                ' . $codeCol . ' AS replacement_code
         FROM codification_rules
         WHERE ' . $termCol . ' IS NOT NULL
           AND ' . $codeCol . ' IS NOT NULL
         ORDER BY ' . $termCol . ' ASC'
    );
    $rules = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    echo json_encode(['ok' => true, 'rules' => $rules], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── ADD ───────────────────────────────────────────────────────────────────────
if ($action === 'add') {
    $term = trim((string) ($payload['term'] ?? ''));
    $code = trim((string) ($payload['code'] ?? ''));

    if ($term === '' || $code === '') {
        echo json_encode(['ok' => false, 'message' => 'Le terme et le code sont obligatoires.']);
        exit;
    }
    if (mb_strlen($term) > 255 || mb_strlen($code) > 100) {
        echo json_encode(['ok' => false, 'message' => 'Valeur trop longue (max terme=255, code=100).']);
        exit;
    }

    // Vérifier doublon
    $check = $pdo->prepare('SELECT COUNT(*) FROM codification_rules WHERE LOWER(' . $termCol . ') = LOWER(:term)');
    $check->execute(['term' => $term]);
    if ((int) $check->fetchColumn() > 0) {
        echo json_encode(['ok' => false, 'message' => 'Ce terme sensible existe déjà.']);
        exit;
    }

    $insert = $pdo->prepare('INSERT INTO codification_rules (' . $termCol . ', ' . $codeCol . ') VALUES (:term, :code)');
    $insert->execute(['term' => $term, 'code' => $code]);
    $newId = (int) $pdo->lastInsertId();

    echo json_encode(['ok' => true, 'id' => $newId, 'message' => 'Règle ajoutée.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── UPDATE ────────────────────────────────────────────────────────────────────
if ($action === 'update') {
    $id   = (int) ($payload['id'] ?? 0);
    $term = trim((string) ($payload['term'] ?? ''));
    $code = trim((string) ($payload['code'] ?? ''));

    if ($id <= 0 || $term === '' || $code === '') {
        echo json_encode(['ok' => false, 'message' => 'Données invalides (id, terme et code requis).']);
        exit;
    }
    if (mb_strlen($term) > 255 || mb_strlen($code) > 100) {
        echo json_encode(['ok' => false, 'message' => 'Valeur trop longue.']);
        exit;
    }

    // Vérifier que la règle existe
    $check = $pdo->prepare('SELECT COUNT(*) FROM codification_rules WHERE ' . $idCol . ' = :id');
    $check->execute(['id' => $id]);
    if ((int) $check->fetchColumn() === 0) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Règle introuvable.']);
        exit;
    }

    $update = $pdo->prepare(
        'UPDATE codification_rules SET ' . $termCol . ' = :term, ' . $codeCol . ' = :code WHERE ' . $idCol . ' = :id'
    );
    $update->execute(['term' => $term, 'code' => $code, 'id' => $id]);

    echo json_encode(['ok' => true, 'message' => 'Règle modifiée.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── DELETE ────────────────────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = (int) ($payload['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['ok' => false, 'message' => 'Identifiant invalide.']);
        exit;
    }

    $check = $pdo->prepare('SELECT COUNT(*) FROM codification_rules WHERE ' . $idCol . ' = :id');
    $check->execute(['id' => $id]);
    if ((int) $check->fetchColumn() === 0) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Règle introuvable.']);
        exit;
    }

    $delete = $pdo->prepare('DELETE FROM codification_rules WHERE ' . $idCol . ' = :id');
    $delete->execute(['id' => $id]);

    echo json_encode(['ok' => true, 'message' => 'Règle supprimée.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Action inconnue ───────────────────────────────────────────────────────────
http_response_code(400);
echo json_encode(['ok' => false, 'message' => 'Action inconnue : ' . $action]);
