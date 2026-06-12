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

$raw = file_get_contents('php://input');
$payload = [];
if (is_string($raw) && trim($raw) !== '') {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}
if ($payload === []) {
    $payload = $_POST;
}

$csrf = (string) ($payload['csrf'] ?? '');
if ($csrf === '' || !isset($_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], $csrf)) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'message' => 'Jeton CSRF invalide.']);
    exit;
}

$pdo = db($config);

function getUserRole(PDO $pdo, int $userId): string
{
    $columnsStmt = $pdo->prepare('SELECT COLUMN_NAME
                                  FROM information_schema.COLUMNS
                                  WHERE TABLE_SCHEMA = DATABASE()
                                    AND TABLE_NAME = :table_name');
    $columnsStmt->execute(['table_name' => 'users']);
    $columns = array_map('strtolower', $columnsStmt->fetchAll(PDO::FETCH_COLUMN) ?: []);

    $hasRole = in_array('role', $columns, true);
    $hasRoleId = in_array('role_id', $columns, true);

    if ($hasRoleId) {
        $stmt = $pdo->prepare('SELECT COALESCE(r.code, "") AS role_code
                               FROM users u
                               LEFT JOIN roles r ON r.id = u.role_id
                               WHERE u.id = :id
                               LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $role = (string) ($stmt->fetchColumn() ?: '');
        return strtoupper(trim($role));
    }

    if ($hasRole) {
        $stmt = $pdo->prepare('SELECT COALESCE(role, "") FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $role = (string) ($stmt->fetchColumn() ?: '');
        return strtoupper(trim($role));
    }

    return '';
}

function ensureSystemSettingsTable(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS system_settings (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(120) NOT NULL,
        setting_value TEXT NULL,
        description VARCHAR(255) DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_system_settings_key (setting_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $defaults = [
        ['groq_api_key',       '', 'Clé API Groq (llama3-8b-8192)'],
        ['openai_api_key',     '', 'Clé API OpenAI'],
        ['active_ai_provider', 'groq', 'Fournisseur IA actif (groq ou openai)'],
        ['maintenance_mode',   '0', 'Activation du mode maintenance'],
        ['review_deadline_days', '3', 'Délai de revue par défaut (jours)'],
    ];

    $upsert = $pdo->prepare('INSERT INTO system_settings (setting_key, setting_value, description)
                             VALUES (:setting_key, :setting_value, :description)
                             ON DUPLICATE KEY UPDATE
                                 description = VALUES(description)');

    foreach ($defaults as $item) {
        $upsert->execute([
            'setting_key' => $item[0],
            'setting_value' => $item[1],
            'description' => $item[2],
        ]);
    }
}

function detectCodificationColumns(PDO $pdo): ?array
{
    $stmt = $pdo->prepare('SELECT COLUMN_NAME
                           FROM information_schema.COLUMNS
                           WHERE TABLE_SCHEMA = DATABASE()
                             AND TABLE_NAME = :table_name');
    $stmt->execute(['table_name' => 'codification_rules']);
    $columnsRaw = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

    if ($columnsRaw === []) {
        return null;
    }

    $columns = [];
    foreach ($columnsRaw as $col) {
        $columns[strtolower((string) $col)] = (string) $col;
    }

    $pick = static function (array $candidates) use ($columns): ?string {
        foreach ($candidates as $candidate) {
            $key = strtolower($candidate);
            if (isset($columns[$key])) {
                return $columns[$key];
            }
        }
        return null;
    };

    return [
        'id' => $pick(['id']),
        'term' => $pick(['sensitive_term', 'source_term', 'term', 'mot_sensible', 'keyword']),
        'code' => $pick(['replacement_code', 'replacement_term', 'code', 'code_remplacement', 'value']),
        'created_at' => $pick(['created_at', 'date_creation']),
        'updated_at' => $pick(['updated_at', 'date_modification']),
    ];
}

$userId = (int) $_SESSION['auth_user_id'];
$role = getUserRole($pdo, $userId);

$allowedRoles = ['ADMIN', 'GTMP_LEAD', 'LEAD_GTMP', 'GTMP_COLEAD'];
if (!in_array($role, $allowedRoles, true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Accès interdit.']);
    exit;
}

$action = strtolower(trim((string) ($payload['action'] ?? 'load')));
$isAdmin = $role === 'ADMIN';
$isLead = in_array($role, ['GTMP_LEAD', 'LEAD_GTMP'], true);

ensureSystemSettingsTable($pdo);

if ($action === 'load') {
    $settingsStmt = $pdo->query('SELECT setting_key, setting_value FROM system_settings');
    $settingsRows = $settingsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $settings = [];
    foreach ($settingsRows as $row) {
        $key = (string) ($row['setting_key'] ?? '');
        if ($key === '') {
            continue;
        }
        $settings[$key] = (string) ($row['setting_value'] ?? '');
    }

    $rules = [];
    $columns = detectCodificationColumns($pdo);
    if ($columns !== null && $columns['term'] !== null && $columns['code'] !== null) {
        $selectParts = [
            'c.' . $columns['term'] . ' AS sensitive_term',
            'c.' . $columns['code'] . ' AS replacement_code',
        ];
        if ($columns['id'] !== null) {
            $selectParts[] = 'c.' . $columns['id'] . ' AS id';
        }

        $orderCol = $columns['updated_at'] ?? $columns['created_at'] ?? $columns['term'];
        $rulesStmt = $pdo->query('SELECT ' . implode(', ', $selectParts) . '
                                  FROM codification_rules c
                                  ORDER BY c.' . $orderCol . ' DESC
                                  LIMIT 200');
        $rules = $rulesStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    echo json_encode([
        'ok' => true,
        'settings' => [
            'maintenance_mode'    => (string) ($settings['maintenance_mode'] ?? '0'),
            'review_deadline_days' => (string) ($settings['review_deadline_days'] ?? '3'),
            'active_ai_provider'  => (string) ($settings['active_ai_provider'] ?? 'groq'),
        ],
        // Groq
        'has_groq_api_key'   => trim((string) ($settings['groq_api_key'] ?? '')) !== '',
        'groq_key_masked'    => trim((string) ($settings['groq_api_key'] ?? '')) !== '' ? '••••••••' . substr(trim((string) ($settings['groq_api_key'] ?? '')), -4) : '',
        // OpenAI
        'has_openai_api_key' => trim((string) ($settings['openai_api_key'] ?? '')) !== '',
        'openai_key_masked'  => trim((string) ($settings['openai_api_key'] ?? '')) !== '' ? '••••••••' . substr(trim((string) ($settings['openai_api_key'] ?? '')), -4) : '',
        'codification_rules' => $rules,
    ]);
    exit;
}

if ($action === 'save_settings') {
    $settings = $payload['settings'] ?? null;
    if (!is_array($settings)) {
        echo json_encode(['ok' => false, 'message' => 'Paramètres invalides.']);
        exit;
    }

    $upsert = $pdo->prepare('INSERT INTO system_settings (setting_key, setting_value, description)
                             VALUES (:setting_key, :setting_value, :description)
                             ON DUPLICATE KEY UPDATE
                                 setting_value = VALUES(setting_value),
                                 description = VALUES(description),
                                 updated_at = CURRENT_TIMESTAMP');

    foreach ($settings as $key => $value) {
        $settingKey = strtolower(trim((string) $key));
        $settingValue = trim((string) $value);

        if (!preg_match('/^[a-z0-9_]{3,64}$/', $settingKey)) {
            continue;
        }

        if (in_array($settingKey, ['groq_api_key', 'openai_api_key', 'active_ai_provider', 'maintenance_mode'], true) && !$isAdmin) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Seul un ADMIN peut modifier la configuration IA/Système.']);
            exit;
        }

        if ($settingKey === 'review_deadline_days' && !($isAdmin || $isLead)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Seul un ADMIN ou GTMP_LEAD peut modifier cette règle métier.']);
            exit;
        }

        if ($settingKey === 'maintenance_mode') {
            $settingValue = $settingValue === '1' ? '1' : '0';
        }

        if ($settingKey === 'review_deadline_days') {
            $days = (int) $settingValue;
            if ($days < 1 || $days > 30) {
                echo json_encode(['ok' => false, 'message' => 'Le délai doit être compris entre 1 et 30 jours.']);
                exit;
            }
            $settingValue = (string) $days;
        }

        if (in_array($settingKey, ['groq_api_key', 'openai_api_key'], true) && $settingValue === '') {
            // Conserver la clé existante si le champ est laissé vide.
            continue;
        }

        if ($settingKey === 'active_ai_provider' && !in_array($settingValue, ['groq', 'openai'], true)) {
            echo json_encode(['ok' => false, 'message' => 'Fournisseur IA invalide. Valeurs acceptées : groq, openai.']);
            exit;
        }

        $descriptions = [
            'groq_api_key'          => 'Clé API Groq (llama3-8b-8192)',
            'openai_api_key'        => 'Clé API OpenAI',
            'active_ai_provider'    => 'Fournisseur IA actif (groq ou openai)',
            'maintenance_mode'      => 'Activation du mode maintenance',
            'review_deadline_days'  => 'Délai de revue par défaut (jours)',
        ];

        $upsert->execute([
            'setting_key' => $settingKey,
            'setting_value' => $settingValue,
            'description' => $descriptions[$settingKey] ?? 'Paramètre système',
        ]);
    }

    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'save_codification_rules') {
    if (!($isAdmin || $isLead)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => 'Seul un ADMIN ou GTMP_LEAD peut modifier les règles de codification.']);
        exit;
    }

    $rules = $payload['rules'] ?? null;
    if (!is_array($rules) || $rules === []) {
        echo json_encode(['ok' => false, 'message' => 'Aucune règle à enregistrer.']);
        exit;
    }

    $columns = detectCodificationColumns($pdo);
    if ($columns === null || $columns['term'] === null || $columns['code'] === null) {
        echo json_encode(['ok' => false, 'message' => 'La table codification_rules est introuvable ou incomplète.']);
        exit;
    }

    $termCol = $columns['term'];
    $codeCol = $columns['code'];

    $insertSql = 'INSERT INTO codification_rules (' . $termCol . ', ' . $codeCol . ') VALUES (:term, :code)';
    $updateSql = 'UPDATE codification_rules SET ' . $codeCol . ' = :code WHERE ' . $termCol . ' = :term';

    $insertStmt = $pdo->prepare($insertSql);
    $updateStmt = $pdo->prepare($updateSql);

    foreach ($rules as $rule) {
        if (!is_array($rule)) {
            continue;
        }

        $term = trim((string) ($rule['sensitive_term'] ?? ''));
        $code = trim((string) ($rule['replacement_code'] ?? ''));

        if ($term === '' || $code === '') {
            continue;
        }

        $updateStmt->execute([
            'term' => $term,
            'code' => $code,
        ]);

        if ($updateStmt->rowCount() < 1) {
            $insertStmt->execute([
                'term' => $term,
                'code' => $code,
            ]);
        }
    }

    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false, 'message' => 'Action non reconnue.']);
