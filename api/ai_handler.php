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
    // =================================================================
    // MISSION 3 : Fournisseurs IA pris en charge + codes d'erreur typés
    // =================================================================
    $endpoint = '';
    $model    = '';

    if ($provider === 'openai') {
        $endpoint = 'https://api.openai.com/v1/chat/completions';
        $model    = 'gpt-4o';
    } elseif ($provider === 'groq') {
        // Groq utilise l'API compatible OpenAI sur son propre endpoint.
        $endpoint = 'https://api.groq.com/openai/v1/chat/completions';
        $model    = 'llama-3.1-8b-instant'; // Modèle mis à jour — llama3-8b-8192 décommissionné
    } else {
        return [
            'ok'         => false,
            'error_code' => 'unknown_provider',
            'message'    => 'Fournisseur IA non supporté : ' . $provider,
        ];
    }

    $body = [
        'model'       => $model,
        'messages'    => $messages,
        'temperature' => 0.3,
    ];

    $jsonBody = json_encode($body, JSON_UNESCAPED_UNICODE);
    if (!is_string($jsonBody)) {
        return [
            'ok'         => false,
            'error_code' => 'serialization_error',
            'message'    => 'Impossible de sérialiser la requête IA.',
        ];
    }

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS     => $jsonBody,
        CURLOPT_TIMEOUT        => 45,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $responseBody = curl_exec($ch);
    $curlError    = curl_error($ch);
    $httpCode     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Erreur réseau cURL (pas de réponse HTTP)
    if (!is_string($responseBody) || $curlError !== '') {
        return [
            'ok'         => false,
            'error_code' => 'network_error',
            'message'    => 'Erreur réseau : ' . ($curlError ?: 'Réponse vide du fournisseur IA.'),
        ];
    }

    $decoded = json_decode($responseBody, true);
    if (!is_array($decoded)) {
        return [
            'ok'         => false,
            'error_code' => 'invalid_json',
            'message'    => 'Réponse JSON invalide du fournisseur IA.',
        ];
    }

    // --- Mapping des codes d'erreur HTTP => codes typiques ---
    if ($httpCode < 200 || $httpCode >= 300) {
        $apiError = trim((string) ($decoded['error']['message'] ?? ''));

        if ($httpCode === 429) {
            $errorCode = 'rate_limit';
            $msg = $apiError ?: 'Limite de requêtes IA atteinte. Veuillez patienter quelques secondes.';
        } elseif (in_array($httpCode, [401, 403], true)) {
            $errorCode = 'auth_error';
            $msg = $apiError ?: 'Clé API invalide ou non autorisée (HTTP ' . $httpCode . ').';
        } elseif (in_array($httpCode, [500, 502, 503, 504], true)) {
            $errorCode = 'server_error';
            $msg = $apiError ?: 'Erreur serveur du fournisseur IA (HTTP ' . $httpCode . '). Veuillez réessayer.';
        } else {
            $errorCode = 'provider_error';
            $msg = $apiError ?: 'Erreur inattendue du fournisseur IA (HTTP ' . $httpCode . ').';
        }

        return [
            'ok'         => false,
            'error_code' => $errorCode,
            'http_code'  => $httpCode,
            'message'    => $msg,
        ];
    }

    $content = trim((string) ($decoded['choices'][0]['message']['content'] ?? ''));
    if ($content === '') {
        return [
            'ok'         => false,
            'error_code' => 'empty_response',
            'message'    => 'Le fournisseur IA n\'a retourné aucun contenu.',
        ];
    }

    return [
        'ok'       => true,
        'provider' => $provider,
        'model'    => $model,
        'content'  => $content,
        'raw'      => $decoded,
    ];
}

$userId = (int) $_SESSION['auth_user_id'];
$role = get_user_role($pdo, $userId);
$userOrgId = 0;

// ════════════════════════════════════════════════════════════════════════════
// MISSION 1 : Récupération de l'organisation de l'utilisateur connecté
// ════════════════════════════════════════════════════════════════════════════
try {
    $orgStmt = $pdo->prepare('SELECT COALESCE(organization_id, 0) FROM users WHERE id = :id LIMIT 1');
    $orgStmt->execute(['id' => $userId]);
    $userOrgId = (int) $orgStmt->fetchColumn();
} catch (Throwable $e) {
    $userOrgId = 0;
}

// ════════════════════════════════════════════════════════════════════════════
// MISSION 1 : Contacts d'urgence (GTMP_LEAD + GTMP_COLEAD)
// ════════════════════════════════════════════════════════════════════════════
$leadContact = 'Non disponible';
$coleadContact = 'Non disponible';
try {
    $contactStmt = $pdo->query(
        'SELECT u.full_name, u.email, UPPER(COALESCE(r.code, "")) AS role_code
         FROM users u
         LEFT JOIN roles r ON r.id = u.role_id
         WHERE UPPER(COALESCE(r.code, "")) IN ("GTMP_LEAD", "LEAD_GTMP", "GTMP_COLEAD")
         AND u.statut = "Actif"
         ORDER BY r.code
         LIMIT 10'
    );
    $contacts = $contactStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($contacts as $c) {
        $name = trim((string) ($c['full_name'] ?? ''));
        $email = trim((string) ($c['email'] ?? ''));
        $rCode = strtoupper(trim((string) ($c['role_code'] ?? '')));
        $info = $name . ($email !== '' ? ' (' . $email . ')' : '');
        if (in_array($rCode, ['GTMP_LEAD', 'LEAD_GTMP'], true) && $leadContact === 'Non disponible') {
            $leadContact = $info;
        }
        if ($rCode === 'GTMP_COLEAD' && $coleadContact === 'Non disponible') {
            $coleadContact = $info;
        }
    }
} catch (Throwable $e) {
    // Fallback silencieux
}

// ════════════════════════════════════════════════════════════════════════════
// MISSION 1 : Statistiques dynamiques selon le rôle
// ════════════════════════════════════════════════════════════════════════════
$statsContext = '';
$isDecisionRole = in_array($role, ['ADMIN', 'CLUSTER_LEADER', 'LEAD_GTMP', 'GTMP_LEAD', 'GTMP_COLEAD'], true);

try {
    if ($isDecisionRole) {
        // Admin/Lead : nombre global d'alertes en attente de validation
        $statsStmt = $pdo->query(
            "SELECT COUNT(*) FROM reports
             WHERE LOWER(REPLACE(REPLACE(COALESCE(workflow_status, ''), 'é', 'e'), 'è', 'e'))
                   IN ('soumis', 'submitted', 'en revue', 'under_review')"
        );
        $pendingCount = (int) $statsStmt->fetchColumn();
        $statsContext = "Il y a actuellement " . $pendingCount . " alerte(s) globale(s) en attente de validation par le Cluster.";
    } else {
        // Rapporteur : ses propres alertes par statut
        $statsStmt = $pdo->prepare(
            "SELECT
                SUM(CASE WHEN LOWER(COALESCE(workflow_status, '')) IN ('brouillon', 'draft') THEN 1 ELSE 0 END) AS brouillons,
                SUM(CASE WHEN LOWER(COALESCE(workflow_status, '')) IN ('soumis', 'submitted') THEN 1 ELSE 0 END) AS soumises
             FROM reports
             WHERE user_id = :uid OR (organization_id = :oid AND :oid > 0)"
        );
        $statsStmt->execute(['uid' => $userId, 'oid' => $userOrgId]);
        $row = $statsStmt->fetch(PDO::FETCH_ASSOC);
        $brouillons = (int) ($row['brouillons'] ?? 0);
        $soumises = (int) ($row['soumises'] ?? 0);
        $statsContext = "Le rapporteur a actuellement " . $brouillons . " alerte(s) en brouillon et " . $soumises . " alerte(s) soumise(s) en attente de validation.";
    }
} catch (Throwable $e) {
    $statsContext = 'Statistiques indisponibles.';
}

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

if ($mode === 'ANALYSIS' && !$isDecisionRole) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Acces reserve aux roles de decision.']);
    exit;
}

$settings = get_system_settings($pdo);
$provider = strtolower(trim((string) ($settings['active_ai_provider'] ?? env_trim(['ACTIVE_AI_PROVIDER', 'AI_PROVIDER']))));
// Groq est le fournisseur par défaut (gratuit, rapide, fiable).
if (!in_array($provider, ['openai', 'groq'], true)) {
    $provider = 'groq';
}

$apiKeyByProvider = [
    'openai' => trim((string) ($settings['openai_api_key'] ?? env_trim(['OPENAI_API_KEY', 'AI_OPENAI_API_KEY']))),
    'groq'   => trim((string) ($settings['groq_api_key']   ?? env_trim(['GROQ_API_KEY',   'AI_GROQ_API_KEY']))),
];
$apiKey = $apiKeyByProvider[$provider] ?? '';
if ($apiKey === '') {
    http_response_code(503);
    echo json_encode([
        'ok'         => false,
        'success'    => false,
        'error_code' => 'missing_api_key',
        'message'    => 'Clé API manquante pour le fournisseur actif (' . $provider . ').',
    ]);
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

// ════════════════════════════════════════════════════════════════════════════
// MISSION 2 : Le System Prompt Ultime (Strict & Formaté)
// ════════════════════════════════════════════════════════════════════════════
$systemPrompt = '';
if ($mode === 'GENERIC_HELP') {
    $systemPrompt = "Tu es l'Assistant IA de SyDRA. RÈGLES STRICTES DE FORMATAGE ET DE COMPORTEMENT : \n"
        . "1. UTILISE TOUJOURS des balises HTML <p> pour séparer tes paragraphes et <br> pour les retours à la ligne. Tes textes doivent être aérés et faciles à lire.\n"
        . "2. Ne crée jamais de gros boutons. Utilise exclusivement ces classes Bootstrap pour tes boutons d'action : <a href='...' class='btn btn-sm btn-outline-primary rounded-pill d-inline-block m-1 px-3 py-1' style='font-size: 0.85rem;'>Texte</a>.\n"
        . "3. Contacts d'urgence : Ne donne les contacts du Lead (" . $leadContact . ") et Co-Lead (" . $coleadContact . ") QUE SI l'utilisateur te demande explicitement 'qui contacter' ou 'j'ai un problème'. NE LES AJOUTE JAMAIS à la fin de tes autres réponses.\n"
        . "4. Voici l'état actuel des données de cet utilisateur : " . $statsContext . " Tu es autorisé à donner ces chiffres exacts à l'utilisateur s'il te pose des questions sur ses alertes ou les alertes en attente.\n"
        . "5. Reste concis, direct et professionnel.\n"
        . "6. RÈGLES DE FORMATAGE DE TEXTE : INTERDICTION FORMELLE d'utiliser le formatage Markdown. Ne génère JAMAIS d'astérisques (** ou *) ni de tirets (-) pour tes listes. Si tu dois faire une liste, utilise EXCLUSIVEMENT les balises HTML <ul> et <li>. Si tu dois mettre un mot en valeur, utilise la balise HTML <strong class='text-primary'>mot</strong>.";
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
        . 'Reste factuel et professionnel.'
        . "\n\nStatistiques utilisateur : " . $statsContext;
} elseif ($mode === 'ANALYSIS') {
    $systemPrompt = "Tu es un conseiller IA pour un Lead GTMP. \n"
        . "Tu dois analyser uniquement le contexte codifié de l'alerte courante. \n"
        . "N'utilise aucune connaissance externe non nécessaire. \n"
        . "Fournis des réponses opérationnelles, concises et actionnables. \n"
        . "RÈGLES DE FORMATAGE : UTILISE TOUJOURS des balises HTML <p> et <br>. Utilise les classes Bootstrap pour les boutons (<a href='...' class='btn btn-sm btn-outline-primary rounded-pill d-inline-block m-1 px-3 py-1' style='font-size: 0.85rem;'>Texte</a>).\n"
        . "RÈGLES DE FORMATAGE DE TEXTE : INTERDICTION FORMELLE d'utiliser le formatage Markdown. Ne génère JAMAIS d'astérisques (** ou *) ni de tirets (-) pour tes listes. Si tu dois faire une liste, utilise EXCLUSIVEMENT les balises HTML <ul> et <li>. Si tu dois mettre un mot en valeur, utilise la balise HTML <strong class='text-primary'>mot</strong>.\n"
        . "\nStatistiques globales : " . $statsContext
        . "\nContacts : Lead GTMP = " . $leadContact . " | Co-Lead = " . $coleadContact;
} else {
    $systemPrompt = 'Tu es un assistant IA SyDRA utile, factuel et concis. '
        . "UTILISE TOUJOURS des balises HTML <p> et <br> pour aérer tes textes. "
        . "INTERDICTION FORMELLE d'utiliser le formatage Markdown (** ou -). Utilise <ul>, <li> et <strong class='text-primary'>.\n"
        . "\n" . $statsContext;
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
    // Mapping error_code => code HTTP pour les erreurs typées
    $errorCode = (string) ($result['error_code'] ?? 'provider_error');
    $httpStatus = match ($errorCode) {
        'rate_limit'   => 429,
        'auth_error'   => 502,
        'server_error' => 503,
        default        => 502,
    };
    http_response_code($httpStatus);
    echo json_encode([
        'ok'         => false,
        'success'    => false,
        'error_code' => $errorCode,
        'message'    => (string) ($result['message'] ?? 'Erreur IA.'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok'       => true,
    'success'  => true,
    'mode'     => $mode,
    'provider' => (string) ($result['provider'] ?? $provider),
    'model'    => (string) ($result['model'] ?? ''),
    'message'  => (string) ($result['content'] ?? ''),
], JSON_UNESCAPED_UNICODE);
