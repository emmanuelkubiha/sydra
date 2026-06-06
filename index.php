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
                                          u.avatar_path, u.logo_path, u.phone, u.job_title, u.organization_name, u.bio,
                                          u.telephone_organisation, u.site_web, u.bio_organisation, u.is_active
                                   FROM users u
                                   LEFT JOIN roles r ON r.id = u.role_id
                                   WHERE u.id = :id
                                   LIMIT 1');
        } else {
            $stmt = $pdo->prepare('SELECT id, full_name, email, role, avatar_path, logo_path, phone, job_title, organization_name, bio,
                                          telephone_organisation, site_web, bio_organisation, is_active
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

if (!function_exists('requires_profile_completion')) {
    function requires_profile_completion(?array $authUser): bool
    {
        if (!is_array($authUser)) {
            return false;
        }

        $role = strtoupper((string) ($authUser['role'] ?? 'REPORTER'));
        if ($role === 'ADMIN') {
            return false;
        }

        $organizationName = trim((string) ($authUser['organization_name'] ?? ''));
        $phone = trim((string) ($authUser['telephone_organisation'] ?? $authUser['phone'] ?? ''));
        $bio = trim((string) ($authUser['bio_organisation'] ?? $authUser['bio'] ?? ''));

        return $organizationName === '' || $phone === '' || $bio === '';
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

if (!function_exists('has_table_column')) {
    function has_table_column(array $config, string $tableName, string $columnName): bool
    {
        $pdo = db($config);
        $stmt = $pdo->prepare('SELECT COUNT(*)
                               FROM information_schema.COLUMNS
                               WHERE TABLE_SCHEMA = DATABASE()
                                 AND TABLE_NAME = :table_name
                                 AND COLUMN_NAME = :column_name');
        $stmt->execute([
            'table_name' => $tableName,
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

if (!function_exists('reports_user_fk_column')) {
    function reports_user_fk_column(array $config): ?string
    {
        $candidates = ['user_id', 'author_id', 'created_by', 'reporter_id', 'reporter_user_id'];
        foreach ($candidates as $columnName) {
            if (has_table_column($config, 'reports', $columnName)) {
                return $columnName;
            }
        }

        return null;
    }
}

if (!function_exists('require_auth')) {
    function require_auth(?array $user): void
    {
        if (!is_array($user)) {
            set_flash('error', 'Veuillez vous connecter pour continuer.');
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

if (!function_exists('is_lead_gtmp')) {
    function is_lead_gtmp(?array $user): bool
    {
        if (!is_array($user)) {
            return false;
        }

        $role = strtoupper((string) ($user['role'] ?? ''));
        return in_array($role, ['CLUSTER_LEADER', 'LEAD_GTMP', 'GTMP_LEAD'], true);
    }
}

if (!function_exists('can_manage_users')) {
    function can_manage_users(?array $user): bool
    {
        return is_admin($user) || is_lead_gtmp($user);
    }
}

if (!function_exists('is_report_mutable_status')) {
    function is_report_mutable_status(string $status): bool
    {
        $normalized = strtolower(trim($status));
        $normalized = str_replace(['é', 'è', 'ê'], 'e', $normalized);
        return in_array($normalized, ['brouillon', 'soumis'], true);
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
            'avatar_path' => 'ALTER TABLE users ADD COLUMN avatar_path VARCHAR(255) DEFAULT NULL',
            'logo_path' => 'ALTER TABLE users ADD COLUMN logo_path VARCHAR(255) DEFAULT NULL',
            'phone' => 'ALTER TABLE users ADD COLUMN phone VARCHAR(60) DEFAULT NULL',
            'job_title' => 'ALTER TABLE users ADD COLUMN job_title VARCHAR(120) DEFAULT NULL',
            'organization_name' => 'ALTER TABLE users ADD COLUMN organization_name VARCHAR(180) DEFAULT NULL',
            'bio' => 'ALTER TABLE users ADD COLUMN bio TEXT DEFAULT NULL',
            'telephone_organisation' => 'ALTER TABLE users ADD COLUMN telephone_organisation VARCHAR(80) DEFAULT NULL',
            'site_web' => 'ALTER TABLE users ADD COLUMN site_web VARCHAR(255) DEFAULT NULL',
            'bio_organisation' => 'ALTER TABLE users ADD COLUMN bio_organisation TEXT DEFAULT NULL',
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
            __DIR__ . '/uploads/reports/attachments',
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
            'confirmer_email',
            'aide',
            'tableau_de_bord',
            'stats',
            'rapportage',
            'rapportage-liste-user',
            'rapportage-admin-list',
            'rapportage-voir',
            'rapportage-creer-AI',
            'rapportage-creer-wizar',
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

if (!function_exists('ensure_email_change_requests_table')) {
    function ensure_email_change_requests_table(array $config): void
    {
        $pdo = db($config);
        $pdo->exec('CREATE TABLE IF NOT EXISTS email_change_requests (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            requested_by INT NULL,
            old_email VARCHAR(190) NOT NULL,
            new_email VARCHAR(190) NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email_change_token (token_hash),
            INDEX idx_email_change_user (user_id),
            CONSTRAINT fk_email_change_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }
}

if (!function_exists('ensure_account_invitations_table')) {
    function ensure_account_invitations_table(array $config): void
    {
        $pdo = db($config);
        $pdo->exec('CREATE TABLE IF NOT EXISTS account_invitations (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            email VARCHAR(190) NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_account_invite_user (user_id),
            INDEX idx_account_invite_token (token_hash),
            INDEX idx_account_invite_expires (expires_at),
            CONSTRAINT fk_account_invite_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }
}

if (!function_exists('ensure_user_security_columns')) {
    function ensure_user_security_columns(array $config): void
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
}

if (!function_exists('ensure_notifications_table')) {
    function ensure_notifications_table(array $config): void
    {
        $pdo = db($config);
        $pdo->exec('CREATE TABLE IF NOT EXISTS notifications (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            title VARCHAR(190) NOT NULL,
            message VARCHAR(255) NOT NULL,
            target_url VARCHAR(255) DEFAULT NULL,
            read_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_notif_user (user_id),
            INDEX idx_notif_read (read_at),
            CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        // Compatibilite avec schemas existants: ajoute les colonnes manquantes.
        $columns = [
            'title' => 'ALTER TABLE notifications ADD COLUMN title VARCHAR(190) NOT NULL DEFAULT "Notification"',
            'message' => 'ALTER TABLE notifications ADD COLUMN message VARCHAR(255) NOT NULL DEFAULT ""',
            'target_url' => 'ALTER TABLE notifications ADD COLUMN target_url VARCHAR(255) DEFAULT NULL',
            'read_at' => 'ALTER TABLE notifications ADD COLUMN read_at DATETIME NULL',
            'created_at' => 'ALTER TABLE notifications ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        ];

        foreach ($columns as $columnName => $ddl) {
            if (!has_table_column($config, 'notifications', $columnName)) {
                $pdo->exec($ddl);
            }
        }
    }
}

if (!function_exists('ensure_reports_workflow_columns')) {
    function ensure_reports_workflow_columns(array $config): void
    {
        $pdo = db($config);
        $columns = [
            'urgency_level' => "ALTER TABLE reports ADD COLUMN urgency_level VARCHAR(20) NOT NULL DEFAULT 'Moyenne'",
            'is_validated' => 'ALTER TABLE reports ADD COLUMN is_validated TINYINT(1) NOT NULL DEFAULT 0',
            'validated_by' => 'ALTER TABLE reports ADD COLUMN validated_by INT NULL',
            'validated_at' => 'ALTER TABLE reports ADD COLUMN validated_at DATETIME NULL',
            'diffused_at' => 'ALTER TABLE reports ADD COLUMN diffused_at DATETIME NULL',
            'workflow_status' => "ALTER TABLE reports ADD COLUMN workflow_status VARCHAR(40) NOT NULL DEFAULT 'Brouillon'",
            'incident_label' => 'ALTER TABLE reports ADD COLUMN incident_label VARCHAR(190) DEFAULT NULL',
            'province' => 'ALTER TABLE reports ADD COLUMN province VARCHAR(120) DEFAULT NULL',
            'gps_lat' => 'ALTER TABLE reports ADD COLUMN gps_lat DECIMAL(10,7) DEFAULT NULL',
            'gps_lng' => 'ALTER TABLE reports ADD COLUMN gps_lng DECIMAL(10,7) DEFAULT NULL',
            'victims_count' => 'ALTER TABLE reports ADD COLUMN victims_count INT DEFAULT NULL',
            'analysis_text' => 'ALTER TABLE reports ADD COLUMN analysis_text TEXT DEFAULT NULL',
            'additional_notes' => 'ALTER TABLE reports ADD COLUMN additional_notes TEXT DEFAULT NULL',
            'territory' => 'ALTER TABLE reports ADD COLUMN territory VARCHAR(140) DEFAULT NULL',
            'health_zone' => 'ALTER TABLE reports ADD COLUMN health_zone VARCHAR(140) DEFAULT NULL',
            'groupement' => 'ALTER TABLE reports ADD COLUMN groupement VARCHAR(140) DEFAULT NULL',
            'village' => 'ALTER TABLE reports ADD COLUMN village VARCHAR(140) DEFAULT NULL',
            'incident_type' => 'ALTER TABLE reports ADD COLUMN incident_type VARCHAR(160) DEFAULT NULL',
            'displaced_households' => 'ALTER TABLE reports ADD COLUMN displaced_households INT DEFAULT NULL',
            'priority_needs_text' => 'ALTER TABLE reports ADD COLUMN priority_needs_text TEXT DEFAULT NULL',
            'recommendations_text' => 'ALTER TABLE reports ADD COLUMN recommendations_text TEXT DEFAULT NULL',
            'submitted_at' => 'ALTER TABLE reports ADD COLUMN submitted_at DATETIME NULL',
            'reviewed_at' => 'ALTER TABLE reports ADD COLUMN reviewed_at DATETIME NULL',
            'published_at' => 'ALTER TABLE reports ADD COLUMN published_at DATETIME NULL',
            'rejected_at' => 'ALTER TABLE reports ADD COLUMN rejected_at DATETIME NULL',
        ];

        foreach ($columns as $columnName => $ddl) {
            $check = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS
                                    WHERE TABLE_SCHEMA = DATABASE()
                                      AND TABLE_NAME = :table_name
                                      AND COLUMN_NAME = :column_name');
            $check->execute([
                'table_name' => 'reports',
                'column_name' => $columnName,
            ]);
            if ((int) $check->fetchColumn() === 0) {
                $pdo->exec($ddl);
            }
        }

        $pdo->exec('CREATE TABLE IF NOT EXISTS report_status_history (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            report_id BIGINT NOT NULL,
            status_label VARCHAR(60) NOT NULL,
            event_note VARCHAR(255) DEFAULT NULL,
            changed_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_rsh_report (report_id),
            INDEX idx_rsh_created (created_at),
            CONSTRAINT fk_rsh_report FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
            CONSTRAINT fk_rsh_user FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $pdo->exec('CREATE TABLE IF NOT EXISTS report_attachments (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            report_id BIGINT NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            storage_path VARCHAR(255) NOT NULL,
            mime_type VARCHAR(120) DEFAULT NULL,
            file_size BIGINT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ra_report (report_id),
            CONSTRAINT fk_ra_report FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }
}

if (!function_exists('report_workflow_status_expr')) {
    function report_workflow_status_expr(array $config, string $reportAlias = 'r'): string
    {
        $hasWorkflowStatus = has_table_column($config, 'reports', 'workflow_status');
        $hasIsValidated = has_table_column($config, 'reports', 'is_validated');

        if ($hasWorkflowStatus) {
            return 'COALESCE(NULLIF(TRIM(' . $reportAlias . '.workflow_status), ""), "Brouillon")';
        }

        if ($hasIsValidated) {
            return 'CASE WHEN ' . $reportAlias . '.is_validated = 1 THEN "Valide" ELSE "Soumis" END';
        }

        return '"Soumis"';
    }
}

if (!function_exists('create_notification')) {
    function create_notification(array $config, ?int $userId, string $title, string $message, ?string $url = null): void
    {
        $pdo = db($config);
        $stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message, target_url)
                               VALUES (:user_id, :title, :message, :target_url)');
        $stmt->bindValue('user_id', $userId, $userId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue('title', $title, PDO::PARAM_STR);
        $stmt->bindValue('message', $message, PDO::PARAM_STR);
        $stmt->bindValue('target_url', $url, $url === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();
    }
}

if (!function_exists('diffuserAlerte')) {
    function diffuserAlerte(int $alerteId): array
    {
        global $config;
        $pdo = db($config);
        $reportUserFk = reports_user_fk_column($config);

        $hasReportTitle = has_table_column($config, 'reports', 'title');
        $hasReportType = has_table_column($config, 'reports', 'report_type');
        $hasUrgencyLevel = has_table_column($config, 'reports', 'urgency_level');
        $hasReportContent = has_table_column($config, 'reports', 'content');
        $hasLocationText = has_table_column($config, 'reports', 'location_text');

        $titleExpr = $hasReportTitle
            ? 'r.title'
            : ($hasReportContent ? 'SUBSTRING(COALESCE(r.content, ""), 1, 120)' : 'CONCAT("Rapport #", r.id)');
        $reportTypeExpr = $hasReportType
            ? 'r.report_type'
            : '"FLASH"';
        $urgencyExpr = $hasUrgencyLevel
            ? 'r.urgency_level'
            : '"Moyenne"';
        $contentExpr = $hasReportContent
            ? 'r.content'
            : '""';
        $locationExpr = $hasLocationText
            ? 'r.location_text'
            : 'NULL';

                $authorExpr = $reportUserFk !== null ? 'u.full_name' : '"Utilisateur inconnu"';
                $joinUserSql = $reportUserFk !== null
                        ? 'LEFT JOIN users u ON u.id = r.' . $reportUserFk
                        : '';

                $stmt = $pdo->prepare('SELECT r.id, '
            . $titleExpr . ' AS title, '
            . $reportTypeExpr . ' AS report_type, '
                        . $contentExpr . ' AS content, '
                        . $locationExpr . ' AS location_text, '
            . $urgencyExpr . ' AS urgency_level, '
                        . 'r.created_at, ' . $authorExpr . ' AS author_name
              FROM reports r
                            ' . $joinUserSql . '
              WHERE r.id = :id
              LIMIT 1');
        $stmt->execute(['id' => $alerteId]);
        $report = $stmt->fetch();

        if (!is_array($report)) {
            return ['success' => false, 'sent' => 0, 'failed' => 0, 'error' => 'Alerte introuvable.'];
        }

        $recipientsStmt = $pdo->query("SELECT email, full_name FROM users WHERE is_active = 1 AND statut = 'Actif' ORDER BY id DESC LIMIT 500");
        $recipients = $recipientsStmt->fetchAll();
        if (!is_array($recipients) || $recipients === []) {
            return ['success' => false, 'sent' => 0, 'failed' => 0, 'error' => 'Aucun destinataire actif.'];
        }

        $appUrl = rtrim((string) ($config['app_url'] ?? ''), '/');
        $detailsUrl = $appUrl . '/pages/reports/alerte_details.php?id=' . (int) $report['id'];

        // Template html humanitaire style ONU (bleu/gris) pour diffusion rapide.
        $html = '<!doctype html><html><body style="margin:0;background:#eef2f7;font-family:Arial,sans-serif;color:#1f2a37;">'
            . '<div style="max-width:760px;margin:22px auto;background:#ffffff;border:1px solid #d7e2f0;border-radius:12px;overflow:hidden;">'
            . '<div style="background:#005b9a;color:#fff;padding:16px 22px;"><h2 style="margin:0;font-size:20px;">Diffusion d\'alerte SyDRA</h2></div>'
            . '<div style="padding:20px 22px;">'
            . '<p style="margin:0 0 12px;">Une alerte validée nécessite votre attention.</p>'
            . '<table style="width:100%;border-collapse:collapse;background:#f8fafc;border:1px solid #e2e8f0;">'
            . '<tr><td style="padding:10px;border-bottom:1px solid #e2e8f0;width:180px;"><strong>Lieu</strong></td><td style="padding:10px;border-bottom:1px solid #e2e8f0;">' . htmlspecialchars((string) ($report['location_text'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') . '</td></tr>'
            . '<tr><td style="padding:10px;border-bottom:1px solid #e2e8f0;"><strong>Type incident</strong></td><td style="padding:10px;border-bottom:1px solid #e2e8f0;">' . htmlspecialchars((string) ($report['report_type'] ?? 'FLASH'), ENT_QUOTES, 'UTF-8') . '</td></tr>'
            . '<tr><td style="padding:10px;"><strong>Urgence</strong></td><td style="padding:10px;">' . htmlspecialchars((string) ($report['urgency_level'] ?? 'Moyenne'), ENT_QUOTES, 'UTF-8') . '</td></tr>'
            . '</table>'
            . '<p style="margin:14px 0 0;line-height:1.45;">' . nl2br(htmlspecialchars((string) ($report['content'] ?? ''), ENT_QUOTES, 'UTF-8')) . '</p>'
            . '<div style="margin:20px 0 8px;">'
            . '<a href="' . htmlspecialchars($detailsUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;background:#005b9a;color:#fff;text-decoration:none;font-weight:700;padding:12px 18px;border-radius:8px;">Consulter le rapport complet</a>'
            . '</div>'
            . '<p style="font-size:12px;color:#475569;">Si vous n\'etes pas connecte, l\'authentification SyDRA sera demandee.</p>'
            . '</div></div></body></html>';

        $subject = 'Alerte validée - action requise';
        $sent = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            $email = strtolower(trim((string) ($recipient['email'] ?? '')));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $failed++;
                continue;
            }
            $result = sendAppMailDetailed($config, $email, $subject, $html, true);
            if ((bool) ($result['success'] ?? false)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return ['success' => $sent > 0, 'sent' => $sent, 'failed' => $failed, 'error' => $sent > 0 ? '' : 'Aucun email n\'a pu être envoyé.'];
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
            $tips[] = 'SMTP_SECURE doit être tls, ssl ou none.';
        }

        $lower = strtolower($errorMessage);
        if (str_contains($lower, 'could not connect') || str_contains($lower, 'connection refused') || str_contains($lower, 'timed out')) {
            $tips[] = 'Le serveur SMTP est inaccessible: vérifiez host, port, firewall et DNS.';
        }
        if (str_contains($lower, 'authentication') || str_contains($lower, 'username') || str_contains($lower, 'password')) {
            $tips[] = 'Échec d\'authentification: vérifiez SMTP_USER/SMTP_PASS et les autorisations du compte.';
        }
        if (str_contains($lower, 'tls') || str_contains($lower, 'ssl') || str_contains($lower, 'certificate')) {
            $tips[] = 'Problème de chiffrement: testez SMTP_SECURE=tls avec port 587 ou ssl avec port 465.';
        }

        if ($tips === []) {
            $tips[] = 'Vérifiez SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_SECURE et MAIL_FROM dans .env.';
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
                    $storedHash = (string) ($existing['password_hash'] ?? '');
                    $hashInfo = password_get_info($storedHash);
                    $hasValidHash = $storedHash !== '' && ((int) ($hashInfo['algo'] ?? 0) !== 0);
                    $mustRepairAccount = !$hasValidHash || (int) ($existing['is_active'] ?? 0) !== 1;

                    if ($mustRepairAccount) {
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
                    $storedHash = (string) ($existing['password_hash'] ?? '');
                    $hashInfo = password_get_info($storedHash);
                    $hasValidHash = $storedHash !== '' && ((int) ($hashInfo['algo'] ?? 0) !== 0);
                    $mustRepairAccount = !$hasValidHash || (int) ($existing['is_active'] ?? 0) !== 1;

                    if ($mustRepairAccount) {
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
ensure_user_security_columns($config);
ensure_password_reset_table($config);
ensure_email_change_requests_table($config);
ensure_account_invitations_table($config);
ensure_notifications_table($config);
ensure_reports_workflow_columns($config);
ensure_upload_directories();
$appEnv = strtolower((string) ($config['app_env'] ?? 'development'));
if ($appEnv !== 'production') {
    ensure_demo_users($config);
}

$authUser = auth_user($config);

$pageAliases = [
    'login' => 'connexion',
    'forgot_password' => 'mot_de_passe_oublie',
    'dashboard' => 'tableau_de_bord',
    'reports_create' => 'rapport_creer',
    'reports_list' => 'rapports_liste',
    'rapportage-home' => 'rapportage',
    'rapportage-user-list' => 'rapportage-liste-user',
    'rapportage-admin-list' => 'rapportage-admin-list',
    'rapportage-view' => 'rapportage-voir',
    'rapportage-details' => 'rapportage-voir',
    'rapportage-create-ai' => 'rapportage-creer-AI',
    'rapportage-create-wizard' => 'rapportage-creer-wizar',
    'rapportage-mes-alertes' => 'rapportage-liste-user',
    'rapportage-coordination' => 'rapportage-admin-list',
    'profile' => 'profil',
    'users' => 'utilisateurs',
    'help' => 'aide',
    'activate' => 'activation_compte',
    'confirm_email' => 'confirmer_email',
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

        $stmt = $pdo->prepare('SELECT id, password_hash, is_active, statut, must_change_password
                               FROM users
                               WHERE email = :email
                               LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        if (!is_array($row)
            || (int) ($row['is_active'] ?? 0) !== 1
            || strtolower((string) ($row['statut'] ?? 'Actif')) === 'bloque'
            || !password_verify($password, (string) $row['password_hash'])) {
            set_flash('error', 'Identifiants invalides.');
            header('Location: ?page=connexion');
            exit;
        }

        $logStmt = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $logStmt->execute(['id' => (int) $row['id']]);

        $_SESSION['auth_user_id'] = (int) $row['id'];

        if ((int) ($row['must_change_password'] ?? 0) === 1) {
            set_flash('error', 'Vous devez modifier votre mot de passe après cette première connexion.');
            header('Location: ?page=profil&must_change_password=1');
            exit;
        }

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

        $subject = 'Réinitialisation de votre mot de passe SyDRA';
        $body = "Bonjour " . (string) ($user['full_name'] ?? 'utilisateur') . ",\n\n"
            . "Nous avons reçu une demande de réinitialisation de mot de passe.\n"
            . "Cliquez sur ce lien pour définir un nouveau mot de passe:\n"
            . $resetLink . "\n\n"
            . "Ce lien expire dans 1 heure.\n"
            . "Si vous n'êtes pas à l'origine de cette demande, ignorez ce message.\n"
            . "Si le problème persiste, contactez l'admin: " . $supportEmail . "\n\n"
            . "Équipe SyDRA";

        $mailResult = sendAppMailDetailed($config, (string) $user['email'], $subject, $body);
        if ((bool) ($mailResult['success'] ?? false)) {
            $_SESSION['password_reset_recent'] = $email;
            set_flash('success', 'Un email de réinitialisation a été envoyé à ' . (string) $user['email'] . '.');
        } else {
            $detail = trim((string) ($mailResult['error'] ?? 'Échec inconnu.'));
            set_flash('error', 'Échec d\'envoi de l\'email de réinitialisation: ' . $detail);
        }

        header('Location: ?page=mot_de_passe_oublie');
        exit;
    }

    if ($action === 'reset_password') {
        $token = trim((string) ($_POST['token'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

        if ($token === '') {
            set_flash('error', 'Lien de réinitialisation invalide.');
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
            set_flash('error', 'Ce lien est invalide, expiré ou déjà utilisé.');
            header('Location: ?page=connexion');
            exit;
        }

        $newHash = password_hash($password, PASSWORD_BCRYPT);
        if (!is_string($newHash) || $newHash === '') {
            set_flash('error', 'Impossible de sécuriser le nouveau mot de passe.');
            header('Location: ?page=connexion');
            exit;
        }

        try {
            $pdo->beginTransaction();

            $updateUser = $pdo->prepare('UPDATE users
                                         SET password_hash = :password_hash,
                                             is_active = 1,
                                             statut = "Actif",
                                             must_change_password = 0
                                         WHERE id = :id');
            $updateUser->execute([
                'password_hash' => $newHash,
                'id' => (int) $resetRow['user_id'],
            ]);

            if ($updateUser->rowCount() < 1) {
                throw new RuntimeException('Aucune ligne utilisateur mise à jour lors du reset password.');
            }

            $markUsed = $pdo->prepare('UPDATE password_reset_requests SET used_at = NOW() WHERE id = :id');
            $markUsed->execute(['id' => (int) $resetRow['id']]);

            if ($markUsed->rowCount() < 1) {
                throw new RuntimeException('Le token de reset n\'a pas pu être marqué comme utilisé.');
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            set_flash('error', 'Échec de la réinitialisation du mot de passe. Réessayez.');
            header('Location: ?page=connexion');
            exit;
        }

        set_flash('success', 'Mot de passe réinitialisé avec succès. Connectez-vous maintenant.');
        header('Location: ?page=connexion');
        exit;
    }

    if ($action === 'create_report') {
        require_auth($authUser);

        $title = trim((string) ($_POST['title'] ?? ''));
        $reportType = strtoupper(trim((string) ($_POST['report_type'] ?? 'FLASH')));
        $location = trim((string) ($_POST['location_text'] ?? ''));
        $urgency = trim((string) ($_POST['urgency_level'] ?? 'Moyenne'));
        $content = trim((string) ($_POST['content'] ?? ''));

        if ($title === '' || $content === '') {
            set_flash('error', 'Titre et contenu sont obligatoires.');
            header('Location: ?page=rapport_creer');
            exit;
        }

        if (!in_array($reportType, ['FLASH', 'NOTE'], true)) {
            $reportType = 'FLASH';
        }

        if (!in_array($urgency, ['Faible', 'Moyenne', 'Elevee', 'Critique'], true)) {
            $urgency = 'Moyenne';
        }

        $stmt = $pdo->prepare('INSERT INTO reports (
                                    user_id,
                                    title,
                                    report_type,
                                    content,
                                    location_text,
                                    urgency_level,
                                    workflow_status,
                                    incident_label,
                                    province,
                                    submitted_at
                               ) VALUES (
                                    :user_id,
                                    :title,
                                    :report_type,
                                    :content,
                                    :location_text,
                                    :urgency_level,
                                    :workflow_status,
                                    :incident_label,
                                    :province,
                                    NOW()
                               )');
        $stmt->execute([
            'user_id' => (int) $authUser['id'],
            'title' => $title,
            'report_type' => $reportType,
            'content' => $content,
            'location_text' => $location !== '' ? $location : null,
            'urgency_level' => $urgency,
            'workflow_status' => 'Soumis',
            'incident_label' => $title,
            'province' => $location !== '' ? $location : null,
        ]);

        $reportId = (int) $pdo->lastInsertId();
        $historyStmt = $pdo->prepare('INSERT INTO report_status_history (report_id, status_label, event_note, changed_by)
                                      VALUES (:report_id, :status_label, :event_note, :changed_by)');
        $historyStmt->execute([
            'report_id' => $reportId,
            'status_label' => 'Soumis',
            'event_note' => 'Rapport soumis par l\'organisation.',
            'changed_by' => (int) ($authUser['id'] ?? 0),
        ]);

        create_notification(
            $config,
            null,
            'Nouveau rapport',
            'Nouvelle alerte soumise a ' . ($location !== '' ? $location : 'localisation non specifiee'),
            '?page=rapports_liste'
        );

        set_flash('success', 'Rapport enregistré.');
        header('Location: ?page=rapports_liste');
        exit;
    }

    if ($action === 'change_password') {
        require_auth($authUser);

        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $newPasswordConfirmation = (string) ($_POST['new_password_confirmation'] ?? '');

        if ($newPassword !== $newPasswordConfirmation) {
            set_flash('error', 'La confirmation du nouveau mot de passe ne correspond pas.');
            header('Location: ?page=profil');
            exit;
        }

        $policyError = validate_password_policy($newPassword);
        if ($policyError !== null) {
            set_flash('error', $policyError);
            header('Location: ?page=profil');
            exit;
        }

        $currentHashStmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
        $currentHashStmt->execute(['id' => (int) $authUser['id']]);
        $dbUser = $currentHashStmt->fetch();

        if (!is_array($dbUser) || !password_verify($currentPassword, (string) ($dbUser['password_hash'] ?? ''))) {
            set_flash('error', 'Mot de passe actuel incorrect.');
            header('Location: ?page=profil');
            exit;
        }

        $updatePassword = $pdo->prepare('UPDATE users
                                         SET password_hash = :password_hash,
                                             must_change_password = 0
                                         WHERE id = :id');
        $updatePassword->execute([
            'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT),
            'id' => (int) $authUser['id'],
        ]);

        set_flash('success', 'Mot de passe mis a jour avec succes.');
        header('Location: ?page=profil');
        exit;
    }

    if ($action === 'update_profile') {
        require_auth($authUser);

        $fullName = trim((string) ($_POST['organization_display_name'] ?? $_POST['full_name'] ?? ''));
        $phone = trim((string) ($_POST['telephone_organisation'] ?? $_POST['phone'] ?? ''));
        $siteWeb = trim((string) ($_POST['site_web'] ?? ''));
        $organizationName = trim((string) ($_POST['organization_name'] ?? ''));
        $bio = trim((string) ($_POST['bio_organisation'] ?? $_POST['bio'] ?? ''));

        if ($siteWeb !== '' && !preg_match('#^https?://#i', $siteWeb)) {
            $siteWeb = 'https://' . $siteWeb;
        }

        if ($siteWeb !== '' && !filter_var($siteWeb, FILTER_VALIDATE_URL)) {
            set_flash('error', 'Le site web est invalide.');
            header('Location: ?page=profil');
            exit;
        }

        if ($fullName === '') {
            set_flash('error', 'Le nom de l\'organisation est obligatoire.');
            header('Location: ?page=profil');
            exit;
        }

        $needsMandatoryCompletion = requires_profile_completion($authUser)
            || (isset($_GET['must_complete_profile']) && (string) $_GET['must_complete_profile'] === '1');
        if ($needsMandatoryCompletion && ($phone === '' || $bio === '')) {
            set_flash('error', 'Téléphone organisation et biographie sont obligatoires pour terminer la première configuration.');
            header('Location: ?page=profil&must_complete_profile=1');
            exit;
        }

        $avatarPath = (string) ($authUser['avatar_path'] ?? $authUser['logo_path'] ?? '');
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
                                   logo_path = :logo_path,
                                   phone = :phone,
                                   telephone_organisation = :telephone_organisation,
                                   site_web = :site_web,
                                   organization_name = :organization_name,
                                   bio = :bio,
                                   bio_organisation = :bio_organisation
                               WHERE id = :id');
        $stmt->execute([
            'full_name' => $fullName,
            'avatar_path' => $avatarPath !== '' ? $avatarPath : null,
            'logo_path' => $avatarPath !== '' ? $avatarPath : null,
            'phone' => $phone !== '' ? $phone : null,
            'telephone_organisation' => $phone !== '' ? $phone : null,
            'site_web' => $siteWeb !== '' ? $siteWeb : null,
            'organization_name' => $organizationName !== '' ? $organizationName : null,
            'bio' => $bio !== '' ? $bio : null,
            'bio_organisation' => $bio !== '' ? $bio : null,
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

        $orgAcronym = trim((string) ($_POST['org_acronym'] ?? $_POST['full_name'] ?? ''));
        $orgLongName = trim((string) ($_POST['organization_name'] ?? $_POST['organization_long_name'] ?? ''));
        $orgPhone = trim((string) ($_POST['telephone_organisation'] ?? ''));
        $orgWebsite = trim((string) ($_POST['site_web'] ?? ''));
        $orgBio = trim((string) ($_POST['bio_organisation'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $role = strtoupper(trim((string) ($_POST['role'] ?? 'REPORTER')));

        if ($orgAcronym === '' || $orgLongName === '' || $email === '') {
            set_flash('error', 'Acronyme, nom long de l\'organisation et email sont obligatoires.');
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
                $roleAliases = [
                    'CLUSTER_LEADER' => ['LEAD_GTMP', 'GTMP_LEAD'],
                    'LEAD_GTMP' => ['CLUSTER_LEADER', 'GTMP_LEAD'],
                    'GTMP_LEAD' => ['CLUSTER_LEADER', 'LEAD_GTMP'],
                    'CLUSTER_CO_LEAD' => ['CO_LEAD'],
                    'REPORTER' => ['RAPPORTEUR'],
                ];

                foreach ($roleAliases[$role] ?? [] as $aliasCode) {
                    $roleId = resolve_role_id($config, $aliasCode);
                    if ($roleId !== null) {
                        break;
                    }
                }
            }

            if ($roleId === null) {
                $fallbackRoleStmt = $pdo->query('SELECT id
                                                 FROM roles
                                                 WHERE UPPER(code) <> "ADMIN"
                                                 ORDER BY CASE UPPER(code)
                                                     WHEN "REPORTER" THEN 1
                                                     WHEN "CLUSTER_CO_LEAD" THEN 2
                                                     WHEN "CLUSTER_LEADER" THEN 3
                                                     WHEN "LEAD_GTMP" THEN 4
                                                     WHEN "GTMP_LEAD" THEN 5
                                                     ELSE 99
                                                 END,
                                                 id ASC
                                                 LIMIT 1');
                $fallbackRoleId = $fallbackRoleStmt ? $fallbackRoleStmt->fetchColumn() : false;
                if ($fallbackRoleId !== false) {
                    $roleId = (int) $fallbackRoleId;
                }
            }

            if ($roleId === null) {
                set_flash('error', 'Aucun rôle utilisable trouvé en base. Vérifiez la table roles.');
                header('Location: ?page=utilisateurs');
                exit;
            }

            $insertUser = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role_id, organization_name, is_active, statut, must_change_password)
                                         VALUES (:full_name, :email, :password_hash, :role_id, :organization_name, 0, "Actif", 1)');
            $insertUser->execute([
                'full_name' => $orgAcronym,
                'email' => $email,
                'password_hash' => $placeholderHash,
                'role_id' => $roleId,
                'organization_name' => $orgLongName,
            ]);
            $userId = (int) $pdo->lastInsertId();
        } else {
            $insertUser = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role, organization_name, is_active, statut, must_change_password)
                                         VALUES (:full_name, :email, :password_hash, :role, :organization_name, 0, "Actif", 1)');
            $insertUser->execute([
                'full_name' => $orgAcronym,
                'email' => $email,
                'password_hash' => $placeholderHash,
                'role' => $role,
                'organization_name' => $orgLongName,
            ]);
            $userId = (int) $pdo->lastInsertId();
        }

        if ($orgWebsite !== '' && !preg_match('#^https?://#i', $orgWebsite)) {
            $orgWebsite = 'https://' . $orgWebsite;
        }

        if ($orgWebsite === '' || filter_var($orgWebsite, FILTER_VALIDATE_URL)) {
            $updateOrgMeta = $pdo->prepare('UPDATE users
                                            SET telephone_organisation = :telephone_organisation,
                                                site_web = :site_web,
                                                bio_organisation = :bio_organisation,
                                                phone = :phone,
                                                bio = :bio
                                            WHERE id = :id');
            $updateOrgMeta->execute([
                'telephone_organisation' => $orgPhone !== '' ? $orgPhone : null,
                'site_web' => $orgWebsite !== '' ? $orgWebsite : null,
                'bio_organisation' => $orgBio !== '' ? $orgBio : null,
                'phone' => $orgPhone !== '' ? $orgPhone : null,
                'bio' => $orgBio !== '' ? $orgBio : null,
                'id' => $userId,
            ]);
        }

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
        $body = "Bonjour {$orgAcronym},\n\n"
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

    if ($action === 'request_email_change') {
        require_auth($authUser);
        if (!is_admin($authUser)) {
            set_flash('error', 'Action réservée aux administrateurs.');
            header('Location: ?page=utilisateurs');
            exit;
        }

        if (trim((string) ($_POST['expires_hours'] ?? '')) !== '' && ctype_digit((string) $_POST['expires_hours'])) {
            $expiresHours = (int) $_POST['expires_hours'];
            if (!in_array($expiresHours, [24, 48], true)) {
                $expiresHours = 48;
            }
        } else {
            $expiresHours = 48;
        }

        $targetUserId = (int) ($_POST['target_user_id'] ?? 0);
        $newEmail = strtolower(trim((string) ($_POST['new_email'] ?? '')));

        if ($targetUserId <= 0 || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Veuillez renseigner un utilisateur et une nouvelle adresse email valide.');
            header('Location: ?page=utilisateurs');
            exit;
        }

        $targetStmt = $pdo->prepare('SELECT id, full_name, email FROM users WHERE id = :id LIMIT 1');
        $targetStmt->execute(['id' => $targetUserId]);
        $target = $targetStmt->fetch();

        if (!is_array($target)) {
            set_flash('error', 'Utilisateur introuvable.');
            header('Location: ?page=utilisateurs');
            exit;
        }

        $currentEmail = strtolower(trim((string) ($target['email'] ?? '')));
        if ($newEmail === $currentEmail) {
            set_flash('error', 'La nouvelle adresse email doit être différente de l adresse actuelle.');
            header('Location: ?page=utilisateurs');
            exit;
        }

        $existsStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $existsStmt->execute(['email' => $newEmail]);
        if (is_array($existsStmt->fetch())) {
            set_flash('error', 'Cette adresse email est déjà utilisée par un autre compte.');
            header('Location: ?page=utilisateurs');
            exit;
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + ($expiresHours * 3600));

        $cleanupStmt = $pdo->prepare('DELETE FROM email_change_requests WHERE user_id = :user_id AND used_at IS NULL');
        $cleanupStmt->execute(['user_id' => $targetUserId]);

        $insertStmt = $pdo->prepare('INSERT INTO email_change_requests (
            user_id, requested_by, old_email, new_email, token_hash, expires_at
        ) VALUES (
            :user_id, :requested_by, :old_email, :new_email, :token_hash, :expires_at
        )');
        $insertStmt->execute([
            'user_id' => $targetUserId,
            'requested_by' => (int) ($authUser['id'] ?? 0),
            'old_email' => $currentEmail,
            'new_email' => $newEmail,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);

        $appUrl = rtrim((string) ($config['app_url'] ?? ''), '/');
        $confirmUrl = $appUrl . '/?page=confirmer_email&token=' . urlencode($token);
        $targetName = (string) ($target['full_name'] ?? 'utilisateur');

        $html = '<!doctype html><html><body style="margin:0;background:#eef2f7;font-family:Arial,sans-serif;color:#1f2a37;">'
            . '<div style="max-width:760px;margin:22px auto;background:#ffffff;border:1px solid #d7e2f0;border-radius:12px;overflow:hidden;">'
            . '<div style="background:#005bbb;color:#fff;padding:16px 22px;"><h2 style="margin:0;font-size:20px;">Confirmation de votre nouvelle adresse email</h2></div>'
            . '<div style="padding:20px 22px;">'
            . '<p>Bonjour ' . htmlspecialchars($targetName, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Une demande de changement d adresse email a été initiée pour votre compte SyDRA.</p>'
            . '<p>Nouvelle adresse demandée: <strong>' . htmlspecialchars($newEmail, ENT_QUOTES, 'UTF-8') . '</strong></p>'
            . '<p style="margin:20px 0 8px;">'
            . '<a href="' . htmlspecialchars($confirmUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;background:#005bbb;color:#fff;text-decoration:none;font-weight:700;padding:12px 18px;border-radius:8px;">Confirmer ma nouvelle adresse</a>'
            . '</p>'
            . '<p style="font-size:12px;color:#475569;">Ce lien expire dans ' . (int) $expiresHours . ' heures. Si vous n êtes pas à l origine de cette demande, ignorez ce message.</p>'
            . '</div></div></body></html>';

        $mailResult = sendAppMailDetailed($config, $newEmail, 'Confirmez votre nouvelle adresse email SyDRA', $html, true);
        if ((bool) ($mailResult['success'] ?? false)) {
            set_flash('success', 'Un email de confirmation a été envoyé à la nouvelle adresse.');
        } else {
            $errorMsg = trim((string) ($mailResult['error'] ?? 'Erreur inconnue.'));
            set_flash('error', 'Demande enregistrée mais email non envoyé: ' . $errorMsg);
        }

        header('Location: ?page=utilisateurs');
        exit;
    }

    if ($action === 'update_user_admin') {
        require_auth($authUser);
        if (!is_admin($authUser)) {
            set_flash('error', 'Action réservée aux administrateurs.');
            header('Location: ?page=utilisateurs');
            exit;
        }

        $targetUserId = (int) ($_POST['target_user_id'] ?? 0);
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $organizationName = trim((string) ($_POST['organization_name'] ?? ''));
        $role = strtoupper(trim((string) ($_POST['role'] ?? 'REPORTER')));
        $status = strtolower(trim((string) ($_POST['statut'] ?? 'actif'))) === 'bloque' ? 'Bloque' : 'Actif';
        $isActive = $status === 'Actif' ? 1 : 0;
        $phone = trim((string) ($_POST['telephone_organisation'] ?? ''));
        $siteWeb = trim((string) ($_POST['site_web'] ?? ''));
        $bio = trim((string) ($_POST['bio_organisation'] ?? ''));
        $newEmail = strtolower(trim((string) ($_POST['new_email'] ?? '')));
        $expiresHours = (int) ($_POST['expires_hours'] ?? 48);
        if (!in_array($expiresHours, [24, 48], true)) {
            $expiresHours = 48;
        }

        if ($targetUserId <= 0 || $fullName === '' || $organizationName === '') {
            set_flash('error', 'Acronyme, nom organisation et utilisateur cible sont obligatoires.');
            header('Location: ?page=utilisateurs');
            exit;
        }

        if ($siteWeb !== '' && !preg_match('#^https?://#i', $siteWeb)) {
            $siteWeb = 'https://' . $siteWeb;
        }
        if ($siteWeb !== '' && !filter_var($siteWeb, FILTER_VALIDATE_URL)) {
            set_flash('error', 'Le site web fourni est invalide.');
            header('Location: ?page=utilisateurs');
            exit;
        }

        $targetStmt = $pdo->prepare('SELECT id, full_name, email, role, role_id FROM users WHERE id = :id LIMIT 1');
        $targetStmt->execute(['id' => $targetUserId]);
        $target = $targetStmt->fetch();
        if (!is_array($target)) {
            set_flash('error', 'Utilisateur introuvable.');
            header('Location: ?page=utilisateurs');
            exit;
        }

        if ((int) ($authUser['id'] ?? 0) === $targetUserId && $isActive === 0) {
            set_flash('error', 'Vous ne pouvez pas vous désactiver vous-même.');
            header('Location: ?page=utilisateurs');
            exit;
        }

        $allowedRoles = detect_user_roles($config);
        if (!in_array($role, $allowedRoles, true)) {
            $role = strtoupper((string) ($target['role'] ?? 'REPORTER'));
        }

        if (role_storage_mode($config) === 'role_fk') {
            $roleId = resolve_role_id($config, $role);
            if ($roleId === null) {
                set_flash('error', 'Rôle introuvable dans la table roles.');
                header('Location: ?page=utilisateurs');
                exit;
            }
            $updateUser = $pdo->prepare('UPDATE users
                                         SET full_name = :full_name,
                                             organization_name = :organization_name,
                                             role_id = :role_id,
                                             statut = :statut,
                                             is_active = :is_active,
                                             telephone_organisation = :telephone_organisation,
                                             phone = :phone,
                                             site_web = :site_web,
                                             bio_organisation = :bio_organisation,
                                             bio = :bio
                                         WHERE id = :id');
            $updateUser->execute([
                'full_name' => $fullName,
                'organization_name' => $organizationName,
                'role_id' => $roleId,
                'statut' => $status,
                'is_active' => $isActive,
                'telephone_organisation' => $phone !== '' ? $phone : null,
                'phone' => $phone !== '' ? $phone : null,
                'site_web' => $siteWeb !== '' ? $siteWeb : null,
                'bio_organisation' => $bio !== '' ? $bio : null,
                'bio' => $bio !== '' ? $bio : null,
                'id' => $targetUserId,
            ]);
        } else {
            $updateUser = $pdo->prepare('UPDATE users
                                         SET full_name = :full_name,
                                             organization_name = :organization_name,
                                             role = :role,
                                             statut = :statut,
                                             is_active = :is_active,
                                             telephone_organisation = :telephone_organisation,
                                             phone = :phone,
                                             site_web = :site_web,
                                             bio_organisation = :bio_organisation,
                                             bio = :bio
                                         WHERE id = :id');
            $updateUser->execute([
                'full_name' => $fullName,
                'organization_name' => $organizationName,
                'role' => $role,
                'statut' => $status,
                'is_active' => $isActive,
                'telephone_organisation' => $phone !== '' ? $phone : null,
                'phone' => $phone !== '' ? $phone : null,
                'site_web' => $siteWeb !== '' ? $siteWeb : null,
                'bio_organisation' => $bio !== '' ? $bio : null,
                'bio' => $bio !== '' ? $bio : null,
                'id' => $targetUserId,
            ]);
        }

        $mailMessage = '';
        if ($newEmail !== '') {
            if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                set_flash('error', 'Nouvelle adresse email invalide.');
                header('Location: ?page=utilisateurs');
                exit;
            }

            $currentEmail = strtolower(trim((string) ($target['email'] ?? '')));
            if ($newEmail !== $currentEmail) {
                $existsStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
                $existsStmt->execute(['email' => $newEmail]);
                if (is_array($existsStmt->fetch())) {
                    set_flash('error', 'Cette adresse email est déjà utilisée par un autre compte.');
                    header('Location: ?page=utilisateurs');
                    exit;
                }

                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expiresAt = date('Y-m-d H:i:s', time() + ($expiresHours * 3600));

                $cleanupStmt = $pdo->prepare('DELETE FROM email_change_requests WHERE user_id = :user_id AND used_at IS NULL');
                $cleanupStmt->execute(['user_id' => $targetUserId]);

                $insertStmt = $pdo->prepare('INSERT INTO email_change_requests (
                    user_id, requested_by, old_email, new_email, token_hash, expires_at
                ) VALUES (
                    :user_id, :requested_by, :old_email, :new_email, :token_hash, :expires_at
                )');
                $insertStmt->execute([
                    'user_id' => $targetUserId,
                    'requested_by' => (int) ($authUser['id'] ?? 0),
                    'old_email' => $currentEmail,
                    'new_email' => $newEmail,
                    'token_hash' => $tokenHash,
                    'expires_at' => $expiresAt,
                ]);

                $appUrl = rtrim((string) ($config['app_url'] ?? ''), '/');
                $confirmUrl = $appUrl . '/?page=confirmer_email&token=' . urlencode($token);
                $targetName = (string) ($target['full_name'] ?? 'utilisateur');

                $html = '<!doctype html><html><body style="margin:0;background:#eef2f7;font-family:Arial,sans-serif;color:#1f2a37;">'
                    . '<div style="max-width:760px;margin:22px auto;background:#ffffff;border:1px solid #d7e2f0;border-radius:12px;overflow:hidden;">'
                    . '<div style="background:#005bbb;color:#fff;padding:16px 22px;"><h2 style="margin:0;font-size:20px;">Confirmation de votre nouvelle adresse email</h2></div>'
                    . '<div style="padding:20px 22px;">'
                    . '<p>Bonjour ' . htmlspecialchars($targetName, ENT_QUOTES, 'UTF-8') . ',</p>'
                    . '<p>Une demande de changement d adresse email a été initiée pour votre compte SyDRA.</p>'
                    . '<p>Nouvelle adresse demandée: <strong>' . htmlspecialchars($newEmail, ENT_QUOTES, 'UTF-8') . '</strong></p>'
                    . '<p style="margin:20px 0 8px;">'
                    . '<a href="' . htmlspecialchars($confirmUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;background:#005bbb;color:#fff;text-decoration:none;font-weight:700;padding:12px 18px;border-radius:8px;">Confirmer ma nouvelle adresse</a>'
                    . '</p>'
                    . '<p style="font-size:12px;color:#475569;">Ce lien expire dans ' . (int) $expiresHours . ' heures. Si vous n êtes pas à l origine de cette demande, ignorez ce message.</p>'
                    . '</div></div></body></html>';

                $mailResult = sendAppMailDetailed($config, $newEmail, 'Confirmez votre nouvelle adresse email SyDRA', $html, true);
                if ((bool) ($mailResult['success'] ?? false)) {
                    $mailMessage = ' Un email de confirmation (' . (int) $expiresHours . 'h) a été envoyé à la nouvelle adresse.';
                } else {
                    $errorMsg = trim((string) ($mailResult['error'] ?? 'Erreur inconnue.'));
                    $mailMessage = ' Demande email enregistrée, mais envoi SMTP en échec: ' . $errorMsg;
                }
            }
        }

        set_flash('success', 'Utilisateur mis à jour.' . $mailMessage);
        header('Location: ?page=utilisateurs');
        exit;
    }

    if ($action === 'confirm_email_change') {
        $token = trim((string) ($_POST['token'] ?? ''));
        if ($token === '') {
            set_flash('error', 'Lien de confirmation invalide.');
            header('Location: ?page=connexion');
            exit;
        }

        $reqStmt = $pdo->prepare('SELECT id, user_id, old_email, new_email, expires_at, used_at
                                  FROM email_change_requests
                                  WHERE token_hash = :token_hash
                                  LIMIT 1');
        $reqStmt->execute(['token_hash' => hash('sha256', $token)]);
        $request = $reqStmt->fetch();

        if (!is_array($request) || !empty($request['used_at']) || strtotime((string) $request['expires_at']) <= time()) {
            set_flash('error', 'Ce lien de confirmation est invalide, expiré ou déjà utilisé.');
            header('Location: ?page=connexion');
            exit;
        }

        $newEmail = strtolower(trim((string) ($request['new_email'] ?? '')));
        $existsStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $existsStmt->execute(['email' => $newEmail]);
        if (is_array($existsStmt->fetch())) {
            set_flash('error', 'Cette adresse email est déjà utilisée. Impossible de finaliser le changement.');
            header('Location: ?page=connexion');
            exit;
        }

        try {
            $pdo->beginTransaction();

            $updateUserStmt = $pdo->prepare('UPDATE users SET email = :email WHERE id = :id');
            $updateUserStmt->execute([
                'email' => $newEmail,
                'id' => (int) ($request['user_id'] ?? 0),
            ]);

            $markUsedStmt = $pdo->prepare('UPDATE email_change_requests SET used_at = NOW() WHERE id = :id');
            $markUsedStmt->execute(['id' => (int) ($request['id'] ?? 0)]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash('error', 'Une erreur est survenue pendant la confirmation. Veuillez réessayer.');
            header('Location: ?page=connexion');
            exit;
        }

        unset($_SESSION['auth_user_id']);
        set_flash('success', 'Adresse email confirmée et mise à jour. Connectez-vous maintenant. Si vous avez oublié votre mot de passe, cliquez sur "Mot de passe oublié".');
        header('Location: ?page=connexion');
        exit;
    }

    if ($action === 'test_smtp') {
        require_auth($authUser);
        if (!is_admin($authUser)) {
            set_flash('error', 'Action réservée aux administrateurs.');
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
        $body = "Ceci est un email de test SMTP envoyé par SyDRA le " . date('Y-m-d H:i:s');
        $result = sendAppMailDetailed($config, $recipient, $subject, $body);

        if ((bool) ($result['success'] ?? false)) {
            set_flash('success', 'SMTP OK: email de test envoyé à ' . $recipient . '.');
        } else {
            $errorMessage = trim((string) ($result['error'] ?? 'Erreur SMTP inconnue.'));
            $tips = smtp_recommendations($config, $errorMessage);
            set_flash('error', 'SMTP KO: ' . $errorMessage);
            set_flash('error', 'Recommandations: ' . implode(' | ', $tips));
        }

        header('Location: ?page=utilisateurs');
        exit;
    }

    if ($action === 'toggle_user_statut') {
        require_auth($authUser);
        if (!can_manage_users($authUser)) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => false, 'message' => 'Accès interdit.']);
            exit;
        }

        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        if ($targetUserId <= 0) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => false, 'message' => 'Utilisateur invalide.']);
            exit;
        }

        if ((int) ($authUser['id'] ?? 0) === $targetUserId) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => false, 'message' => 'Vous ne pouvez pas vous bloquer vous-meme.']);
            exit;
        }

        $targetStmt = $pdo->prepare('SELECT id, statut FROM users WHERE id = :id LIMIT 1');
        $targetStmt->execute(['id' => $targetUserId]);
        $target = $targetStmt->fetch();

        if (!is_array($target)) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => false, 'message' => 'Utilisateur introuvable.']);
            exit;
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

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'ok' => true,
            'message' => $nextStatus === 'Actif' ? 'Utilisateur debloque.' : 'Utilisateur bloque.',
            'new_status' => $nextStatus,
        ]);
        exit;
    }

    if ($action === 'delete_user_permanently') {
        require_auth($authUser);
        if (!is_admin($authUser)) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => false, 'message' => 'Action réservée aux administrateurs.']);
            exit;
        }

        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        if ($targetUserId <= 0) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => false, 'message' => 'Utilisateur invalide.']);
            exit;
        }

        if ((int) ($authUser['id'] ?? 0) === $targetUserId) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => false, 'message' => 'Vous ne pouvez pas supprimer votre propre compte.']);
            exit;
        }

        $targetStmt = $pdo->prepare('SELECT id, email FROM users WHERE id = :id LIMIT 1');
        $targetStmt->execute(['id' => $targetUserId]);
        $target = $targetStmt->fetch();
        if (!is_array($target)) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => false, 'message' => 'Utilisateur introuvable.']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $cleanupInv = $pdo->prepare('DELETE FROM account_invitations WHERE user_id = :user_id');
            $cleanupInv->execute(['user_id' => $targetUserId]);

            $cleanupReset = $pdo->prepare('DELETE FROM password_reset_requests WHERE user_id = :user_id');
            $cleanupReset->execute(['user_id' => $targetUserId]);

            $cleanupEmailChange = $pdo->prepare('DELETE FROM email_change_requests WHERE user_id = :user_id OR requested_by = :requested_by');
            $cleanupEmailChange->execute([
                'user_id' => $targetUserId,
                'requested_by' => $targetUserId,
            ]);

            $cleanupNotifs = $pdo->prepare('DELETE FROM notifications WHERE user_id = :user_id');
            $cleanupNotifs->execute(['user_id' => $targetUserId]);

            $deleteStmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $deleteStmt->execute(['id' => $targetUserId]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => false, 'message' => 'Suppression impossible pour le moment.']);
            exit;
        }

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'ok' => true,
            'message' => 'Compte supprimé définitivement.',
            'deleted_email' => (string) ($target['email'] ?? ''),
        ]);
        exit;
    }

    if ($action === 'mark_notifications_read') {
        require_auth($authUser);

        $hasNotifReadAt = has_table_column($config, 'notifications', 'read_at');
        if (!$hasNotifReadAt) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => true]);
            exit;
        }

        $mark = $pdo->prepare('UPDATE notifications
                               SET read_at = NOW()
                               WHERE read_at IS NULL
                                 AND (user_id = :user_id OR user_id IS NULL)');
        $mark->execute(['user_id' => (int) ($authUser['id'] ?? 0)]);

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'validate_and_diffuse') {
        require_auth($authUser);
        if (!is_lead_gtmp($authUser) && !is_admin($authUser)) {
            set_flash('error', 'Action reservee aux roles Lead/Admin.');
            header('Location: ?page=tableau_de_bord');
            exit;
        }

        $reportId = (int) ($_POST['report_id'] ?? 0);
        if ($reportId <= 0) {
            set_flash('error', 'Alerte invalide.');
            header('Location: ?page=tableau_de_bord');
            exit;
        }

        $validateStmt = $pdo->prepare('UPDATE reports
                                       SET is_validated = 1,
                                           validated_by = :validated_by,
                                           validated_at = NOW(),
                                           diffused_at = NOW()
                                       WHERE id = :id');
        $validateStmt->execute([
            'validated_by' => (int) ($authUser['id'] ?? 0),
            'id' => $reportId,
        ]);

        $diffusion = diffuserAlerte($reportId);
        if ((bool) ($diffusion['success'] ?? false)) {
            create_notification(
                $config,
                null,
                'Alerte diffusee',
                'Une alerte a ete validee et diffusee par le lead.',
                '?page=rapports_liste'
            );
            set_flash('success', 'Alerte diffusee. Emails envoyes: ' . (int) ($diffusion['sent'] ?? 0));
        } else {
            set_flash('error', 'Diffusion echouee: ' . (string) ($diffusion['error'] ?? 'Erreur inconnue.'));
        }

        header('Location: ?page=tableau_de_bord');
        exit;
    }

    if ($action === 'delete_org_report') {
        require_auth($authUser);

        $reportId = (int) ($_POST['report_id'] ?? 0);
        if ($reportId <= 0) {
            set_flash('error', 'Rapport invalide.');
            header('Location: ?page=rapportage-liste-user');
            exit;
        }

        $reportUserFk = reports_user_fk_column($config);
        if ($reportUserFk === null) {
            set_flash('error', 'Impossible de lier ce rapport à une organisation.');
            header('Location: ?page=rapportage-liste-user');
            exit;
        }

        $statusExpr = report_workflow_status_expr($config, 'r');
        $stmt = $pdo->prepare('SELECT r.id, ' . $statusExpr . ' AS workflow_status
                               FROM reports r
                               WHERE r.id = :id
                                 AND r.' . $reportUserFk . ' = :user_id
                               LIMIT 1');
        $stmt->execute([
            'id' => $reportId,
            'user_id' => (int) ($authUser['id'] ?? 0),
        ]);
        $report = $stmt->fetch();

        if (!is_array($report)) {
            set_flash('error', 'Rapport introuvable ou non autorisé.');
            header('Location: ?page=rapportage-liste-user');
            exit;
        }

        $status = (string) ($report['workflow_status'] ?? '');
        if (!is_report_mutable_status($status)) {
            set_flash('error', 'Suppression autorisée uniquement pour les statuts Brouillon ou Soumis.');
            header('Location: ?page=rapportage-liste-user');
            exit;
        }

        try {
            $pdo->beginTransaction();

            $attachmentsStmt = $pdo->prepare('SELECT storage_path FROM report_attachments WHERE report_id = :report_id');
            $attachmentsStmt->execute(['report_id' => $reportId]);
            $attachments = $attachmentsStmt->fetchAll();

            $deleteStmt = $pdo->prepare('DELETE FROM reports WHERE id = :id LIMIT 1');
            $deleteStmt->execute(['id' => $reportId]);

            $pdo->commit();

            foreach ($attachments as $attachment) {
                $storedPath = trim((string) ($attachment['storage_path'] ?? ''));
                if ($storedPath === '') {
                    continue;
                }
                $fullPath = __DIR__ . '/' . ltrim($storedPath, '/');
                if (is_file($fullPath)) {
                    @unlink($fullPath);
                }
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash('error', 'Suppression impossible pour le moment.');
            header('Location: ?page=rapportage-liste-user');
            exit;
        }

        set_flash('success', 'Rapport supprimé avec succès.');
        header('Location: ?page=rapportage-liste-user');
        exit;
    }

    if ($action === 'lead_report_decision') {
        require_auth($authUser);
        if (!is_lead_gtmp($authUser) && !is_admin($authUser)) {
            set_flash('error', 'Action réservée aux rôles Lead/Admin.');
            header('Location: ?page=rapportage-admin-list');
            exit;
        }

        $reportId = (int) ($_POST['report_id'] ?? 0);
        $decision = trim((string) ($_POST['decision'] ?? ''));
        $decisionReason = trim((string) ($_POST['decision_reason'] ?? ''));
        $decisionComment = trim((string) ($_POST['decision_comment'] ?? ''));
        $legacyMessage = trim((string) ($_POST['decision_message'] ?? ''));

        if ($decisionComment === '' && $legacyMessage !== '') {
            $decisionComment = $legacyMessage;
        }

        if ($reportId <= 0 || !in_array($decision, ['publish', 'request_info', 'reject'], true)) {
            set_flash('error', 'Décision invalide.');
            header('Location: ?page=rapportage-admin-list');
            exit;
        }

        $reportUserFk = reports_user_fk_column($config);
        $joinUserSql = $reportUserFk !== null
            ? 'LEFT JOIN users u ON u.id = r.' . $reportUserFk
            : '';
        $orgExpr = $reportUserFk !== null
            ? 'COALESCE(NULLIF(TRIM(u.organization_name), ""), u.full_name, "Organisation")'
            : '"Organisation"';
        $mailExpr = $reportUserFk !== null ? 'u.email' : 'NULL';

        $reportStmt = $pdo->prepare('SELECT r.id, r.title, ' . $orgExpr . ' AS organization_name, ' . $mailExpr . ' AS organization_email
                                     FROM reports r
                                     ' . $joinUserSql . '
                                     WHERE r.id = :id
                                     LIMIT 1');
        $reportStmt->execute(['id' => $reportId]);
        $report = $reportStmt->fetch();

        if (!is_array($report)) {
            set_flash('error', 'Rapport introuvable.');
            header('Location: ?page=rapportage-admin-list');
            exit;
        }

        if ($decision === 'request_info' && $decisionReason === '') {
            set_flash('error', 'Veuillez sélectionner une raison pour la demande d informations supplémentaires.');
            header('Location: ?page=rapportage-voir&id=' . urlencode((string) $reportId));
            exit;
        }

        if ($decision === 'reject' && $decisionReason === '') {
            set_flash('error', 'Veuillez sélectionner une raison pour le rejet.');
            header('Location: ?page=rapportage-voir&id=' . urlencode((string) $reportId));
            exit;
        }

        $statusLabel = 'Soumis';
        $historyNote = null;

        $decisionNoteParts = [];
        if ($decisionReason !== '') {
            $decisionNoteParts[] = 'Raison: ' . $decisionReason;
        }
        if ($decisionComment !== '') {
            $decisionNoteParts[] = 'Commentaire: ' . $decisionComment;
        }
        $decisionNote = trim(implode(' | ', $decisionNoteParts));

        if ($decision === 'publish') {
            $updateStmt = $pdo->prepare('UPDATE reports
                                         SET workflow_status = :workflow_status,
                                             is_validated = 1,
                                             validated_by = :validated_by,
                                             validated_at = NOW(),
                                             diffused_at = NOW(),
                                             published_at = NOW()
                                         WHERE id = :id');
            $updateStmt->execute([
                'workflow_status' => 'Publié',
                'validated_by' => (int) ($authUser['id'] ?? 0),
                'id' => $reportId,
            ]);
            $statusLabel = 'Publié';
            $historyNote = 'Rapport validé et publié par le Lead GTMP.';
        } elseif ($decision === 'request_info') {
            $updateStmt = $pdo->prepare('UPDATE reports
                                         SET workflow_status = :workflow_status,
                                             reviewed_at = NOW()
                                         WHERE id = :id');
            $updateStmt->execute([
                'workflow_status' => 'En révision',
                'id' => $reportId,
            ]);
            $statusLabel = 'En révision';
            $historyNote = $decisionNote !== '' ? $decisionNote : 'Informations supplémentaires demandées par le Lead GTMP.';
        } else {
            $updateStmt = $pdo->prepare('UPDATE reports
                                         SET workflow_status = :workflow_status,
                                             rejected_at = NOW()
                                         WHERE id = :id');
            $updateStmt->execute([
                'workflow_status' => 'Rejeté',
                'id' => $reportId,
            ]);
            $statusLabel = 'Rejeté';
            $historyNote = $decisionNote !== '' ? $decisionNote : 'Rapport rejeté par le Lead GTMP.';
        }

        $historyStmt = $pdo->prepare('INSERT INTO report_status_history (report_id, status_label, event_note, changed_by)
                                      VALUES (:report_id, :status_label, :event_note, :changed_by)');
        $historyStmt->execute([
            'report_id' => $reportId,
            'status_label' => $statusLabel,
            'event_note' => $historyNote,
            'changed_by' => (int) ($authUser['id'] ?? 0),
        ]);

        $orgEmail = strtolower(trim((string) ($report['organization_email'] ?? '')));
        if ($orgEmail !== '' && filter_var($orgEmail, FILTER_VALIDATE_EMAIL)) {
            $orgName = (string) ($report['organization_name'] ?? 'Organisation');
            $title = (string) ($report['title'] ?? ('Rapport #' . $reportId));
            $subject = 'Mise à jour de votre rapport SyDRA';
            $body = "Bonjour {$orgName},\n\n"
                . "Le statut de votre rapport \"{$title}\" est maintenant: {$statusLabel}.\n\n";

            if ($historyNote !== null && trim($historyNote) !== '') {
                $body .= "Message du Lead GTMP:\n{$historyNote}\n\n";
            }

            $body .= "Équipe SyDRA";
            sendAppMail($config, $orgEmail, $subject, $body);
        }

        set_flash('success', 'Décision enregistrée avec succès.');
        header('Location: ?page=rapportage-voir&id=' . urlencode((string) $reportId));
        exit;
    }

    if ($action === 'check_new_submitted_alert') {
        require_auth($authUser);
        header('Content-Type: application/json; charset=UTF-8');

        if (!is_lead_gtmp($authUser) && !is_admin($authUser)) {
            echo json_encode(['ok' => false, 'message' => 'Accès interdit.']);
            exit;
        }

        $lastSeenId = (int) ($_POST['last_seen_id'] ?? 0);
        $statusExpr = report_workflow_status_expr($config, 'r');
        $reportUserFk = reports_user_fk_column($config);
        $joinUserSql = $reportUserFk !== null
            ? 'LEFT JOIN users u ON u.id = r.' . $reportUserFk
            : '';
        $orgExpr = $reportUserFk !== null
            ? 'COALESCE(NULLIF(TRIM(u.organization_name), ""), u.full_name, "Organisation inconnue")'
            : '"Organisation inconnue"';

        $stmt = $pdo->query('SELECT r.id, ' . $orgExpr . ' AS organization_name, ' . $statusExpr . ' AS workflow_status
                     FROM reports r
                     ' . $joinUserSql . '
                     WHERE LOWER(REPLACE(REPLACE(REPLACE(' . $statusExpr . ', "é", "e"), "è", "e"), "ê", "e")) = "soumis"
                     ORDER BY r.id DESC
                     LIMIT 1');
        $latest = $stmt->fetch();

        if (!is_array($latest)) {
            echo json_encode(['ok' => true, 'has_new' => false, 'latest_id' => 0]);
            exit;
        }

        $latestId = (int) ($latest['id'] ?? 0);
        echo json_encode([
            'ok' => true,
            'has_new' => $latestId > $lastSeenId,
            'latest_id' => $latestId,
            'organization_name' => (string) ($latest['organization_name'] ?? 'Organisation inconnue'),
            'status' => (string) ($latest['workflow_status'] ?? ''),
        ]);
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

        $updateUser = $pdo->prepare('UPDATE users
                                     SET password_hash = :password_hash,
                                         is_active = 1,
                                         statut = "Actif",
                                         must_change_password = 0
                                     WHERE id = :id');
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

$publicPages = ['connexion', 'mot_de_passe_oublie', 'activation_compte', 'reinitialiser_mot_de_passe', 'confirmer_email'];

if ($page === 'connexion' && is_array($authUser)) {
    header('Location: ?page=tableau_de_bord');
    exit;
}

if (!in_array($page, $publicPages, true)) {
    require_auth($authUser);
}

if (is_array($authUser)
    && requires_profile_completion($authUser)
    && !in_array($page, ['profil', 'deconnexion'], true)
) {
    set_flash('error', 'Complétez le profil organisation (nom long, téléphone et bio) avant de continuer.');
    header('Location: ?page=profil&must_complete_profile=1');
    exit;
}

$pageMap = [
    'connexion' => ['file' => __DIR__ . '/pages/login.php', 'title' => 'Connexion'],
    'mot_de_passe_oublie' => ['file' => __DIR__ . '/pages/forgot_password.php', 'title' => 'Mot de passe oublie'],
    'tableau_de_bord' => ['file' => __DIR__ . '/pages/dashboard.php', 'title' => 'Tableau de bord'],
    'stats' => ['file' => __DIR__ . '/pages/stats.php', 'title' => 'Statistiques strategiques'],
    'rapportage' => ['file' => __DIR__ . '/pages/rapportage/index.php', 'title' => 'Rapportage'],
    'rapportage-liste-user' => ['file' => __DIR__ . '/pages/rapportage-liste-user.php', 'title' => 'Mes rapports'],
    'rapportage-admin-list' => ['file' => __DIR__ . '/pages/rapportage-admin-list.php', 'title' => 'Tour de controle GTMP'],
    'rapportage-voir' => ['file' => __DIR__ . '/pages/rapportage/details.php', 'title' => 'Detail du rapport'],
    'rapportage-creer-AI' => ['file' => __DIR__ . '/pages/rapportage/creer_ia.php', 'title' => 'Creation assistee IA'],
    'rapportage-creer-wizar' => ['file' => __DIR__ . '/pages/rapportage/creer_wizard.php', 'title' => 'Creation manuelle Wizard'],
    'rapport_creer' => ['file' => __DIR__ . '/pages/reports_create.php', 'title' => 'Nouveau rapport'],
    'rapports_liste' => ['file' => __DIR__ . '/pages/reports_list.php', 'title' => 'Rapports'],
    'profil' => ['file' => __DIR__ . '/pages/profile.php', 'title' => 'Profil'],
    'utilisateurs' => ['file' => __DIR__ . '/pages/users.php', 'title' => 'Utilisateurs'],
    'activation_compte' => ['file' => __DIR__ . '/pages/activate.php', 'title' => 'Activation de compte'],
    'reinitialiser_mot_de_passe' => ['file' => __DIR__ . '/pages/reset_password.php', 'title' => 'Reinitialiser le mot de passe'],
    'confirmer_email' => ['file' => __DIR__ . '/pages/confirm_email_change.php', 'title' => 'Confirmer le changement email'],
    'aide' => ['file' => __DIR__ . '/pages/aide.php', 'title' => 'Centre d\'aide'],
];

if (!isset($pageMap[$page])) {
    http_response_code(404);
    echo 'Page introuvable.';
    exit;
}

if ($page === 'utilisateurs' && !can_manage_users($authUser)) {
    http_response_code(403);
    echo 'Accès interdit.';
    exit;
}

if ($page === 'rapportage-admin-list' && !is_lead_gtmp($authUser) && !is_admin($authUser)) {
    http_response_code(403);
    echo 'Accès interdit.';
    exit;
}

$reports = [];
$users = [];
$activation = null;
$urgentAlerts = [];
$topNotifications = [];
$unreadNotificationsCount = 0;
$mapAlerts = [];
$orgReportTrend = ['labels' => [], 'flash' => [], 'note' => []];
$statsTopOrganizations = ['labels' => [], 'values' => []];
$statsGlobalTrend = ['labels' => [], 'totals' => [], 'flash' => [], 'note' => []];
$statsUrgencyDistribution = ['labels' => [], 'values' => []];
$emailChangeRequest = null;
$rapportageMapAlerts = [];
$rapportageOrganizations = [];
$rapportageStats = ['total' => 0, 'critiques' => 0, 'attente' => 0, 'valides' => 0];
$rapportageRecentProvinceAlerts = [];
$rapportageUserReports = [];
$rapportageAdminReports = [];
$rapportageView = null;
$rapportageAttachments = [];
$rapportageTimeline = [];
$rapportageLatestSubmitted = null;
$dashboardKpis = [];
$dashboardRecentActivities = [];
$dashboardRecentReports = [];
$dashboardMapAlerts = [];
$pdo = db($config);

if ($page === 'rapports_liste') {
    $reportUserFk = reports_user_fk_column($config);
    $hasReportTitle = has_table_column($config, 'reports', 'title');
    $hasReportType = has_table_column($config, 'reports', 'report_type');
    $hasReportContent = has_table_column($config, 'reports', 'content');
    $hasLocationText = has_table_column($config, 'reports', 'location_text');

    $titleExpr = $hasReportTitle
        ? 'r.title'
        : ($hasReportContent ? 'SUBSTRING(COALESCE(r.content, ""), 1, 120)' : 'CONCAT("Rapport #", r.id)');
    $reportTypeExpr = $hasReportType
        ? 'r.report_type'
        : '"FLASH"';
    $locationExpr = $hasLocationText
        ? 'r.location_text'
        : 'NULL';

    $authorExpr = $reportUserFk !== null ? 'u.full_name' : '"Utilisateur inconnu"';
    $joinUserSql = $reportUserFk !== null
        ? 'LEFT JOIN users u ON u.id = r.' . $reportUserFk
        : '';

    $stmt = $pdo->query('SELECT r.id, '
        . $titleExpr . ' AS title, '
        . $reportTypeExpr . ' AS report_type, '
        . $locationExpr . ' AS location_text, '
        . 'r.created_at, ' . $authorExpr . ' AS full_name
          FROM reports r
          ' . $joinUserSql . '
          ORDER BY r.created_at DESC
          LIMIT 300');
    $reports = $stmt->fetchAll();
}

if ($page === 'rapportage' || $page === 'rapportage-liste-user' || $page === 'rapportage-admin-list' || $page === 'rapportage-voir') {
    $reportUserFk = reports_user_fk_column($config);
    $hasReportType = has_table_column($config, 'reports', 'report_type');
    $hasUrgencyLevel = has_table_column($config, 'reports', 'urgency_level');
    $hasLocationText = has_table_column($config, 'reports', 'location_text');
    $hasTerritory = has_table_column($config, 'reports', 'territory');
    $hasLocality = has_table_column($config, 'reports', 'locality');
    $hasVillage = has_table_column($config, 'reports', 'village');
    $hasTitle = has_table_column($config, 'reports', 'title');
    $hasContent = has_table_column($config, 'reports', 'content');
    $hasProvince = has_table_column($config, 'reports', 'province');
    $hasIncidentLabel = has_table_column($config, 'reports', 'incident_label');
    $hasGpsLat = has_table_column($config, 'reports', 'gps_lat');
    $hasGpsLng = has_table_column($config, 'reports', 'gps_lng');
    $hasLatitude = has_table_column($config, 'reports', 'latitude');
    $hasLongitude = has_table_column($config, 'reports', 'longitude');
    $hasVictimsCount = has_table_column($config, 'reports', 'victims_count');
    $hasDisplacedHouseholds = has_table_column($config, 'reports', 'displaced_households');
    $hasAnalysis = has_table_column($config, 'reports', 'analysis_text');
    $hasAdditionalNotes = has_table_column($config, 'reports', 'additional_notes');

    $typeExpr = $hasReportType ? 'r.report_type' : '"FLASH"';
    $urgencyExpr = $hasUrgencyLevel ? 'r.urgency_level' : '"Moyenne"';
    $locationCandidates = [];
    if ($hasLocationText) {
        $locationCandidates[] = 'NULLIF(TRIM(r.location_text), "")';
    }
    if ($hasProvince) {
        $locationCandidates[] = 'NULLIF(TRIM(r.province), "")';
    }
    if ($hasTerritory) {
        $locationCandidates[] = 'NULLIF(TRIM(r.territory), "")';
    }
    if ($hasLocality) {
        $locationCandidates[] = 'NULLIF(TRIM(r.locality), "")';
    }
    if ($hasVillage) {
        $locationCandidates[] = 'NULLIF(TRIM(r.village), "")';
    }
    $locationExpr = $locationCandidates !== []
        ? ('COALESCE(' . implode(', ', $locationCandidates) . ', "Non précisée")')
        : '"Non précisée"';
    $provinceExpr = $hasProvince ? 'NULLIF(TRIM(r.province), "")' : 'NULL';
    $incidentExpr = $hasIncidentLabel
        ? 'r.incident_label'
        : ($hasTitle ? 'r.title' : ($hasContent ? 'SUBSTRING(COALESCE(r.content, ""), 1, 120)' : 'CONCAT("Rapport #", r.id)'));
    $titleExpr = $hasTitle
        ? 'r.title'
        : ($hasContent ? 'SUBSTRING(COALESCE(r.content, ""), 1, 120)' : 'CONCAT("Rapport #", r.id)');
    $contentExpr = $hasContent ? 'r.content' : '""';
    $statusExpr = report_workflow_status_expr($config, 'r');
    $statusNormalizedExpr = 'LOWER(REPLACE(REPLACE(REPLACE(COALESCE(' . $statusExpr . ', ""), "é", "e"), "è", "e"), "ê", "e"))';
    $gpsLatExpr = $hasGpsLat
        ? ($hasLatitude ? 'COALESCE(r.gps_lat, r.latitude)' : 'r.gps_lat')
        : ($hasLatitude ? 'r.latitude' : 'NULL');
    $gpsLngExpr = $hasGpsLng
        ? ($hasLongitude ? 'COALESCE(r.gps_lng, r.longitude)' : 'r.gps_lng')
        : ($hasLongitude ? 'r.longitude' : 'NULL');
    $victimsExpr = $hasVictimsCount ? 'r.victims_count' : 'NULL';
    $displacedExpr = $hasDisplacedHouseholds ? 'r.displaced_households' : 'NULL';
    $analysisExpr = $hasAnalysis ? 'r.analysis_text' : 'NULL';
    $notesExpr = $hasAdditionalNotes ? 'r.additional_notes' : 'NULL';

    $joinUserSql = $reportUserFk !== null
        ? 'LEFT JOIN users u ON u.id = r.' . $reportUserFk
        : '';
    $orgExpr = $reportUserFk !== null
        ? 'COALESCE(NULLIF(TRIM(u.organization_name), ""), u.full_name, "Organisation inconnue")'
        : '"Organisation inconnue"';
    $orgEmailExpr = $reportUserFk !== null ? 'COALESCE(u.email, "")' : '""';
    $orgSiteExpr = $reportUserFk !== null ? 'COALESCE(u.site_web, "")' : '""';
    $orgLogoExpr = $reportUserFk !== null ? 'COALESCE(NULLIF(TRIM(u.logo_path), ""), u.avatar_path, "")' : '""';
    $userExpr = $reportUserFk !== null ? 'r.' . $reportUserFk : 'NULL';

    if ($page === 'rapportage') {
        $hasUserOrgId = has_table_column($config, 'users', 'organization_id');
        $hasReportOrgId = has_table_column($config, 'reports', 'organization_id');

        $rawDateDebut = trim((string) ($_GET['date_debut'] ?? ''));
        $rawDateFin = trim((string) ($_GET['date_fin'] ?? ''));
        $rawOrgId = trim((string) ($_GET['organisation_id'] ?? ''));

        $dateDebut = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDateDebut) === 1 ? $rawDateDebut : null;
        $dateFin = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDateFin) === 1 ? $rawDateFin : null;
        $filterOrgId = ($rawOrgId !== '' && ctype_digit($rawOrgId)) ? (int) $rawOrgId : null;

        // Détection robuste du rôle (schéma users.role ou users.role_id)
        $userRole = strtoupper((string) ($authUser['role'] ?? $authUser['role_code'] ?? ''));
        if ($userRole === '' && isset($authUser['id'])) {
            $roleId = (int) ($authUser['role_id'] ?? 0);
            if ($roleId > 0) {
                $roleLookup = $pdo->prepare('SELECT COALESCE(code, "") FROM roles WHERE id = :id LIMIT 1');
                $roleLookup->execute(['id' => $roleId]);
                $userRole = strtoupper((string) $roleLookup->fetchColumn());
            }
        }
        $isLeadOrAdmin = in_array($userRole, ['ADMIN', 'CLUSTER_LEADER', 'GTMP_LEAD', 'GTMP_COLEAD', 'CLUSTER_PROTECTION', 'LEAD_GTMP'], true);

        $commonConditions = [];
        $commonParams = [];
        if ($dateDebut !== null) {
            $commonConditions[] = 'r.created_at >= :date_debut';
            $commonParams['date_debut'] = $dateDebut . ' 00:00:00';
        }
        if ($dateFin !== null) {
            $commonConditions[] = 'r.created_at <= :date_fin';
            $commonParams['date_fin'] = $dateFin . ' 23:59:59';
        }

        if ($isLeadOrAdmin && $filterOrgId !== null) {
            if ($reportUserFk !== null && $hasUserOrgId) {
                $commonConditions[] = 'u.organization_id = :org_id';
                $commonParams['org_id'] = $filterOrgId;
            } elseif ($hasReportOrgId) {
                $commonConditions[] = 'r.organization_id = :org_id';
                $commonParams['org_id'] = $filterOrgId;
            }
        }

        if (!$isLeadOrAdmin && $reportUserFk !== null) {
            $commonConditions[] = 'r.' . $reportUserFk . ' = :reporter_uid';
            $commonParams['reporter_uid'] = (int) ($authUser['id'] ?? 0);
        }

        $commonWhere = $commonConditions !== [] ? (' WHERE ' . implode(' AND ', $commonConditions)) : '';

        $mapSql = 'SELECT r.id,
                          ' . $typeExpr . ' AS report_type,
                          ' . $urgencyExpr . ' AS urgency_level,
                          ' . $locationExpr . ' AS location_text,
                          ' . $provinceExpr . ' AS province,
                          ' . $gpsLatExpr . ' AS gps_lat,
                          ' . $gpsLngExpr . ' AS gps_lng,
                          ' . $orgExpr . ' AS organization_name,
                          ' . $statusExpr . ' AS workflow_status,
                          r.created_at
                   FROM reports r
                   ' . $joinUserSql
                   . $commonWhere . '
                   ORDER BY r.created_at DESC
                   LIMIT 200';
        $mapStmt = $pdo->prepare($mapSql);
        $mapStmt->execute($commonParams);
        $rapportageMapAlerts = $mapStmt->fetchAll();

        $statsStmt = $pdo->prepare('
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN LOWER(COALESCE(r.urgency_level, "")) LIKE "%crit%" THEN 1 ELSE 0 END) AS critiques,
                SUM(CASE WHEN LOWER(COALESCE(r.workflow_status, "")) IN ("soumis", "submitted", "en revue", "under_review") THEN 1 ELSE 0 END) AS attente,
                SUM(CASE WHEN LOWER(COALESCE(r.workflow_status, "")) IN ("approuve", "approved", "publie") THEN 1 ELSE 0 END) AS valides
            FROM reports r
            ' . $joinUserSql
            . $commonWhere);
        $statsStmt->execute($commonParams);
        $rawStats = $statsStmt->fetch();
        if (is_array($rawStats)) {
            $rapportageStats = [
                'total'     => (int) ($rawStats['total'] ?? 0),
                'critiques' => (int) ($rawStats['critiques'] ?? 0),
                'attente'   => (int) ($rawStats['attente'] ?? 0),
                'valides'   => (int) ($rawStats['valides'] ?? 0),
            ];
        }

        // Liste organisations pour les rôles lead/admin
        if ($isLeadOrAdmin) {
            $orgStmt = $pdo->query('SELECT id, name FROM organizations WHERE is_active = 1 ORDER BY name ASC');
            $rapportageOrganizations = $orgStmt->fetchAll();
        }

        $currentProvince = trim((string) ($_GET['province'] ?? ''));
        if ($currentProvince === '' && $reportUserFk !== null && is_array($authUser)) {
            $provinceLookup = $pdo->prepare('SELECT ' . $provinceExpr . ' AS province_name
                                             FROM reports r
                                             WHERE r.' . $reportUserFk . ' = :user_id
                                               AND ' . $provinceExpr . ' IS NOT NULL
                                               AND TRIM(' . $provinceExpr . ') <> ""
                                             ORDER BY r.created_at DESC
                                             LIMIT 1');
            $provinceLookup->execute(['user_id' => (int) ($authUser['id'] ?? 0)]);
            $currentProvince = trim((string) $provinceLookup->fetchColumn());
        }

        if ($currentProvince !== '') {
            $recentStmt = $pdo->prepare('SELECT r.id,
                                                ' . $incidentExpr . ' AS incident_label,
                                                ' . $typeExpr . ' AS report_type,
                                                ' . $statusExpr . ' AS workflow_status,
                                                ' . $provinceExpr . ' AS province,
                                                r.created_at
                                         FROM reports r
                                         WHERE ' . $provinceExpr . ' = :province
                                         ORDER BY r.created_at DESC
                                         LIMIT 5');
            $recentStmt->execute(['province' => $currentProvince]);
            $rapportageRecentProvinceAlerts = $recentStmt->fetchAll();
        } else {
            $recentStmt = $pdo->query('SELECT r.id,
                                              ' . $incidentExpr . ' AS incident_label,
                                              ' . $typeExpr . ' AS report_type,
                                              ' . $statusExpr . ' AS workflow_status,
                                              ' . $provinceExpr . ' AS province,
                                              r.created_at
                                       FROM reports r
                                       ORDER BY r.created_at DESC
                                       LIMIT 5');
            $rapportageRecentProvinceAlerts = $recentStmt->fetchAll();
        }
    }

    if ($page === 'rapportage-liste-user' && is_array($authUser)) {
        $isLeadOrAdminList = is_lead_gtmp($authUser) || is_admin($authUser);
        $authUserIdForList = (int) ($authUser['id'] ?? 0);
        $ownerExprList = $reportUserFk !== null ? 'r.' . $reportUserFk : 'NULL';
        $orgExprList = $reportUserFk !== null
            ? 'COALESCE(NULLIF(TRIM(u.organization_name), ""), u.full_name, "Organisation")'
            : '"Organisation"';
        $joinUserSqlList = $reportUserFk !== null
            ? 'LEFT JOIN users u ON u.id = r.' . $reportUserFk
            : '';
        $statusNormExprList = 'LOWER(REPLACE(REPLACE(REPLACE(' . $statusExpr . ', "é", "e"), "è", "e"), "ê", "e"))';

        if ($isLeadOrAdminList) {
            $userStmt = $pdo->prepare('SELECT r.id,
                                              r.created_at,
                                              ' . $typeExpr . ' AS report_type,
                                              ' . $locationExpr . ' AS location_text,
                                              ' . $incidentExpr . ' AS incident_label,
                                              ' . $statusExpr . ' AS workflow_status,
                                              ' . $urgencyExpr . ' AS urgency_level,
                                              ' . $ownerExprList . ' AS owner_user_id,
                                              ' . $orgExprList . ' AS organization_name
                                       FROM reports r
                                       ' . $joinUserSqlList . '
                                       WHERE (' . $statusNormalizedExpr . ' <> "brouillon" OR ' . $ownerExprList . ' = :current_user_id)
                                       ORDER BY r.created_at DESC
                                       LIMIT 800');
            $userStmt->execute(['current_user_id' => (int) ($authUser['id'] ?? 0)]);
            $rapportageUserReports = $userStmt->fetchAll();
        } elseif ($reportUserFk !== null) {
            $userStmt = $pdo->prepare('SELECT r.id,
                                              r.created_at,
                                              ' . $typeExpr . ' AS report_type,
                                              ' . $locationExpr . ' AS location_text,
                                              ' . $incidentExpr . ' AS incident_label,
                                              ' . $statusExpr . ' AS workflow_status,
                                              ' . $urgencyExpr . ' AS urgency_level,
                                              ' . $ownerExprList . ' AS owner_user_id,
                                              ' . $orgExprList . ' AS organization_name
                                       FROM reports r
                                       ' . $joinUserSqlList . '
                                       WHERE r.' . $reportUserFk . ' = :user_id
                                       ORDER BY r.created_at DESC
                                       LIMIT 500');
            $userStmt->execute(['user_id' => (int) ($authUser['id'] ?? 0)]);
            $rapportageUserReports = $userStmt->fetchAll();
        }
    }

    if ($page === 'rapportage-admin-list') {
        $adminStmt = $pdo->prepare('SELECT r.id,
                                           r.created_at,
                                           ' . $typeExpr . ' AS report_type,
                                           ' . $locationExpr . ' AS location_text,
                                           ' . $incidentExpr . ' AS incident_label,
                                           ' . $statusExpr . ' AS workflow_status,
                                           ' . $urgencyExpr . ' AS urgency_level,
                                           ' . $orgExpr . ' AS organization_name
                                    FROM reports r
                                    ' . $joinUserSql . '
                                    WHERE (' . $statusNormalizedExpr . ' <> "brouillon" OR ' . $userExpr . ' = :current_user_id)
                                    ORDER BY r.created_at DESC
                                    LIMIT 800');
        $adminStmt->execute(['current_user_id' => (int) ($authUser['id'] ?? 0)]);
        $rapportageAdminReports = $adminStmt->fetchAll();

        $latestSubmittedStmt = $pdo->query('SELECT r.id
                                            FROM reports r
                                            WHERE LOWER(REPLACE(REPLACE(REPLACE(' . $statusExpr . ', "é", "e"), "è", "e"), "ê", "e")) = "soumis"
                                            ORDER BY r.id DESC
                                            LIMIT 1');
        $latestSubmittedId = $latestSubmittedStmt->fetchColumn();
        $rapportageLatestSubmitted = $latestSubmittedId !== false ? (int) $latestSubmittedId : null;
    }

    if ($page === 'rapportage-voir') {
        $reportId = (int) ($_GET['id'] ?? 0);
        if ($reportId <= 0) {
            http_response_code(400);
            echo 'Rapport invalide.';
            exit;
        }

        $detailStmt = $pdo->prepare('SELECT r.id,
                                            ' . $titleExpr . ' AS title,
                                            ' . $incidentExpr . ' AS incident_label,
                                            ' . $typeExpr . ' AS report_type,
                                            ' . $locationExpr . ' AS location_text,
                                            ' . $provinceExpr . ' AS province,
                                            ' . $statusExpr . ' AS workflow_status,
                                            ' . $urgencyExpr . ' AS urgency_level,
                                            ' . $contentExpr . ' AS content,
                                            ' . $gpsLatExpr . ' AS gps_lat,
                                            ' . $gpsLngExpr . ' AS gps_lng,
                                            ' . $victimsExpr . ' AS victims_count,
                                            ' . $displacedExpr . ' AS displaced_households,
                                            ' . $analysisExpr . ' AS analysis_text,
                                            ' . $notesExpr . ' AS additional_notes,
                                            r.created_at,
                                            r.submitted_at,
                                            r.reviewed_at,
                                            r.validated_at,
                                            r.published_at,
                                            r.rejected_at,
                                            ' . $orgExpr . ' AS organization_name,
                                              ' . $orgEmailExpr . ' AS organization_email,
                                              ' . $orgSiteExpr . ' AS organization_site_web,
                                              ' . $orgLogoExpr . ' AS organization_logo_path,
                                            ' . $userExpr . ' AS owner_user_id
                                     FROM reports r
                                     ' . $joinUserSql . '
                                     WHERE r.id = :id
                                     LIMIT 1');
        $detailStmt->execute(['id' => $reportId]);
        $rapportageView = $detailStmt->fetch();

        if (!is_array($rapportageView)) {
            http_response_code(404);
            echo 'Rapport introuvable.';
            exit;
        }

        $ownerId = (int) ($rapportageView['owner_user_id'] ?? 0);
        $isDecisionRole = is_lead_gtmp($authUser) || is_admin($authUser);
        if (!$isDecisionRole && is_array($authUser) && $ownerId > 0 && $ownerId !== (int) ($authUser['id'] ?? 0)) {
            http_response_code(403);
            echo 'Accès interdit.';
            exit;
        }

        $statusRaw = (string) ($rapportageView['workflow_status'] ?? '');
        $statusNormalized = strtolower(trim(str_replace(['é', 'è', 'ê'], 'e', $statusRaw)));
        if ($isDecisionRole && $statusNormalized === 'brouillon' && $ownerId > 0 && $ownerId !== (int) ($authUser['id'] ?? 0)) {
            http_response_code(403);
            echo 'Accès interdit.';
            exit;
        }

        $attachmentsStmt = $pdo->prepare('SELECT id, original_name, storage_path, mime_type, file_size, created_at
                                          FROM report_attachments
                                          WHERE report_id = :report_id
                                          ORDER BY id DESC');
        $attachmentsStmt->execute(['report_id' => $reportId]);
        $rapportageAttachments = $attachmentsStmt->fetchAll();

        $timelineStmt = $pdo->prepare('SELECT h.id, h.status_label, h.event_note, h.created_at,
                                              COALESCE(NULLIF(TRIM(u.organization_name), ""), u.full_name, "Système") AS actor_name
                                       FROM report_status_history h
                                       LEFT JOIN users u ON u.id = h.changed_by
                                       WHERE h.report_id = :report_id
                                       ORDER BY h.created_at ASC, h.id ASC');
        $timelineStmt->execute(['report_id' => $reportId]);
        $rapportageTimeline = $timelineStmt->fetchAll();

        if ($rapportageTimeline === []) {
            $syntheticTimeline = [];
            $createdAt = (string) ($rapportageView['created_at'] ?? '');
            if ($createdAt !== '') {
                $syntheticTimeline[] = [
                    'status_label' => 'Soumis',
                    'event_note' => 'Rapport enregistré dans SyDRA.',
                    'created_at' => $createdAt,
                    'actor_name' => (string) ($rapportageView['organization_name'] ?? 'Organisation'),
                ];
            }

            $publishedAt = (string) ($rapportageView['published_at'] ?? '');
            if ($publishedAt !== '') {
                $syntheticTimeline[] = [
                    'status_label' => 'Publié',
                    'event_note' => 'Rapport validé et publié.',
                    'created_at' => $publishedAt,
                    'actor_name' => 'Lead GTMP',
                ];
            }

            $rapportageTimeline = $syntheticTimeline;
        }
    }
}

if ($page === 'utilisateurs') {
    $reportUserFk = reports_user_fk_column($config);

    if (role_storage_mode($config) === 'role_fk') {
        $stmt = $pdo->query('SELECT u.id, u.full_name, u.email, u.organization_name, COALESCE(r.code, "REPORTER") AS role,
                                    u.avatar_path, u.logo_path, u.bio, u.bio_organisation, u.phone, u.telephone_organisation,
                                                                        u.site_web, u.is_active, u.statut, u.last_login_at, u.created_at,
                                                                        EXISTS(
                                                                                SELECT 1
                                                                                FROM account_invitations ai
                                                                                WHERE ai.user_id = u.id
                                                                                    AND ai.used_at IS NULL
                                                                                    AND ai.expires_at > NOW()
                                                                        ) AS pending_mail_validation
                             FROM users u
                             LEFT JOIN roles r ON r.id = u.role_id
                             ORDER BY u.id DESC
                             LIMIT 300');
    } else {
        $stmt = $pdo->query('SELECT id, full_name, email, organization_name, role,
                                    avatar_path, logo_path, bio, bio_organisation, phone, telephone_organisation, site_web,
                                                                        is_active, statut, last_login_at, created_at,
                                                                        EXISTS(
                                                                                SELECT 1
                                                                                FROM account_invitations ai
                                                                                WHERE ai.user_id = users.id
                                                                                    AND ai.used_at IS NULL
                                                                                    AND ai.expires_at > NOW()
                                                                        ) AS pending_mail_validation
                             FROM users
                             ORDER BY id DESC
                             LIMIT 300');
    }
    $users = $stmt->fetchAll();

    $monthlyByUser = [];
    if ($reportUserFk !== null) {
        $monthlyStmt = $pdo->query('SELECT r.' . $reportUserFk . ' AS user_id, COUNT(*) AS total
                                    FROM reports r
                                    WHERE DATE_FORMAT(r.created_at, "%Y-%m") = DATE_FORMAT(CURRENT_DATE, "%Y-%m")
                                    GROUP BY r.' . $reportUserFk);
        foreach ($monthlyStmt->fetchAll() as $monthlyRow) {
            $monthlyByUser[(int) ($monthlyRow['user_id'] ?? 0)] = (int) ($monthlyRow['total'] ?? 0);
        }
    }

    foreach ($users as &$userRow) {
        $uid = (int) ($userRow['id'] ?? 0);
        $userRow['monthly_reports'] = $monthlyByUser[$uid] ?? 0;
    }
    unset($userRow);
}

if ($page === 'tableau_de_bord' || $page === 'stats') {
    $reportUserFk = reports_user_fk_column($config);
    $hasReportType = has_table_column($config, 'reports', 'report_type');
    $hasUrgencyLevel = has_table_column($config, 'reports', 'urgency_level');
    $hasLocationText = has_table_column($config, 'reports', 'location_text');

    $typeExpr = $hasReportType ? 'r.report_type' : '"FLASH"';
    $urgencyExpr = $hasUrgencyLevel ? 'r.urgency_level' : '"Moyenne"';
    $locationExpr = $hasLocationText ? 'r.location_text' : 'NULL';
    $orgExpr = $reportUserFk !== null
        ? 'COALESCE(NULLIF(TRIM(u.organization_name), ""), u.full_name, "Organisation inconnue")'
        : '"Organisation inconnue"';
    $joinUserSql = $reportUserFk !== null
        ? 'LEFT JOIN users u ON u.id = r.' . $reportUserFk
        : '';

    $mapStmt = $pdo->query('SELECT r.id, '
        . $typeExpr . ' AS report_type, '
        . $urgencyExpr . ' AS urgency_level, '
        . $locationExpr . ' AS location_text, '
        . $orgExpr . ' AS organization_name, '
        . 'r.created_at
        FROM reports r
        ' . $joinUserSql . '
        ORDER BY r.created_at DESC
        LIMIT 200');
    $mapAlerts = $mapStmt->fetchAll();

    if (!is_lead_gtmp($authUser) && !is_admin($authUser)) {
        $monthLabels = [];
        $monthMap = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthKey = date('Y-m', strtotime('-' . $i . ' month'));
            $monthMap[$monthKey] = [
                'flash' => 0,
                'note' => 0,
            ];
            $monthLabels[] = date('m/Y', strtotime($monthKey . '-01'));
        }

        if ($reportUserFk !== null && is_array($authUser)) {
            $trendStmt = $pdo->prepare('SELECT DATE_FORMAT(r.created_at, "%Y-%m") AS ym, '
                . $typeExpr . ' AS report_type, COUNT(*) AS total
                FROM reports r
                WHERE r.' . $reportUserFk . ' = :user_id
                  AND r.created_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), "%Y-%m-01")
                GROUP BY ym, report_type
                ORDER BY ym ASC');
            $trendStmt->execute(['user_id' => (int) ($authUser['id'] ?? 0)]);

            foreach ($trendStmt->fetchAll() as $trendRow) {
                $ym = (string) ($trendRow['ym'] ?? '');
                if (!isset($monthMap[$ym])) {
                    continue;
                }

                $reportType = strtoupper((string) ($trendRow['report_type'] ?? 'FLASH'));
                $bucket = $reportType === 'NOTE' ? 'note' : 'flash';
                $monthMap[$ym][$bucket] = (int) ($trendRow['total'] ?? 0);
            }
        }

        $flashValues = [];
        $noteValues = [];
        foreach ($monthMap as $seriesRow) {
            $flashValues[] = (int) ($seriesRow['flash'] ?? 0);
            $noteValues[] = (int) ($seriesRow['note'] ?? 0);
        }

        $orgReportTrend = [
            'labels' => $monthLabels,
            'flash' => $flashValues,
            'note' => $noteValues,
        ];
    }
}

if ($page === 'stats' && (is_lead_gtmp($authUser) || is_admin($authUser))) {
    $reportUserFk = reports_user_fk_column($config);
    $hasReportType = has_table_column($config, 'reports', 'report_type');
    $hasUrgencyLevel = has_table_column($config, 'reports', 'urgency_level');

    $typeExpr = $hasReportType ? 'r.report_type' : '"FLASH"';
    $urgencyExpr = $hasUrgencyLevel ? 'r.urgency_level' : '"Moyenne"';

    $topLabels = [];
    $topValues = [];
    if ($reportUserFk !== null) {
        $topOrgExpr = 'COALESCE(NULLIF(TRIM(u.organization_name), ""), u.full_name, "Organisation inconnue")';
        $topStmt = $pdo->query('SELECT ' . $topOrgExpr . ' AS organization_name,
                                       COUNT(*) AS total
                                FROM reports r
                                LEFT JOIN users u ON u.id = r.' . $reportUserFk . '
                                GROUP BY ' . $topOrgExpr . '
                                ORDER BY total DESC
                                LIMIT 8');
        foreach ($topStmt->fetchAll() as $topRow) {
            $topLabels[] = (string) ($topRow['organization_name'] ?? 'Organisation inconnue');
            $topValues[] = (int) ($topRow['total'] ?? 0);
        }
    }
    $statsTopOrganizations = [
        'labels' => $topLabels,
        'values' => $topValues,
    ];

    $monthLabels = [];
    $monthMap = [];
    for ($i = 5; $i >= 0; $i--) {
        $monthKey = date('Y-m', strtotime('-' . $i . ' month'));
        $monthMap[$monthKey] = [
            'total' => 0,
            'flash' => 0,
            'note' => 0,
        ];
        $monthLabels[] = date('m/Y', strtotime($monthKey . '-01'));
    }

    $globalStmt = $pdo->query('SELECT DATE_FORMAT(r.created_at, "%Y-%m") AS ym, '
        . $typeExpr . ' AS report_type, COUNT(*) AS total
        FROM reports r
        WHERE r.created_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), "%Y-%m-01")
        GROUP BY ym, report_type
        ORDER BY ym ASC');

    foreach ($globalStmt->fetchAll() as $globalRow) {
        $ym = (string) ($globalRow['ym'] ?? '');
        if (!isset($monthMap[$ym])) {
            continue;
        }

        $total = (int) ($globalRow['total'] ?? 0);
        $monthMap[$ym]['total'] += $total;

        $reportType = strtoupper((string) ($globalRow['report_type'] ?? 'FLASH'));
        if ($reportType === 'NOTE') {
            $monthMap[$ym]['note'] += $total;
        } else {
            $monthMap[$ym]['flash'] += $total;
        }
    }

    $totalSeries = [];
    $flashSeries = [];
    $noteSeries = [];
    foreach ($monthMap as $monthly) {
        $totalSeries[] = (int) ($monthly['total'] ?? 0);
        $flashSeries[] = (int) ($monthly['flash'] ?? 0);
        $noteSeries[] = (int) ($monthly['note'] ?? 0);
    }
    $statsGlobalTrend = [
        'labels' => $monthLabels,
        'totals' => $totalSeries,
        'flash' => $flashSeries,
        'note' => $noteSeries,
    ];

    $urgencyBuckets = [
        'Faible' => 0,
        'Moyenne' => 0,
        'Elevee' => 0,
        'Critique' => 0,
    ];
    $urgencyStmt = $pdo->query('SELECT ' . $urgencyExpr . ' AS urgency_level, COUNT(*) AS total
                                FROM reports r
                                GROUP BY urgency_level');
    foreach ($urgencyStmt->fetchAll() as $urgencyRow) {
        $level = (string) ($urgencyRow['urgency_level'] ?? 'Moyenne');
        if (!isset($urgencyBuckets[$level])) {
            $urgencyBuckets[$level] = 0;
        }
        $urgencyBuckets[$level] += (int) ($urgencyRow['total'] ?? 0);
    }
    $statsUrgencyDistribution = [
        'labels' => array_keys($urgencyBuckets),
        'values' => array_values($urgencyBuckets),
    ];
}

if ($page === 'tableau_de_bord' && (is_lead_gtmp($authUser) || is_admin($authUser))) {
    $reportUserFk = reports_user_fk_column($config);
    $hasReportTitle = has_table_column($config, 'reports', 'title');
    $hasReportType = has_table_column($config, 'reports', 'report_type');
    $hasUrgencyLevel = has_table_column($config, 'reports', 'urgency_level');
    $hasIsValidated = has_table_column($config, 'reports', 'is_validated');
    $hasReportContent = has_table_column($config, 'reports', 'content');
    $hasLocationText = has_table_column($config, 'reports', 'location_text');

    $titleExpr = $hasReportTitle
        ? 'r.title'
        : ($hasReportContent ? 'SUBSTRING(COALESCE(r.content, ""), 1, 120)' : 'CONCAT("Rapport #", r.id)');
    $reportTypeExpr = $hasReportType
        ? 'r.report_type'
        : '"FLASH"';
    $urgencyExpr = $hasUrgencyLevel
        ? 'r.urgency_level'
        : '"Moyenne"';
    $locationExpr = $hasLocationText
        ? 'r.location_text'
        : 'NULL';

    $conditions = [];
    if ($hasReportType) {
        $conditions[] = "r.report_type = 'FLASH'";
    }
    if ($hasIsValidated) {
        $conditions[] = 'r.is_validated = 0';
    }
    $whereSql = $conditions === [] ? '1=1' : implode(' AND ', $conditions);

    $submittedByExpr = $reportUserFk !== null ? 'u.full_name' : '"Utilisateur inconnu"';
    $joinUserSql = $reportUserFk !== null
        ? 'LEFT JOIN users u ON u.id = r.' . $reportUserFk
        : '';

    $stmt = $pdo->query('SELECT r.id, '
        . $titleExpr . ' AS title, '
        . $reportTypeExpr . ' AS report_type, '
                . $locationExpr . ' AS location_text, '
        . $urgencyExpr . ' AS urgency_level, '
        . 'r.created_at, ' . $submittedByExpr . ' AS submitted_by
          FROM reports r
          ' . $joinUserSql . '
          WHERE ' . $whereSql . '
          ORDER BY r.created_at DESC
          LIMIT 40');
    $urgentAlerts = $stmt->fetchAll();
}

if ($page === 'tableau_de_bord' && is_array($authUser)) {
    $reportUserFk = reports_user_fk_column($config);
    $hasUrgencyLevel = has_table_column($config, 'reports', 'urgency_level');
    $hasReportType = has_table_column($config, 'reports', 'report_type');
    $hasLocationText = has_table_column($config, 'reports', 'location_text');
    $hasProvince = has_table_column($config, 'reports', 'province');
    $hasTerritory = has_table_column($config, 'reports', 'territory');
    $hasLocality = has_table_column($config, 'reports', 'locality');
    $hasVillage = has_table_column($config, 'reports', 'village');
    $hasGpsLat = has_table_column($config, 'reports', 'gps_lat');
    $hasGpsLng = has_table_column($config, 'reports', 'gps_lng');
    $hasLatitude = has_table_column($config, 'reports', 'latitude');
    $hasLongitude = has_table_column($config, 'reports', 'longitude');
    $hasSeverityId = has_table_column($config, 'reports', 'severity_id');
    $urgencyExpr = $hasUrgencyLevel ? 'r.urgency_level' : '"Moyenne"';
    $typeExpr = $hasReportType ? 'r.report_type' : '"FLASH"';
    $locationCandidates = [];
    if ($hasLocationText) {
        $locationCandidates[] = 'NULLIF(TRIM(r.location_text), "")';
    }
    if ($hasProvince) {
        $locationCandidates[] = 'NULLIF(TRIM(r.province), "")';
    }
    if ($hasTerritory) {
        $locationCandidates[] = 'NULLIF(TRIM(r.territory), "")';
    }
    if ($hasLocality) {
        $locationCandidates[] = 'NULLIF(TRIM(r.locality), "")';
    }
    if ($hasVillage) {
        $locationCandidates[] = 'NULLIF(TRIM(r.village), "")';
    }
    $locationExpr = $locationCandidates !== []
        ? ('COALESCE(' . implode(', ', $locationCandidates) . ', "Non précisée")')
        : '"Non précisée"';
    $gpsLatExpr = $hasGpsLat
        ? ($hasLatitude ? 'COALESCE(r.gps_lat, r.latitude)' : 'r.gps_lat')
        : ($hasLatitude ? 'r.latitude' : 'NULL');
    $gpsLngExpr = $hasGpsLng
        ? ($hasLongitude ? 'COALESCE(r.gps_lng, r.longitude)' : 'r.gps_lng')
        : ($hasLongitude ? 'r.longitude' : 'NULL');
    $severityExpr = $hasSeverityId ? 'r.severity_id' : 'NULL';
    $statusExpr = report_workflow_status_expr($config, 'r');
    $statusNormalizedExpr = 'LOWER(REPLACE(REPLACE(REPLACE(COALESCE(' . $statusExpr . ', ""), "é", "e"), "è", "e"), "ê", "e"))';
    $isLeadOrAdminDashboard = is_lead_gtmp($authUser) || is_admin($authUser);
    $joinUserSql = $reportUserFk !== null
        ? 'LEFT JOIN users u ON u.id = r.' . $reportUserFk
        : '';
    $orgExpr = $reportUserFk !== null
        ? 'COALESCE(NULLIF(TRIM(u.organization_name), ""), u.full_name, "Organisation")'
        : '"Organisation"';
    $userExpr = $reportUserFk !== null ? 'r.' . $reportUserFk : 'NULL';

    $recentSql = 'SELECT r.id,
                         r.created_at,
                         ' . $typeExpr . ' AS report_type,
                         ' . $locationExpr . ' AS location_text,
                         ' . $statusExpr . ' AS workflow_status,
                         ' . $orgExpr . ' AS organization_name
                  FROM reports r
                  ' . $joinUserSql;
    $recentParams = [];
    if (!$isLeadOrAdminDashboard && $reportUserFk !== null) {
        $recentSql .= ' WHERE r.' . $reportUserFk . ' = :user_id';
        $recentParams['user_id'] = (int) ($authUser['id'] ?? 0);
    }
    $recentSql .= ' ORDER BY r.created_at DESC LIMIT 5';
    $recentStmt = $pdo->prepare($recentSql);
    $recentStmt->execute($recentParams);
    $dashboardRecentReports = $recentStmt->fetchAll() ?: [];

    $mapSql = 'SELECT r.id,
                      ' . $typeExpr . ' AS report_type,
                      ' . $urgencyExpr . ' AS urgency_level,
                      ' . $locationExpr . ' AS location_text,
                      ' . $statusExpr . ' AS workflow_status,
                      ' . $severityExpr . ' AS severity_id,
                      ' . $gpsLatExpr . ' AS gps_lat,
                      ' . $gpsLngExpr . ' AS gps_lng,
                      ' . $orgExpr . ' AS organization_name,
                      r.created_at
               FROM reports r
               ' . $joinUserSql;
    $mapParams = [];
    if (!$isLeadOrAdminDashboard && $reportUserFk !== null) {
        $mapSql .= ' WHERE (r.' . $reportUserFk . ' = :user_id OR ' . $statusNormalizedExpr . ' IN ("valide", "validee", "approuve", "approuvé", "approved", "publie", "published"))';
        $mapParams['user_id'] = (int) ($authUser['id'] ?? 0);
    }
    $mapSql .= ' ORDER BY r.created_at DESC LIMIT 300';
    $mapStmt = $pdo->prepare($mapSql);
    $mapStmt->execute($mapParams);
    $dashboardMapAlerts = $mapStmt->fetchAll() ?: [];

    if ($isLeadOrAdminDashboard) {
        $globalStmt = $pdo->query('SELECT
                                        COUNT(*) AS total_reports,
                                        SUM(CASE WHEN DATE_FORMAT(r.created_at, "%Y-%m") = DATE_FORMAT(CURRENT_DATE, "%Y-%m") THEN 1 ELSE 0 END) AS month_reports,
                                        SUM(CASE WHEN ' . $statusNormalizedExpr . ' IN ("soumis", "submitted", "en revision", "en revue", "under_review") THEN 1 ELSE 0 END) AS pending_reports,
                                        SUM(CASE WHEN ' . $statusNormalizedExpr . ' IN ("approuve", "approuvé", "approved", "publie", "valide", "validee") THEN 1 ELSE 0 END) AS approved_reports
                                    FROM reports r');
        $global = $globalStmt->fetch() ?: [];

        $activeOrgCount = 0;
        if ($reportUserFk !== null) {
            $orgCountStmt = $pdo->query('SELECT COUNT(DISTINCT r.' . $reportUserFk . ') FROM reports r');
            $activeOrgCount = (int) $orgCountStmt->fetchColumn();
        }

        $dashboardKpis = [
            ['label' => 'Rapports ce mois', 'value' => (int) ($global['month_reports'] ?? 0), 'icon' => 'fa-calendar-days'],
            ['label' => 'En attente validation', 'value' => (int) ($global['pending_reports'] ?? 0), 'icon' => 'fa-hourglass-half'],
            ['label' => 'Validés / publiés', 'value' => (int) ($global['approved_reports'] ?? 0), 'icon' => 'fa-badge-check'],
            ['label' => 'Organisations actives', 'value' => $activeOrgCount, 'icon' => 'fa-building'],
        ];
    } else {
        if ($reportUserFk !== null) {
            $orgStmt = $pdo->prepare('SELECT
                                            SUM(CASE WHEN DATE_FORMAT(r.created_at, "%Y-%m") = DATE_FORMAT(CURRENT_DATE, "%Y-%m") THEN 1 ELSE 0 END) AS month_reports,
                                            SUM(CASE WHEN ' . $statusNormalizedExpr . ' IN ("soumis", "submitted", "en revision", "en revue", "under_review") THEN 1 ELSE 0 END) AS pending_reports,
                                            SUM(CASE WHEN ' . $statusNormalizedExpr . ' IN ("approuve", "approuvé", "approved", "publie", "valide", "validee") THEN 1 ELSE 0 END) AS approved_reports,
                                            SUM(CASE WHEN ' . $statusNormalizedExpr . ' = "brouillon" THEN 1 ELSE 0 END) AS draft_reports
                                        FROM reports r
                                        WHERE r.' . $reportUserFk . ' = :user_id');
            $orgStmt->execute(['user_id' => (int) ($authUser['id'] ?? 0)]);
            $orgStats = $orgStmt->fetch() ?: [];

            $dashboardKpis = [
                ['label' => 'Vos alertes ce mois', 'value' => (int) ($orgStats['month_reports'] ?? 0), 'icon' => 'fa-calendar-days'],
                ['label' => 'En attente validation', 'value' => (int) ($orgStats['pending_reports'] ?? 0), 'icon' => 'fa-hourglass-half'],
                ['label' => 'Validées / publiées', 'value' => (int) ($orgStats['approved_reports'] ?? 0), 'icon' => 'fa-badge-check'],
                ['label' => 'Brouillons en cours', 'value' => (int) ($orgStats['draft_reports'] ?? 0), 'icon' => 'fa-pen-to-square'],
            ];
        }
    }
}

if (is_array($authUser)) {
    $hasNotifReadAt = has_table_column($config, 'notifications', 'read_at');
    if ($hasNotifReadAt) {
        $notifCountStmt = $pdo->prepare('SELECT COUNT(*)
                                         FROM notifications
                                         WHERE read_at IS NULL
                                           AND (user_id = :user_id OR user_id IS NULL)');
        $notifCountStmt->execute(['user_id' => (int) ($authUser['id'] ?? 0)]);
        $unreadNotificationsCount = (int) $notifCountStmt->fetchColumn();
    } else {
        $notifCountStmt = $pdo->prepare('SELECT COUNT(*)
                                         FROM notifications
                                         WHERE user_id = :user_id OR user_id IS NULL');
        $notifCountStmt->execute(['user_id' => (int) ($authUser['id'] ?? 0)]);
        $unreadNotificationsCount = (int) $notifCountStmt->fetchColumn();
    }

    $notifStmt = $pdo->prepare('SELECT id, title, message, target_url, created_at
                                FROM notifications
                                WHERE user_id = :user_id OR user_id IS NULL
                                ORDER BY created_at DESC
                                LIMIT 8');
    $notifStmt->execute(['user_id' => (int) ($authUser['id'] ?? 0)]);
    $topNotifications = $notifStmt->fetchAll();
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

if ($page === 'confirmer_email') {
    $token = trim((string) ($_GET['token'] ?? ''));
    if ($token !== '') {
        $stmt = $pdo->prepare('SELECT id, user_id, old_email, new_email, expires_at, used_at
                               FROM email_change_requests
                               WHERE token_hash = :token_hash
                               LIMIT 1');
        $stmt->execute(['token_hash' => hash('sha256', $token)]);
        $row = $stmt->fetch();

        if (is_array($row) && empty($row['used_at']) && strtotime((string) $row['expires_at']) > time()) {
            $emailChangeRequest = $row;
            $emailChangeRequest['token'] = $token;
        }
    }
}

$pageTitle = $pageMap[$page]['title'] . ' - ' . $config['app_name'];
$flashes = get_flashes();

require __DIR__ . '/pages/en_tete.php';

$flashPayload = [];
foreach ($flashes as $flash) {
    $flashPayload[] = [
        'type' => ((string) ($flash['type'] ?? 'success')) === 'error' ? 'error' : 'success',
        'message' => (string) ($flash['message'] ?? ''),
    ];
}
echo '<script>window.SYDRA_FLASHES = '
    . json_encode($flashPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
    . ';</script>';

require $pageMap[$page]['file'];

require __DIR__ . '/pages/pied_de_page.php';
