<?php

declare(strict_types=1);

session_start();

$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/mail.php';
$appUrl = rtrim((string) ($config['app_url'] ?? ''), '/');

if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
}

// Verification simple CSRF alignee sur le routeur principal.
function csrf_ok(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals((string) $_SESSION['csrf_token'], $token);
}

function flash(string $type, string $message): void
{
    $_SESSION['flashes'][] = ['type' => $type, 'message' => $message];
}

function role_storage_mode_local(array $config): string
{
    $pdo = db($config);
    $roleCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch();
    if (is_array($roleCol)) {
        return 'role_column';
    }

    $roleIdCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'role_id'")->fetch();
    return is_array($roleIdCol) ? 'role_fk' : 'role_column';
}

function ensure_user_security_columns_local(array $config): void
{
    $pdo = db($config);
    $columns = [
        'statut' => "ALTER TABLE users ADD COLUMN statut ENUM('Actif','Bloque') NOT NULL DEFAULT 'Actif'",
        'must_change_password' => 'ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 1',
        'last_login_at' => 'ALTER TABLE users ADD COLUMN last_login_at DATETIME NULL',
    ];

    foreach ($columns as $columnName => $ddl) {
        $check = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS
                                WHERE TABLE_SCHEMA = DATABASE()
                                  AND TABLE_NAME = :table_name
                                  AND COLUMN_NAME = :column_name');
        $check->execute([
            'table_name' => 'users',
            'column_name' => $columnName,
        ]);
        if ((int) $check->fetchColumn() === 0) {
            $pdo->exec($ddl);
        }
    }
}

function resolve_role_id_local(array $config, string $roleCode): ?int
{
    $pdo = db($config);
    $stmt = $pdo->prepare('SELECT id FROM roles WHERE code = :code LIMIT 1');
    $stmt->execute(['code' => $roleCode]);
    $id = $stmt->fetchColumn();
    return $id !== false ? (int) $id : null;
}

// Generation d'un mot de passe aleatoire securise sur 8 caracteres.
function generate_password_8(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789@#%';
    $maxIndex = strlen($alphabet) - 1;
    $out = '';
    for ($i = 0; $i < 8; $i++) {
        $out .= $alphabet[random_int(0, $maxIndex)];
    }
    return $out;
}

$authUserId = (int) ($_SESSION['auth_user_id'] ?? 0);
if ($authUserId <= 0) {
    header('Location: ' . $appUrl . '/?page=connexion');
    exit;
}

$pdo = db($config);
$modeForAuth = role_storage_mode_local($config);
ensure_user_security_columns_local($config);

if ($modeForAuth === 'role_fk') {
    $authStmt = $pdo->prepare('SELECT u.id, u.full_name, u.email, COALESCE(r.code, "REPORTER") AS role
                               FROM users u
                               LEFT JOIN roles r ON r.id = u.role_id
                               WHERE u.id = :id
                               LIMIT 1');
} else {
    $authStmt = $pdo->prepare('SELECT id, full_name, email, role FROM users WHERE id = :id LIMIT 1');
}
$authStmt->execute(['id' => $authUserId]);
$auth = $authStmt->fetch();

$role = strtoupper((string) ($auth['role'] ?? ''));
$canCreate = in_array($role, ['ADMIN', 'CLUSTER_LEADER', 'LEAD_GTMP', 'GTMP_LEAD'], true);
if (!is_array($auth) || !$canCreate) {
    flash('error', 'Acces interdit a la creation de compte.');
    header('Location: ' . $appUrl . '/?page=tableau_de_bord');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $appUrl . '/?page=utilisateurs');
    exit;
}

if (!csrf_ok($_POST['csrf'] ?? null)) {
    flash('error', 'Session expiree. Reessayez.');
    header('Location: ' . $appUrl . '/?page=utilisateurs');
    exit;
}

$fullName = trim((string) ($_POST['full_name'] ?? ''));
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$organizationName = trim((string) ($_POST['organization_name'] ?? ''));
$targetRole = strtoupper(trim((string) ($_POST['role'] ?? 'REPORTER')));

if ($fullName === '' || $email === '' || $organizationName === '') {
    flash('error', 'Nom, email et organisation sont obligatoires.');
    header('Location: ' . $appUrl . '/?page=utilisateurs');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('error', 'Email invalide.');
    header('Location: ' . $appUrl . '/?page=utilisateurs');
    exit;
}

$allowedRoles = ['REPORTER', 'CLUSTER_LEADER', 'CLUSTER_CO_LEAD'];
if (!in_array($targetRole, $allowedRoles, true)) {
    $targetRole = 'REPORTER';
}

$existsStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$existsStmt->execute(['email' => $email]);
if (is_array($existsStmt->fetch())) {
    flash('error', 'Un utilisateur existe deja avec cet email.');
    header('Location: ' . $appUrl . '/?page=utilisateurs');
    exit;
}

$generatedPassword = generate_password_8();
$passwordHash = password_hash($generatedPassword, PASSWORD_BCRYPT);

$mode = role_storage_mode_local($config);
if ($mode === 'role_fk') {
    $roleId = resolve_role_id_local($config, $targetRole);
    if ($roleId === null) {
        flash('error', 'Role introuvable dans la table roles.');
        header('Location: ' . $appUrl . '/?page=utilisateurs');
        exit;
    }

    $insert = $pdo->prepare('INSERT INTO users (
            full_name, email, password_hash, role_id, organization_name,
            is_active, statut, must_change_password
        ) VALUES (
            :full_name, :email, :password_hash, :role_id, :organization_name,
            1, "Actif", 1
        )');
    $insert->execute([
        'full_name' => $fullName,
        'email' => $email,
        'password_hash' => $passwordHash,
        'role_id' => $roleId,
        'organization_name' => $organizationName,
    ]);
} else {
    $insert = $pdo->prepare('INSERT INTO users (
            full_name, email, password_hash, role, organization_name,
            is_active, statut, must_change_password
        ) VALUES (
            :full_name, :email, :password_hash, :role, :organization_name,
            1, "Actif", 1
        )');
    $insert->execute([
        'full_name' => $fullName,
        'email' => $email,
        'password_hash' => $passwordHash,
        'role' => $targetRole,
        'organization_name' => $organizationName,
    ]);
}

$loginUrl = $appUrl . '/?page=connexion';

// Email HTML professionnel avec appel a l'action.
$html = '<!doctype html><html><body style="margin:0;background:#eef2f7;font-family:Arial,sans-serif;">'
    . '<div style="max-width:740px;margin:22px auto;background:#fff;border:1px solid #d8e1ee;border-radius:12px;overflow:hidden;">'
    . '<div style="background:#0b4f8a;color:#fff;padding:16px 20px;">'
    . '<h2 style="margin:0;font-size:20px;">Bienvenue sur SyDRA</h2>'
    . '</div>'
    . '<div style="padding:20px;color:#1f2937;">'
    . '<p>Bonjour ' . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . ',</p>'
    . '<p>Votre compte SyDRA a ete cree avec succes.</p>'
    . '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;margin:14px 0;">'
    . '<p style="margin:0 0 6px;"><strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</p>'
    . '<p style="margin:0;"><strong>Mot de passe temporaire:</strong> ' . htmlspecialchars($generatedPassword, ENT_QUOTES, 'UTF-8') . '</p>'
    . '</div>'
    . '<p>Vous devrez obligatoirement changer ce mot de passe lors de votre premiere connexion.</p>'
    . '<p style="margin-top:20px;">'
    . '<a href="' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;background:#0b4f8a;color:#fff;text-decoration:none;padding:12px 18px;border-radius:8px;font-weight:700;">Se connecter</a>'
    . '</p>'
    . '<p style="font-size:12px;color:#64748b;">Equipe SyDRA</p>'
    . '</div></div></body></html>';

$mailResult = sendAppMailDetailed($config, $email, 'Votre compte SyDRA a ete cree', $html, true);
if ((bool) ($mailResult['success'] ?? false)) {
    flash('success', 'Compte cree et email envoye a ' . $email . '.');
} else {
    flash('error', 'Compte cree mais email non envoye: ' . (string) ($mailResult['error'] ?? 'Erreur inconnue.'));
}

header('Location: ' . $appUrl . '/?page=utilisateurs');
exit;
