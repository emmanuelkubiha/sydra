<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=UTF-8');

$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';

if ((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Methode non autorisee.']);
    exit;
}

if (!isset($_SESSION['auth_user_id']) || (int) $_SESSION['auth_user_id'] <= 0) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Session expiree.']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode((string) $raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Payload JSON invalide.']);
    exit;
}

$csrf = (string) ($payload['csrf'] ?? '');
if ($csrf === '' || !isset($_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], $csrf)) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'message' => 'Jeton CSRF invalide.']);
    exit;
}

$pdo = db($config);

function get_user_role(PDO $pdo, int $userId): string
{
    $columnsStmt = $pdo->prepare('SELECT COLUMN_NAME
                                  FROM information_schema.COLUMNS
                                  WHERE TABLE_SCHEMA = DATABASE()
                                    AND TABLE_NAME = :table_name');
    $columnsStmt->execute(['table_name' => 'users']);
    $columns = array_map('strtolower', $columnsStmt->fetchAll(PDO::FETCH_COLUMN) ?: []);

    if (in_array('role_id', $columns, true)) {
        $stmt = $pdo->prepare('SELECT COALESCE(r.code, "") AS role_code
                               FROM users u
                               LEFT JOIN roles r ON r.id = u.role_id
                               WHERE u.id = :id
                               LIMIT 1');
        $stmt->execute(['id' => $userId]);
        return strtoupper(trim((string) ($stmt->fetchColumn() ?: '')));
    }

    if (in_array('role', $columns, true)) {
        $stmt = $pdo->prepare('SELECT COALESCE(role, "") FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        return strtoupper(trim((string) ($stmt->fetchColumn() ?: '')));
    }

    return '';
}

function get_system_settings(PDO $pdo): array
{
    $settings = [];
    try {
        $stmt = $pdo->query('SELECT setting_key, setting_value FROM system_settings');
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $key = (string) ($row['setting_key'] ?? '');
            if ($key === '') {
                continue;
            }
            $settings[$key] = (string) ($row['setting_value'] ?? '');
        }
    } catch (Throwable $e) {
        return [];
    }
    return $settings;
}

function env_trim(array $candidates): string
{
    foreach ($candidates as $name) {
        $value = trim((string) ($_ENV[$name] ?? getenv($name) ?: ''));
        if ($value !== '') {
            return $value;
        }
    }
    return '';
}

function get_codification_rules(PDO $pdo): array
{
    $columnsStmt = $pdo->prepare('SELECT COLUMN_NAME
                                  FROM information_schema.COLUMNS
                                  WHERE TABLE_SCHEMA = DATABASE()
                                    AND TABLE_NAME = :table_name');
    $columnsStmt->execute(['table_name' => 'codification_rules']);
    $rawColumns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    if ($rawColumns === []) {
        return [];
    }

    $columns = [];
    foreach ($rawColumns as $column) {
        $columns[strtolower((string) $column)] = (string) $column;
    }

    $pick = static function (array $candidates) use ($columns): ?string {
        foreach ($candidates as $candidate) {
            if (isset($columns[strtolower($candidate)])) {
                return $columns[strtolower($candidate)];
            }
        }
        return null;
    };

    $termCol = $pick(['sensitive_term', 'source_term', 'term', 'mot_sensible', 'keyword']);
    $codeCol = $pick(['replacement_code', 'replacement_term', 'code', 'code_remplacement', 'value']);

    if ($termCol === null || $codeCol === null) {
        return [];
    }

    $stmt = $pdo->query('SELECT ' . $termCol . ' AS sensitive_term, ' . $codeCol . ' AS replacement_code
                         FROM codification_rules
                         WHERE ' . $termCol . ' IS NOT NULL
                           AND ' . $codeCol . ' IS NOT NULL');

    $rules = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $term = trim((string) ($row['sensitive_term'] ?? ''));
        $code = trim((string) ($row['replacement_code'] ?? ''));
        if ($term === '' || $code === '') {
            continue;
        }
        $rules[] = ['term' => $term, 'code' => $code];
    }

    return $rules;
}

function apply_codification_text(string $text, array $rules): string
{
    $safe = $text;
    foreach ($rules as $rule) {
        $term = (string) ($rule['term'] ?? '');
        $code = (string) ($rule['code'] ?? '');
        if ($term === '' || $code === '') {
            continue;
        }
        $safe = str_ireplace($term, $code, $safe);
    }
    return $safe;
}

function apply_codification_deep($value, array $rules)
{
    if (is_string($value)) {
        return apply_codification_text($value, $rules);
    }

    if (is_array($value)) {
        $result = [];
        foreach ($value as $key => $item) {
            $result[$key] = apply_codification_deep($item, $rules);
        }
        return $result;
    }

    return $value;
}

function normalize_messages($messages): array
{
    if (!is_array($messages)) {
        return [];
    }

    $normalized = [];
    foreach ($messages as $message) {
        if (!is_array($message)) {
            continue;
        }

        $role = strtolower(trim((string) ($message['role'] ?? 'user')));
        if (!in_array($role, ['system', 'user', 'assistant'], true)) {
            $role = 'user';
        }

        $content = trim((string) ($message['content'] ?? ''));
        if ($content === '') {
            continue;
        }

        $normalized[] = [
            'role' => $role,
            'content' => $content,
        ];
    }

    return $normalized;
}

function get_table_columns(PDO $pdo, string $tableName): array
{
    $stmt = $pdo->prepare('SELECT COLUMN_NAME
                           FROM information_schema.COLUMNS
                           WHERE TABLE_SCHEMA = DATABASE()
                             AND TABLE_NAME = :table_name');
    $stmt->execute(['table_name' => $tableName]);

    $columns = [];
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) ?: [] as $col) {
        $columns[strtolower((string) $col)] = (string) $col;
    }

    return $columns;
}

function first_existing_column(array $columns, array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        $key = strtolower((string) $candidate);
        if (isset($columns[$key])) {
            return $columns[$key];
        }
    }
    return null;
}

function get_report_context(PDO $pdo, int $reportId): array
{
    if ($reportId <= 0) {
        return [];
    }

    $reportCols = get_table_columns($pdo, 'reports');
    if ($reportCols === [] || first_existing_column($reportCols, ['id']) === null) {
        return [];
    }

    $selectParts = ['r.id AS report_id'];

    $map = [
        'workflow_status' => ['workflow_status', 'status'],
        'report_type' => ['report_type', 'type'],
        'urgency_level' => ['urgency_level', 'severity_level', 'gravity_level'],
        'location_text' => ['location_text', 'location', 'province', 'territory', 'commune', 'village'],
        'incident_label' => ['incident_label', 'incident_type', 'incident_category'],
        'content' => ['content', 'description', 'incident_description'],
        'analysis_text' => ['analysis_text', 'analysis', 'analyse'],
        'additional_notes' => ['additional_notes', 'notes'],
        'victims_count' => ['victims_count', 'affected_people', 'nb_victims'],
        'displaced_households' => ['displaced_households', 'households_displaced', 'nb_displaced_households'],
        'recommendations_text' => ['recommendations_text', 'recommandations', 'recommendations'],
        'priority_needs_text' => ['priority_needs_text', 'priority_needs', 'besoins_prioritaires'],
    ];

    foreach ($map as $alias => $candidates) {
        $column = first_existing_column($reportCols, $candidates);
        if ($column !== null) {
            $selectParts[] = 'r.' . $column . ' AS ' . $alias;
        }
    }

    $joinUsers = '';
    $userCols = get_table_columns($pdo, 'users');
    $reportUserFk = first_existing_column($reportCols, ['user_id', 'author_id', 'created_by', 'reporter_id', 'reporter_user_id']);
    if ($reportUserFk !== null && $userCols !== []) {
        $joinUsers = ' LEFT JOIN users u ON u.id = r.' . $reportUserFk;
        $orgCol = first_existing_column($userCols, ['organization_name', 'organisation_name', 'full_name', 'name']);
        if ($orgCol !== null) {
            $selectParts[] = 'u.' . $orgCol . ' AS organization_name';
        }
    }

    $sql = 'SELECT ' . implode(', ', $selectParts) . '
            FROM reports r'
            . $joinUsers
            . ' WHERE r.id = :id LIMIT 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $reportId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : [];
}

function call_ai_provider(string $provider, string $apiKey, array $messages): array
{
    $endpoint = '';
    $model = '';

    if ($provider === 'openai') {
        $endpoint = 'https://api.openai.com/v1/chat/completions';
        $model = 'gpt-4o';
    } elseif ($provider === 'xai') {
        $endpoint = 'https://api.x.ai/v1/chat/completions';
        $model = 'grok-3';
    } else {
        return ['ok' => false, 'message' => 'Fournisseur IA non supporte.'];
    }

    $body = [
        'model' => $model,
        'messages' => $messages,
        'temperature' => 0.3,
    ];

    $jsonBody = json_encode($body, JSON_UNESCAPED_UNICODE);
    if (!is_string($jsonBody)) {
        return ['ok' => false, 'message' => 'Impossible de serialiser la requete IA.'];
    }

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => $jsonBody,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_CONNECTTIMEOUT => 15,
    ]);

    $responseBody = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!is_string($responseBody)) {
        return ['ok' => false, 'message' => 'Reponse vide du fournisseur IA.'];
    }

    if ($curlError !== '') {
        return ['ok' => false, 'message' => 'Erreur cURL: ' . $curlError];
    }

    $decoded = json_decode($responseBody, true);
    if (!is_array($decoded)) {
        return ['ok' => false, 'message' => 'Reponse JSON invalide du fournisseur IA.'];
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        $apiError = '';
        if (isset($decoded['error']['message'])) {
            $apiError = (string) $decoded['error']['message'];
        }
        return ['ok' => false, 'message' => $apiError !== '' ? $apiError : ('Erreur HTTP IA ' . $httpCode)];
    }

    $content = trim((string) ($decoded['choices'][0]['message']['content'] ?? ''));
    if ($content === '') {
        return ['ok' => false, 'message' => 'Le fournisseur IA n\'a retourne aucun contenu.'];
    }

    return [
        'ok' => true,
        'provider' => $provider,
        'model' => $model,
        'content' => $content,
        'raw' => $decoded,
    ];
}

$userId = (int) $_SESSION['auth_user_id'];
$role = get_user_role($pdo, $userId);

$action = strtolower(trim((string) ($payload['action'] ?? 'chat')));
$requestedMode = strtoupper(trim((string) ($payload['mode'] ?? '')));
$reportId = (int) ($payload['report_id'] ?? (($payload['report_context']['report_id'] ?? 0)));
$messagesInput = normalize_messages($payload['messages'] ?? []);

$mode = $requestedMode;
if (!in_array($mode, ['GENERIC_HELP', 'DRAFTING', 'ANALYSIS'], true)) {
    if ($action === 'analyze_report') {
        $mode = 'ANALYSIS';
    } elseif (in_array($action, ['assist_creation', 'generate_structured'], true)) {
        $mode = 'DRAFTING';
    } else {
        $mode = 'GENERIC_HELP';
    }
}

$isDecisionRole = in_array($role, ['ADMIN', 'CLUSTER_LEADER', 'LEAD_GTMP', 'GTMP_LEAD'], true);
if ($mode === 'ANALYSIS' && !$isDecisionRole) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Acces reserve aux roles de decision.']);
    exit;
}

$settings = get_system_settings($pdo);
$provider = strtolower(trim((string) ($settings['active_ai_provider'] ?? env_trim(['ACTIVE_AI_PROVIDER', 'AI_PROVIDER', 'XAI_PROVIDER']))));
if (!in_array($provider, ['openai', 'xai'], true)) {
    $provider = 'xai';
}

$apiKeyByProvider = [
    'openai' => trim((string) ($settings['openai_api_key'] ?? env_trim(['OPENAI_API_KEY', 'AI_OPENAI_API_KEY']))),
    'xai' => trim((string) ($settings['xai_api_key'] ?? env_trim(['XAI_API_KEY', 'AI_XAI_API_KEY']))),
];
$apiKey = $apiKeyByProvider[$provider] ?? '';
if ($apiKey === '') {
    http_response_code(503);
    echo json_encode(['ok' => false, 'message' => 'Cle API manquante pour le fournisseur actif.']);
    exit;
}

$rules = get_codification_rules($pdo);
$messages = apply_codification_deep($messagesInput, $rules);

$analysisContext = [];
if ($mode === 'ANALYSIS') {
    $analysisContext = get_report_context($pdo, $reportId);
    if ($analysisContext === []) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Alerte introuvable pour analyse.']);
        exit;
    }
}
$safeAnalysisContext = apply_codification_deep($analysisContext, $rules);

$systemPrompt = '';
if ($mode === 'GENERIC_HELP') {
    $systemPrompt = 'Tu es l\'assistant SyDRA. Tu n\'as accès à aucune donnée du système sur cette page. '
        . 'Ton seul rôle est d\'expliquer brièvement comment utiliser le système ou inviter l\'utilisateur à aller sur la page de création d\'alerte. '
        . 'Ne réponds à aucune question sur des incidents réels.';
} elseif ($mode === 'DRAFTING' && $action === 'generate_structured') {
    $systemPrompt = 'Tu es un assistant de structuration d\'alerte. '
        . 'Retourne uniquement un JSON valide sans markdown ni texte additionnel, selon ce schema exact: '
        . '{"incident_type":"...","urgency_level":"Faible|Moyenne|Elevee|Critique","location":"...","contexte":"...","analyse":"...","besoins_prioritaires":"...","recommandations":"...","victims_count":0,"displaced_households":0}. '
        . 'Si une valeur manque, propose une valeur raisonnable sans inventer des details sensibles.';
} elseif ($mode === 'DRAFTING') {
    $systemPrompt = 'Tu es un Expert de monitoring de protection humanitaire. '
        . 'Objectif: aider l\'agent a collecter les informations manquantes pour une alerte de protection. '
        . 'Pose des questions courtes, une a la fois, jusqu\'a obtenir: contexte, localisation, type d\'incident, victimes, menages deplaces, analyse, besoins prioritaires, recommandations. '
        . 'Quand les informations semblent suffisantes, ajoute a la fin de ta reponse le marqueur [[READY_TO_GENERATE]]. '
        . 'Reste factuel et professionnel.';
} elseif ($mode === 'ANALYSIS') {
    $systemPrompt = 'Tu es un conseiller IA pour un Lead GTMP. '
        . 'Tu dois analyser uniquement le contexte codifie de l\'alerte courante. '
        . 'N\'utilise aucune connaissance externe non necessaire. '
        . 'Fournis des reponses operationnelles, concises et actionnables.';
} else {
    $systemPrompt = 'Tu es un assistant IA SyDRA utile, factuel et concis.';
}

$preparedMessages = [['role' => 'system', 'content' => $systemPrompt]];

if ($mode === 'ANALYSIS' && $safeAnalysisContext !== []) {
    $preparedMessages[] = [
        'role' => 'system',
        'content' => 'Contexte alerte (base unique de reponse): ' . json_encode($safeAnalysisContext, JSON_UNESCAPED_UNICODE),
    ];
}

foreach ($messages as $message) {
    $preparedMessages[] = $message;
}

$result = call_ai_provider($provider, $apiKey, $preparedMessages);
if (($result['ok'] ?? false) !== true) {
    http_response_code(502);
    echo json_encode([
        'ok' => false,
        'message' => (string) ($result['message'] ?? 'Erreur IA.'),
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'mode' => $mode,
    'provider' => (string) ($result['provider'] ?? $provider),
    'model' => (string) ($result['model'] ?? ''),
    'message' => (string) ($result['content'] ?? ''),
], JSON_UNESCAPED_UNICODE);
