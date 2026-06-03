<?php

declare(strict_types=1);

session_start();

$config = require __DIR__ . '/config/config.php';
require __DIR__ . '/config/database.php';
require __DIR__ . '/config/mail.php';

if (is_file(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
}

if (!function_exists('current_lang')) {
    function current_lang(): string
    {
        $lang = strtolower((string) ($_SESSION['lang'] ?? 'fr'));
        return in_array($lang, ['fr', 'en'], true) ? $lang : 'fr';
    }
}

if (!function_exists('t')) {
    function t(string $key): string
    {
        $dict = [
            'fr' => [
                'nav.dashboard' => 'Tableau de bord',
                'nav.create_report' => 'Créer rapport',
                'nav.reports' => 'Liste rapports',
                'nav.profile' => 'Profil',
                'nav.users' => 'Utilisateurs',
                'nav.logout' => 'Déconnexion',
                'nav.hello' => 'Bonjour',
                'login.title' => 'Connexion',
                'login.subtitle' => 'Accès sécurisé à votre espace.',
                'login.secure_title' => 'Connexion sécurisée',
                'login.email' => 'Email',
                'login.password' => 'Mot de passe',
                'login.submit' => 'Se connecter',
                'login.forgot' => 'Mot de passe oublié ?',
                'forgot.title' => 'Mot de passe oublié',
                'forgot.subtitle' => 'Saisissez votre email pour recevoir un lien de réinitialisation.',
                'forgot.submit' => 'Envoyer le lien de réinitialisation',
                'forgot.back' => 'Retour à la connexion',
                'forgot.help' => 'Besoin d\'aide',
                'forgot.write_us' => 'Nous écrire',
                'loader.title' => 'Chargement en cours...',
                'loader.default' => 'Vérification de la session et préparation de l\'espace.',
                'intro.line1' => 'Système de documentation, de rapportage et d\'alerte.',
                'intro.line2' => 'Coordonnez vos alertes plus rapidement.',
                'intro.line3' => 'Suivez, partagez et agissez plus vite.',
                'dashboard.title' => 'Tableau de bord simple',
                'dashboard.body' => 'Utilisez les liens du menu pour gérer les rapports et votre profil.',
            ],
            'en' => [
                'nav.dashboard' => 'Dashboard',
                'nav.create_report' => 'Create report',
                'nav.reports' => 'Reports list',
                'nav.profile' => 'Profile',
                'nav.users' => 'Users',
                'nav.logout' => 'Logout',
                'nav.hello' => 'Hello',
                'login.title' => 'Sign in',
                'login.subtitle' => 'Secure access to your workspace.',
                'login.secure_title' => 'Secure sign in',
                'login.email' => 'Email',
                'login.password' => 'Password',
                'login.submit' => 'Sign in',
                'login.forgot' => 'Forgot password?',
                'forgot.title' => 'Forgot password',
                'forgot.subtitle' => 'Enter your email to receive a reset link.',
                'forgot.submit' => 'Send reset link',
                'forgot.back' => 'Back to sign in',
                'forgot.help' => 'Need help',
                'forgot.write_us' => 'Write to us',
                'loader.title' => 'Loading...',
                'loader.default' => 'Checking your session and preparing your workspace.',
                'intro.line1' => 'Documentation, reporting and alert system.',
                'intro.line2' => 'Coordinate your alerts faster.',
                'intro.line3' => 'Track, share and act faster.',
                'dashboard.title' => 'Simple dashboard',
                'dashboard.body' => 'Use the menu links to manage reports and your profile.',
            ],
        ];

        $lang = current_lang();
        if (isset($dict[$lang][$key])) {
            return $dict[$lang][$key];
        }
        return $dict['fr'][$key] ?? $key;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify(?string $token): bool
    {
        return is_string($token)
            && isset($_SESSION['csrf_token'])
            && hash_equals((string) $_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('set_flash')) {
    function set_flash(string $type, string $message): void
    {
        $_SESSION['flashes'][] = ['type' => $type, 'message' => $message];
    }
}

if (!function_exists('get_flashes')) {
    function get_flashes(): array
    {
        $flashes = $_SESSION['flashes'] ?? [];
        unset($_SESSION['flashes']);
        return is_array($flashes) ? $flashes : [];
    }
}

if (!function_exists('auth_user')) {
    function auth_user(array $config): ?array
    {
        if (!isset($_SESSION['auth_user_id'])) {
            return null;
        }

        $pdo = db($config);
        $mode = role_storage_mode($config);

        if ($mode === 'role_fk') {
            $stmt = $pdo->prepare('SELECT u.id, u.full_name, u.email,
                                          COALESCE(r.code, "REPORTER") AS role,
                                          u.avatar_path, u.phone, u.job_title, u.organization_name, u.bio, u.is_active
                                   FROM users u
                                   LEFT JOIN roles r ON r.id = u.role_id
                                   WHERE u.id = :id
                                   LIMIT 1');
        } else {
            $stmt = $pdo->prepare('SELECT id, full_name, email, role, avatar_path, phone, job_title, organization_name, bio, is_active
                                   FROM users
                                   WHERE id = :id
                                   LIMIT 1');
        }
        $stmt->execute(['id' => (int) $_SESSION['auth_user_id']]);
        $user = $stmt->fetch();

        if (!is_array($user) || (int) ($user['is_active'] ?? 0) !== 1) {
            unset($_SESSION['auth_user_id']);
            return null;
        }

        return $user;
    }
}

if (!function_exists('has_users_column')) {
    function has_users_column(array $config, string $columnName): bool
    {
        $pdo = db($config);
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
}

if (!function_exists('role_storage_mode')) {
    function role_storage_mode(array $config): string
    {
        if (has_users_column($config, 'role')) {
            return 'role_column';
        }

        if (has_users_column($config, 'role_id')) {
            return 'role_fk';
        }

        return 'role_column';
    }
}

if (!function_exists('require_auth')) {
    function require_auth(?array $user): void
    {
        if (!is_array($user)) {
            header('Location: ?page=connexion');
            exit;
        }
    }
}

if (!function_exists('is_admin')) {
    function is_admin(?array $user): bool
    {
        return is_array($user) && (($user['role'] ?? '') === 'ADMIN');
    }
}

if (!function_exists('validate_password_policy')) {
    function validate_password_policy(string $password): ?string
    {
        if (strlen($password) < 10) {
            return 'Le mot de passe doit contenir au moins 10 caractères.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return 'Le mot de passe doit contenir au moins une majuscule.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            return 'Le mot de passe doit contenir au moins une minuscule.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            return 'Le mot de passe doit contenir au moins un chiffre.';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return 'Le mot de passe doit contenir au moins un caractère spécial.';
        }

        return null;
    }
}

if (!function_exists('ensure_user_profile_columns')) {
    function ensure_user_profile_columns(array $config): void
    {
        $pdo = db($config);
        $columns = [
            'phone' => 'ALTER TABLE users ADD COLUMN phone VARCHAR(60) DEFAULT NULL',
            'job_title' => 'ALTER TABLE users ADD COLUMN job_title VARCHAR(120) DEFAULT NULL',
            'organization_name' => 'ALTER TABLE users ADD COLUMN organization_name VARCHAR(180) DEFAULT NULL',
            'bio' => 'ALTER TABLE users ADD COLUMN bio TEXT DEFAULT NULL',
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
}

if (!function_exists('ensure_upload_directories')) {
    function ensure_upload_directories(): void
    {
        $dirs = [
            __DIR__ . '/uploads/avatars',
            __DIR__ . '/uploads/organizations/logos',
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
        }
    }
}

if (!function_exists('app_pages')) {
    function app_pages(): array
    {
        return [
            'connexion',
            'mot_de_passe_oublie',
            'activation_compte',
            'reinitialiser_mot_de_passe',
            'tableau_de_bord',
            'rapport_creer',
            'rapports_liste',
            'profil',
            'utilisateurs',
        ];
    }
}

if (!function_exists('build_uploaded_filename')) {
    function build_uploaded_filename(string $prefix, string $extension): string
    {
        $safePrefix = preg_replace('/[^a-z0-9_\-]/i', '_', strtolower($prefix)) ?: 'file';
        return $safePrefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    }
}

if (!function_exists('ensure_password_reset_table')) {
    function ensure_password_reset_table(array $config): void
    {
        $pdo = db($config);
        $pdo->exec('CREATE TABLE IF NOT EXISTS password_reset_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            email VARCHAR(190) NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_reset_token_hash (token_hash),
            INDEX idx_reset_user_id (user_id),
            CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }
}

if (!function_exists('detect_user_roles')) {
    function detect_user_roles(array $config): array
    {
        $pdo = db($config);
        $mode = role_storage_mode($config);

        if ($mode === 'role_fk') {
            $rows = $pdo->query('SELECT code FROM roles ORDER BY id ASC')->fetchAll();
            $codes = [];
            foreach ($rows as $row) {
                $code = trim((string) ($row['code'] ?? ''));
                if ($code !== '') {
                    $codes[] = $code;
                }
            }
            return $codes !== [] ? $codes : ['ADMIN', 'CLUSTER_LEADER', 'CLUSTER_CO_LEAD', 'REPORTER'];
        }

        $row = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch();
        if (!is_array($row) || !isset($row['Type'])) {
            return ['ADMIN', 'CLUSTER_LEADER', 'CLUSTER_CO_LEAD', 'REPORTER'];
        }

        $type = (string) $row['Type'];
        if (!preg_match('/^enum\((.+)\)$/i', $type, $matches)) {
            return ['ADMIN', 'CLUSTER_LEADER', 'CLUSTER_CO_LEAD', 'REPORTER'];
        }

        $roles = str_getcsv($matches[1], ',', "'");
        $roles = array_values(array_filter(array_map('trim', $roles), static fn ($v) => $v !== ''));

        return $roles !== [] ? $roles : ['ADMIN', 'CLUSTER_LEADER', 'CLUSTER_CO_LEAD', 'REPORTER'];
    }
}

if (!function_exists('resolve_role_id')) {
    function resolve_role_id(array $config, string $roleCode): ?int
    {
        $pdo = db($config);
        $stmt = $pdo->prepare('SELECT id FROM roles WHERE code = :code LIMIT 1');
        $stmt->execute(['code' => $roleCode]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }
}

if (!function_exists('smtp_recommendations')) {
    function smtp_recommendations(array $config, string $errorMessage): array
    {
        $tips = [];
        $host = trim((string) ($config['mail']['smtp_host'] ?? ''));
        $port = (int) ($config['mail']['smtp_port'] ?? 0);
        $user = trim((string) ($config['mail']['smtp_user'] ?? ''));
        $secure = strtolower((string) ($config['mail']['smtp_secure'] ?? 'tls'));

        if ($host === '') {
            $tips[] = 'SMTP_HOST est vide dans .env. Renseignez le serveur SMTP.';
        }
        if ($port <= 0) {
            $tips[] = 'SMTP_PORT invalide. Utilisez 587 (TLS) ou 465 (SSL).';
        }
        if ($user === '' && (bool) ($config['mail']['smtp_auth'] ?? true)) {
            $tips[] = 'SMTP_USER est vide alors que SMTP_AUTH=true.';
        }
        if (!in_array($secure, ['tls', 'ssl', 'none'], true)) {
            $tips[] = 'SMTP_SECURE doit etre tls, ssl ou none.';
        }

        $lower = strtolower($errorMessage);
        if (str_contains($lower, 'could not connect') || str_contains($lower, 'connection refused') || str_contains($lower, 'timed out')) {
            $tips[] = 'Le serveur SMTP est inaccessible: verifier host, port, firewall et DNS.';
        }
        if (str_contains($lower, 'authentication') || str_contains($lower, 'username') || str_contains($lower, 'password')) {
            $tips[] = 'Echec d authentification: verifier SMTP_USER/SMTP_PASS et autorisations du compte.';
        }
        if (str_contains($lower, 'tls') || str_contains($lower, 'ssl') || str_contains($lower, 'certificate')) {
            $tips[] = 'Probleme de chiffrement: tester SMTP_SECURE=tls avec port 587 ou ssl avec port 465.';
        }

        if ($tips === []) {
            $tips[] = 'Verifier SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_SECURE et MAIL_FROM dans .env.';
        }

        return $tips;
    }
}

if (!function_exists('ensure_demo_users')) {
    function ensure_demo_users(array $config): void
    {
        $pdo = db($config);
        $roles = detect_user_roles($config);
        $mode = role_storage_mode($config);
        $passwordHash = password_hash('password', PASSWORD_BCRYPT);

        $demoUsers = [
            ['full_name' => 'Admin SyDRA', 'email' => 'it@fosip-drc.org', 'role_candidates' => ['ADMIN']],
            ['full_name' => 'Lead Cluster', 'email' => 'lead.cluster@sydra.local', 'role_candidates' => ['CLUSTER_LEADER', 'GTMP_LEAD']],
            ['full_name' => 'Co-Lead Cluster', 'email' => 'colead.cluster@sydra.local', 'role_candidates' => ['CLUSTER_CO_LEAD', 'GTMP_COLEAD']],
            ['full_name' => 'Reporteur Terrain', 'email' => 'reporter@sydra.local', 'role_candidates' => ['REPORTER', 'ORG_REPORTER']],
        ];

        foreach ($demoUsers as $demo) {
            $targetRole = null;
            foreach ($demo['role_candidates'] as $candidate) {
                if (in_array($candidate, $roles, true)) {
                    $targetRole = $candidate;
                    break;
                }
            }

            if ($targetRole === null) {
                continue;
            }

            $check = $pdo->prepare('SELECT id, password_hash, is_active FROM users WHERE email = :email LIMIT 1');
            $check->execute(['email' => $demo['email']]);
            $existing = $check->fetch();

            if ($mode === 'role_fk') {
                $roleId = resolve_role_id($config, $targetRole);
                if ($roleId === null) {
                    continue;
                }

                if (is_array($existing)) {
                    $mustResetPassword = !password_verify('password', (string) ($existing['password_hash'] ?? ''));
                    if ($mustResetPassword || (int) ($existing['is_active'] ?? 0) !== 1) {
                        $update = $pdo->prepare('UPDATE users
                                                 SET full_name = :full_name,
                                                     password_hash = :password_hash,
                                                     role_id = :role_id,
                                                     is_active = 1
                                                 WHERE id = :id');
                        $update->execute([
                            'full_name' => $demo['full_name'],
                            'password_hash' => $passwordHash,
                            'role_id' => $roleId,
                            'id' => (int) $existing['id'],
                        ]);
                    }
                } else {
                    $insert = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role_id, is_active)
                                             VALUES (:full_name, :email, :password_hash, :role_id, 1)');
                    $insert->execute([
                        'full_name' => $demo['full_name'],
                        'email' => $demo['email'],
                        'password_hash' => $passwordHash,
                        'role_id' => $roleId,
                    ]);
                }
            } else {
                if (is_array($existing)) {
                    $mustResetPassword = !password_verify('password', (string) ($existing['password_hash'] ?? ''));
                    if ($mustResetPassword || (int) ($existing['is_active'] ?? 0) !== 1) {
                        $update = $pdo->prepare('UPDATE users
                                                 SET full_name = :full_name,
                                                     password_hash = :password_hash,
                                                     role = :role,
                                                     is_active = 1
                                                 WHERE id = :id');
                        $update->execute([
                            'full_name' => $demo['full_name'],
                            'password_hash' => $passwordHash,
                            'role' => $targetRole,
                            'id' => (int) $existing['id'],
                        ]);
                    }
                } else {
                    $insert = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role, is_active)
                                             VALUES (:full_name, :email, :password_hash, :role, 1)');
                    $insert->execute([
                        'full_name' => $demo['full_name'],
                        'email' => $demo['email'],
                        'password_hash' => $passwordHash,
                        'role' => $targetRole,
                    ]);
                }
            }
        }
    }
}

$page = (string) ($_GET['page'] ?? '');
$method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');

if (isset($_GET['lang'])) {
    $requestedLang = strtolower(trim((string) $_GET['lang']));
    if (in_array($requestedLang, ['fr', 'en'], true)) {
        $_SESSION['lang'] = $requestedLang;
    }
}

ensure_user_profile_columns($config);
ensure_password_reset_table($config);
ensure_upload_directories();
ensure_demo_users($config);

$authUser = auth_user($config);

$pageAliases = [
    'login' => 'connexion',
    'forgot_password' => 'mot_de_passe_oublie',
    'dashboard' => 'tableau_de_bord',
    'reports_create' => 'rapport_creer',
    'reports_list' => 'rapports_liste',
    'profile' => 'profil',
    'users' => 'utilisateurs',
    'activate' => 'activation_compte',
    'logout' => 'deconnexion',
];
$page = $pageAliases[$page] ?? $page;
if ($page === '') {
    $page = is_array($authUser) ? 'tableau_de_bord' : 'connexion';
}

if ($method === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if (!csrf_verify($_POST['csrf'] ?? null)) {
        set_flash('error', 'Session expirée. Réessayez.');
        header('Location: ?page=' . urlencode($page));
        exit;
    }

    $pdo = db($config);

    if ($action === 'login') {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        $stmt = $pdo->prepare('SELECT id, password_hash, is_active FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        if (!is_array($row) || (int) ($row['is_active'] ?? 0) !== 1 || !password_verify($password, (string) $row['password_hash'])) {
            set_flash('error', 'Identifiants invalides.');
            header('Location: ?page=connexion');
            exit;
        }

        $_SESSION['auth_user_id'] = (int) $row['id'];
        set_flash('success', 'Connexion réussie.');
        header('Location: ?page=tableau_de_bord');
        exit;
    }

    if ($action === 'request_password_reset') {
        $email = strtolower(trim((string) ($_POST['reset_email'] ?? $_POST['email'] ?? '')));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Veuillez saisir un email valide.');
            header('Location: ?page=mot_de_passe_oublie');
            exit;
        }

        $stmt = $pdo->prepare('SELECT id, full_name, email, is_active FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!is_array($user) || (int) ($user['is_active'] ?? 0) !== 1) {
            set_flash('error', 'Adresse email introuvable ou compte inactif.');
            header('Location: ?page=mot_de_passe_oublie');
            exit;
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);

        $cleanup = $pdo->prepare('DELETE FROM password_reset_requests WHERE user_id = :user_id AND used_at IS NULL');
        $cleanup->execute(['user_id' => (int) $user['id']]);

        $insertReset = $pdo->prepare('INSERT INTO password_reset_requests (user_id, email, token_hash, expires_at)
                                      VALUES (:user_id, :email, :token_hash, :expires_at)');
        $insertReset->execute([
            'user_id' => (int) $user['id'],
            'email' => (string) $user['email'],
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);

        $appUrl = rtrim((string) $config['app_url'], '/');
        $resetLink = $appUrl . '/?page=reinitialiser_mot_de_passe&token=' . urlencode($token);
        $supportEmail = (string) ($config['support_email'] ?? $config['mail']['from'] ?? 'it@fosip-drc.org');

        $subject = 'Reinitialisation de votre mot de passe SyDRA';
        $body = "Bonjour " . (string) ($user['full_name'] ?? 'utilisateur') . ",\n\n"
            . "Nous avons recu une demande de reinitialisation de mot de passe.\n"
            . "Cliquez sur ce lien pour definir un nouveau mot de passe:\n"
            . $resetLink . "\n\n"
            . "Ce lien expire dans 1 heure.\n"
            . "Si vous n'etes pas a l'origine de cette demande, ignorez ce message.\n"
            . "Si le probleme persiste, contactez l'admin: " . $supportEmail . "\n\n"
            . "Equipe SyDRA";

        $mailResult = sendAppMailDetailed($config, (string) $user['email'], $subject, $body);
        if ((bool) ($mailResult['success'] ?? false)) {
            $_SESSION['password_reset_recent'] = $email;
            set_flash('success', 'Un email de réinitialisation a été envoyé à ' . (string) $user['email'] . '.');
        } else {
            $detail = trim((string) ($mailResult['error'] ?? 'Echec inconnu.'));
            set_flash('error', 'Echec d\'envoi de l\'email de reinitialisation: ' . $detail);
        }

        header('Location: ?page=mot_de_passe_oublie');
        exit;
    }

    if ($action === 'reset_password') {
        $token = trim((string) ($_POST['token'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

        if ($token === '') {
            set_flash('error', 'Lien de reinitialisation invalide.');
            header('Location: ?page=connexion');
            exit;
        }

        if ($password !== $passwordConfirmation) {
            set_flash('error', 'La confirmation du mot de passe ne correspond pas.');
            header('Location: ?page=reinitialiser_mot_de_passe&token=' . urlencode($token));
            exit;
        }

        $policyError = validate_password_policy($password);
        if ($policyError !== null) {
            set_flash('error', $policyError);
            header('Location: ?page=reinitialiser_mot_de_passe&token=' . urlencode($token));
            exit;
        }

        $resetStmt = $pdo->prepare('SELECT id, user_id, expires_at, used_at
                                    FROM password_reset_requests
                                    WHERE token_hash = :token_hash
                                    LIMIT 1');
        $resetStmt->execute(['token_hash' => hash('sha256', $token)]);
        $resetRow = $resetStmt->fetch();

        if (!is_array($resetRow) || !empty($resetRow['used_at']) || strtotime((string) $resetRow['expires_at']) <= time()) {
            set_flash('error', 'Ce lien est invalide, expire ou deja utilise.');
            header('Location: ?page=connexion');
            exit;
        }

        $updateUser = $pdo->prepare('UPDATE users SET password_hash = :password_hash, is_active = 1 WHERE id = :id');
        $updateUser->execute([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'id' => (int) $resetRow['user_id'],
        ]);

        $markUsed = $pdo->prepare('UPDATE password_reset_requests SET used_at = NOW() WHERE id = :id');
        $markUsed->execute(['id' => (int) $resetRow['id']]);

        set_flash('success', 'Mot de passe reinitialise avec succes. Connectez-vous maintenant.');
        header('Location: ?page=connexion');
        exit;
    }

    if ($action === 'create_report') {
        require_auth($authUser);

        $title = trim((string) ($_POST['title'] ?? ''));
        $reportType = strtoupper(trim((string) ($_POST['report_type'] ?? 'FLASH')));
        $location = trim((string) ($_POST['location_text'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));

        if ($title === '' || $content === '') {
            set_flash('error', 'Titre et contenu sont obligatoires.');
            header('Location: ?page=rapport_creer');
            exit;
        }

        if (!in_array($reportType, ['FLASH', 'NOTE'], true)) {
            $reportType = 'FLASH';
        }

        $stmt = $pdo->prepare('INSERT INTO reports (user_id, title, report_type, content, location_text)
                               VALUES (:user_id, :title, :report_type, :content, :location_text)');
        $stmt->execute([
            'user_id' => (int) $authUser['id'],
            'title' => $title,
            'report_type' => $reportType,
            'content' => $content,
            'location_text' => $location !== '' ? $location : null,
        ]);

        set_flash('success', 'Rapport enregistré.');
        header('Location: ?page=rapports_liste');
        exit;
    }

    if ($action === 'update_profile') {
        require_auth($authUser);

        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $jobTitle = trim((string) ($_POST['job_title'] ?? ''));
        $organizationName = trim((string) ($_POST['organization_name'] ?? ''));
        $bio = trim((string) ($_POST['bio'] ?? ''));
        if ($fullName === '') {
            set_flash('error', 'Le nom complet est obligatoire.');
            header('Location: ?page=profil');
            exit;
        }

        $avatarPath = (string) ($authUser['avatar_path'] ?? '');
        if (isset($_FILES['avatar_file']) && is_array($_FILES['avatar_file'])) {
            $file = $_FILES['avatar_file'];
            $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($errorCode !== UPLOAD_ERR_NO_FILE) {
                if ($errorCode !== UPLOAD_ERR_OK) {
                    set_flash('error', 'Échec de l\'upload de la photo.');
                    header('Location: ?page=profil');
                    exit;
                }

                if ((int) ($file['size'] ?? 0) > (5 * 1024 * 1024)) {
                    set_flash('error', 'Photo trop lourde (max 5 Mo).');
                    header('Location: ?page=profil');
                    exit;
                }

                $tmp = (string) ($file['tmp_name'] ?? '');
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = (string) $finfo->file($tmp);
                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    'image/gif' => 'gif',
                ];

                if (!isset($allowed[$mime])) {
                    set_flash('error', 'Format image non supporté.');
                    header('Location: ?page=profil');
                    exit;
                }

                $targetDir = __DIR__ . '/uploads/avatars';
                $fileName = build_uploaded_filename('avatar_' . (string) $authUser['id'], $allowed[$mime]);
                $targetPath = $targetDir . '/' . $fileName;
                if (!move_uploaded_file($tmp, $targetPath)) {
                    set_flash('error', 'Impossible d\'enregistrer la photo.');
                    header('Location: ?page=profil');
                    exit;
                }

                $avatarPath = 'uploads/avatars/' . $fileName;
            }
        }

        $stmt = $pdo->prepare('UPDATE users
                               SET full_name = :full_name,
                                   avatar_path = :avatar_path,
                                   phone = :phone,
                                   job_title = :job_title,
                                   organization_name = :organization_name,
                                   bio = :bio
                               WHERE id = :id');
        $stmt->execute([
            'full_name' => $fullName,
            'avatar_path' => $avatarPath !== '' ? $avatarPath : null,
            'phone' => $phone !== '' ? $phone : null,
            'job_title' => $jobTitle !== '' ? $jobTitle : null,
            'organization_name' => $organizationName !== '' ? $organizationName : null,
            'bio' => $bio !== '' ? $bio : null,
            'id' => (int) $authUser['id'],
        ]);

        set_flash('success', 'Profil mis à jour.');
        header('Location: ?page=profil');
        exit;
    }

    if ($action === 'invite_user') {
        require_auth($authUser);
        if (!is_admin($authUser)) {
            set_flash('error', 'Action réservée aux administrateurs.');
            header('Location: ?page=tableau_de_bord');
            exit;
        }

        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $role = strtoupper(trim((string) ($_POST['role'] ?? 'REPORTER')));

        if ($fullName === '' || $email === '') {
            set_flash('error', 'Nom complet et email sont obligatoires.');
            header('Location: ?page=utilisateurs');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Email invalide.');
            header('Location: ?page=utilisateurs');
            exit;
        }

        $allowedRoles = array_values(array_filter(detect_user_roles($config), static fn ($r) => $r !== 'ADMIN'));
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'REPORTER';
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        if (is_array($stmt->fetch())) {
            set_flash('error', 'Un compte existe déjà avec cet email.');
            header('Location: ?page=utilisateurs');
            exit;
        }

        $placeholderHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
        if (role_storage_mode($config) === 'role_fk') {
            $roleId = resolve_role_id($config, $role);
            if ($roleId === null) {
                set_flash('error', 'Role introuvable en base.');
                header('Location: ?page=utilisateurs');
                exit;
            }

            $insertUser = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role_id, is_active)
                                         VALUES (:full_name, :email, :password_hash, :role_id, 0)');
            $insertUser->execute([
                'full_name' => $fullName,
                'email' => $email,
                'password_hash' => $placeholderHash,
                'role_id' => $roleId,
            ]);
        } else {
            $insertUser = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role, is_active)
                                         VALUES (:full_name, :email, :password_hash, :role, 0)');
            $insertUser->execute([
                'full_name' => $fullName,
                'email' => $email,
                'password_hash' => $placeholderHash,
                'role' => $role,
            ]);
        }
        $userId = (int) $pdo->lastInsertId();

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + (48 * 3600));

        $insertInvitation = $pdo->prepare('INSERT INTO account_invitations (user_id, email, token_hash, expires_at)
                                           VALUES (:user_id, :email, :token_hash, :expires_at)');
        $insertInvitation->execute([
            'user_id' => $userId,
            'email' => $email,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);

        $appUrl = rtrim((string) $config['app_url'], '/');
        $activationLink = $appUrl . '/?page=activation_compte&token=' . urlencode($token);

        $subject = 'Félicitations, votre compte SyDRA a été créé avec succès';
        $body = "Bonjour {$fullName},\n\n"
            . "Félicitations, votre compte SyDRA a été créé avec succès.\n"
            . "SyDRA est une plateforme de documentation, rapportage et alerte pour la coordination humanitaire.\n\n"
            . "Cliquez sur ce lien pour valider votre compte et définir votre mot de passe:\n"
            . $activationLink . "\n\n"
            . "Ce lien expire automatiquement après 48 heures.\n"
            . "Si ce message est une erreur, ignorez-le simplement.\n\n"
            . "Équipe SyDRA";

        $sent = sendAppMail($config, $email, $subject, $body);
        if ($sent) {
            set_flash('success', 'Invitation envoyée à ' . $email . '.');
        } else {
            set_flash('error', 'Utilisateur créé, mais échec d\'envoi email. Vérifie SMTP.');
        }

        header('Location: ?page=utilisateurs');
        exit;
    }

    if ($action === 'test_smtp') {
        require_auth($authUser);
        if (!is_admin($authUser)) {
            set_flash('error', 'Action reservee aux administrateurs.');
            header('Location: ?page=tableau_de_bord');
            exit;
        }

        $recipient = strtolower(trim((string) ($_POST['test_email'] ?? '')));
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Email de test invalide.');
            header('Location: ?page=utilisateurs');
            exit;
        }

        $subject = 'Test SMTP SyDRA';
        $body = "Ceci est un email de test SMTP envoye par SyDRA le " . date('Y-m-d H:i:s');
        $result = sendAppMailDetailed($config, $recipient, $subject, $body);

        if ((bool) ($result['success'] ?? false)) {
            set_flash('success', 'SMTP OK: email de test envoye a ' . $recipient . '.');
        } else {
            $errorMessage = trim((string) ($result['error'] ?? 'Erreur SMTP inconnue.'));
            $tips = smtp_recommendations($config, $errorMessage);
            set_flash('error', 'SMTP KO: ' . $errorMessage);
            set_flash('error', 'Recommandations: ' . implode(' | ', $tips));
        }

        header('Location: ?page=utilisateurs');
        exit;
    }

    if ($action === 'activate_account') {
        $token = trim((string) ($_POST['token'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

        if ($token === '') {
            set_flash('error', 'Lien d\'activation invalide.');
            header('Location: ?page=connexion');
            exit;
        }

        if ($password !== $passwordConfirmation) {
            set_flash('error', 'La confirmation du mot de passe ne correspond pas.');
            header('Location: ?page=activation_compte&token=' . urlencode($token));
            exit;
        }

        $policyError = validate_password_policy($password);
        if ($policyError !== null) {
            set_flash('error', $policyError);
            header('Location: ?page=activation_compte&token=' . urlencode($token));
            exit;
        }

        $invitationStmt = $pdo->prepare('SELECT id, user_id, expires_at, used_at
                                         FROM account_invitations
                                         WHERE token_hash = :token_hash
                                         LIMIT 1');
        $invitationStmt->execute(['token_hash' => hash('sha256', $token)]);
        $invitation = $invitationStmt->fetch();

        if (!is_array($invitation) || !empty($invitation['used_at']) || strtotime((string) $invitation['expires_at']) <= time()) {
            set_flash('error', 'Ce lien est invalide, expiré ou déjà utilisé.');
            header('Location: ?page=connexion');
            exit;
        }

        $updateUser = $pdo->prepare('UPDATE users SET password_hash = :password_hash, is_active = 1 WHERE id = :id');
        $updateUser->execute([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'id' => (int) $invitation['user_id'],
        ]);

        $markUsed = $pdo->prepare('UPDATE account_invitations SET used_at = NOW() WHERE id = :id');
        $markUsed->execute(['id' => (int) $invitation['id']]);

        set_flash('success', 'Compte activé avec succès. Vous pouvez vous connecter.');
        header('Location: ?page=connexion');
        exit;
    }
}

if ($page === 'deconnexion') {
    unset($_SESSION['auth_user_id']);
    set_flash('success', 'Déconnexion effectuée.');
    header('Location: ?page=connexion');
    exit;
}

$publicPages = ['connexion', 'mot_de_passe_oublie', 'activation_compte', 'reinitialiser_mot_de_passe'];
if (!in_array($page, $publicPages, true)) {
    require_auth($authUser);
}

$pageMap = [
    'connexion' => ['file' => __DIR__ . '/pages/login.php', 'title' => 'Connexion'],
    'mot_de_passe_oublie' => ['file' => __DIR__ . '/pages/forgot_password.php', 'title' => 'Mot de passe oublie'],
    'tableau_de_bord' => ['file' => __DIR__ . '/pages/dashboard.php', 'title' => 'Tableau de bord'],
    'rapport_creer' => ['file' => __DIR__ . '/pages/reports_create.php', 'title' => 'Nouveau rapport'],
    'rapports_liste' => ['file' => __DIR__ . '/pages/reports_list.php', 'title' => 'Rapports'],
    'profil' => ['file' => __DIR__ . '/pages/profile.php', 'title' => 'Profil'],
    'utilisateurs' => ['file' => __DIR__ . '/pages/users.php', 'title' => 'Utilisateurs'],
    'activation_compte' => ['file' => __DIR__ . '/pages/activate.php', 'title' => 'Activation de compte'],
    'reinitialiser_mot_de_passe' => ['file' => __DIR__ . '/pages/reset_password.php', 'title' => 'Reinitialiser le mot de passe'],
];

if (!isset($pageMap[$page])) {
    http_response_code(404);
    echo 'Page introuvable.';
    exit;
}

if ($page === 'utilisateurs' && !is_admin($authUser)) {
    http_response_code(403);
    echo 'Accès interdit.';
    exit;
}

$reports = [];
$users = [];
$activation = null;
$pdo = db($config);

if ($page === 'rapports_liste') {
    $stmt = $pdo->query('SELECT r.id, r.title, r.report_type, r.location_text, r.created_at, u.full_name
                         FROM reports r
                         INNER JOIN users u ON u.id = r.user_id
                         ORDER BY r.created_at DESC
                         LIMIT 300');
    $reports = $stmt->fetchAll();
}

if ($page === 'utilisateurs') {
    if (role_storage_mode($config) === 'role_fk') {
        $stmt = $pdo->query('SELECT u.id, u.full_name, u.email, COALESCE(r.code, "REPORTER") AS role, u.is_active, u.created_at
                             FROM users u
                             LEFT JOIN roles r ON r.id = u.role_id
                             ORDER BY u.id DESC
                             LIMIT 300');
    } else {
        $stmt = $pdo->query('SELECT id, full_name, email, role, is_active, created_at FROM users ORDER BY id DESC LIMIT 300');
    }
    $users = $stmt->fetchAll();
}

if ($page === 'activation_compte') {
    $token = trim((string) ($_GET['token'] ?? ''));
    if ($token !== '') {
        $stmt = $pdo->prepare('SELECT ai.id, ai.user_id, ai.expires_at, ai.used_at, u.full_name, u.email
                               FROM account_invitations ai
                               INNER JOIN users u ON u.id = ai.user_id
                               WHERE ai.token_hash = :token_hash
                               LIMIT 1');
        $stmt->execute(['token_hash' => hash('sha256', $token)]);
        $row = $stmt->fetch();

        if (is_array($row) && empty($row['used_at']) && strtotime((string) $row['expires_at']) > time()) {
            $activation = $row;
        }
    }
}

if ($page === 'reinitialiser_mot_de_passe') {
    $token = trim((string) ($_GET['token'] ?? ''));
    if ($token !== '') {
        $stmt = $pdo->prepare('SELECT id, user_id, expires_at, used_at
                               FROM password_reset_requests
                               WHERE token_hash = :token_hash
                               LIMIT 1');
        $stmt->execute(['token_hash' => hash('sha256', $token)]);
        $row = $stmt->fetch();

        if (is_array($row) && empty($row['used_at']) && strtotime((string) $row['expires_at']) > time()) {
            $activation = $row;
        }
    }
}

$pageTitle = $pageMap[$page]['title'] . ' - ' . $config['app_name'];
$flashes = get_flashes();

require __DIR__ . '/pages/en_tete.php';

foreach ($flashes as $flash) {
    $type = ((string) ($flash['type'] ?? 'success')) === 'error' ? 'error' : 'success';
    echo '<div class="flash ' . $type . '">'
        . htmlspecialchars((string) ($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8')
        . '</div>';
}

require $pageMap[$page]['file'];

require __DIR__ . '/pages/pied_de_page.php';
