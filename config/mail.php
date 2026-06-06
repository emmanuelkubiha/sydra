<?php

declare(strict_types=1);

if (!function_exists('sendAppMail')) {
    function ensureMailerAutoload(): array
    {
        $className = 'PHPMailer\\PHPMailer\\PHPMailer';
        if (class_exists($className)) {
            return ['loaded' => true, 'source' => 'already_loaded'];
        }

        $candidates = [
            dirname(__DIR__) . '/vendor/autoload.php',
            __DIR__ . '/../vendor/autoload.php',
            getcwd() . '/vendor/autoload.php',
        ];

        foreach ($candidates as $autoloadPath) {
            if (!is_string($autoloadPath) || $autoloadPath === '') {
                continue;
            }

            if (is_file($autoloadPath)) {
                require_once $autoloadPath;
                if (class_exists($className)) {
                    return ['loaded' => true, 'source' => $autoloadPath];
                }
            }
        }

        return ['loaded' => false, 'source' => $candidates[0] ?? 'vendor/autoload.php'];
    }

    function phpmailerMissingIssue(): string
    {
        $autoloadInfo = ensureMailerAutoload();
        if (($autoloadInfo['loaded'] ?? false) === true) {
            return '';
        }

        $projectRoot = dirname(__DIR__);
        $composerJsonPath = $projectRoot . '/composer.json';
        $vendorAutoloadPath = $projectRoot . '/vendor/autoload.php';

        $composerExists = is_file($composerJsonPath);
        $vendorExists = is_file($vendorAutoloadPath);

        $parts = [];
        $parts[] = 'SMTP configure mais PHPMailer est introuvable.';

        if (!$vendorExists) {
            $parts[] = 'Le dossier vendor/autoload.php est absent.';
            if ($composerExists) {
                $parts[] = 'Executez depuis le projet: composer install';
            } else {
                $parts[] = 'Initialisez Composer puis installez PHPMailer: composer require phpmailer/phpmailer';
            }
        } else {
            $parts[] = 'vendor/autoload.php existe mais la classe PHPMailer n\'est pas chargee.';
            $parts[] = 'Verifiez que le package phpmailer/phpmailer est bien installe et non supprime du vendor.';
        }

        $parts[] = 'Chemin attendu: ' . $vendorAutoloadPath;

        return implode(' ', $parts);
    }

    function smtpConfigIssues(array $config): array
    {
        $issues = [];
        $host = trim((string) ($config['mail']['smtp_host'] ?? ''));
        $port = (int) ($config['mail']['smtp_port'] ?? 0);
        $auth = (bool) ($config['mail']['smtp_auth'] ?? true);
        $user = trim((string) ($config['mail']['smtp_user'] ?? ''));
        $pass = trim((string) ($config['mail']['smtp_pass'] ?? ''));
        $secure = strtolower((string) ($config['mail']['smtp_secure'] ?? 'tls'));

        if ($host === '') {
            $issues[] = 'SMTP non configure: SMTP_HOST est vide.';
        }
        if ($port <= 0) {
            $issues[] = 'SMTP_PORT manquant ou invalide.';
        }
        if (!in_array($secure, ['tls', 'ssl', 'none'], true)) {
            $issues[] = 'SMTP_SECURE doit etre tls, ssl ou none.';
        }
        if ($auth && $user === '') {
            $issues[] = 'SMTP_USER manquant alors que SMTP_AUTH=true.';
        }
        if ($auth && $pass === '') {
            $issues[] = 'SMTP_PASS manquant alors que SMTP_AUTH=true.';
        }

        return $issues;
    }

    /**
     * @param string|array<int, string> $emails
     * @return array<int, string>
     */
    function normalizeEmailList(string|array $emails): array
    {
        $input = is_array($emails) ? $emails : [$emails];
        $out = [];

        foreach ($input as $email) {
            $value = strtolower(trim((string) $email));
            if ($value === '' || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $out[$value] = $value;
        }

        return array_values($out);
    }

    /**
     * @param array<int, string> $to
     * @param array<int, string> $cc
     */
    function sendAppMailMultiDetailed(array $config, array $to, array $cc, string $subject, string $body, bool $isHtml = false): array
    {
        $issues = smtpConfigIssues($config);
        if ($issues !== []) {
            return ['success' => false, 'error' => implode(' ', $issues)];
        }

        $autoloadInfo = ensureMailerAutoload();
        if (!(bool) ($autoloadInfo['loaded'] ?? false)) {
            return [
                'success' => false,
                'error' => phpmailerMissingIssue(),
            ];
        }

        $to = normalizeEmailList($to);
        $cc = normalizeEmailList($cc);

        if ($to === []) {
            return ['success' => false, 'error' => 'Aucun destinataire email valide.'];
        }

        $class = 'PHPMailer\\PHPMailer\\PHPMailer';

        try {
            $mail = new $class(true);
            $mail->isSMTP();
            $mail->Host = (string) $config['mail']['smtp_host'];
            $mail->Port = (int) $config['mail']['smtp_port'];
            $mail->SMTPAuth = (bool) $config['mail']['smtp_auth'];
            $mail->Username = (string) $config['mail']['smtp_user'];
            $mail->Password = (string) $config['mail']['smtp_pass'];

            $secure = (string) ($config['mail']['smtp_secure'] ?? 'tls');
            if ($secure === 'ssl') {
                $mail->SMTPSecure = $mail::ENCRYPTION_SMTPS;
            } elseif ($secure === 'none') {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            } else {
                $mail->SMTPSecure = $mail::ENCRYPTION_STARTTLS;
            }

            $mail->setFrom((string) $config['mail']['from'], (string) $config['mail']['from_name']);

            foreach ($to as $email) {
                $mail->addAddress($email);
            }
            foreach ($cc as $email) {
                if (!in_array($email, $to, true)) {
                    $mail->addCC($email);
                }
            }

            $mail->CharSet = 'UTF-8';
            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body = $body;

            if ($isHtml) {
                $mail->AltBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body)));
            }

            $sent = $mail->send();
            if ($sent) {
                return ['success' => true, 'error' => ''];
            }

            return ['success' => false, 'error' => 'Envoi SMTP echoue sans message detaille.'];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => trim((string) $e->getMessage())];
        }
    }

    function sendAppMailDetailed(array $config, string $to, string $subject, string $body, bool $isHtml = false): array
    {
        return sendAppMailMultiDetailed($config, [$to], [], $subject, $body, $isHtml);
    }

    function sendAppMail(array $config, string $to, string $subject, string $body): bool
    {
        $result = sendAppMailDetailed($config, $to, $subject, $body);
        return (bool) ($result['success'] ?? false);
    }

    function sendAppMailHtml(array $config, string $to, string $subject, string $htmlBody): bool
    {
        $result = sendAppMailDetailed($config, $to, $subject, $htmlBody, true);
        return (bool) ($result['success'] ?? false);
    }

    function mailRoleStorageMode(array $config): string
    {
        $pdo = db($config);

        $roleColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch();
        if (is_array($roleColumn)) {
            return 'role_column';
        }

        $roleIdColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'role_id'")->fetch();
        if (is_array($roleIdColumn)) {
            return 'role_fk';
        }

        return 'role_column';
    }

    /**
     * @return array<int, string>
     */
    function getLeadAndAdminEmails(array $config): array
    {
        $pdo = db($config);
        $mode = mailRoleStorageMode($config);

        if ($mode === 'role_fk') {
            $stmt = $pdo->query("SELECT DISTINCT u.email
                                 FROM users u
                                 LEFT JOIN roles r ON r.id = u.role_id
                                 WHERE u.is_active = 1
                                   AND LOWER(COALESCE(u.statut, 'Actif')) <> 'bloque'
                                   AND COALESCE(r.code, '') IN ('ADMIN', 'CLUSTER_LEADER', 'LEAD_GTMP', 'GTMP_LEAD')");
        } else {
            $stmt = $pdo->query("SELECT DISTINCT email
                                 FROM users
                                 WHERE is_active = 1
                                   AND LOWER(COALESCE(statut, 'Actif')) <> 'bloque'
                                   AND role IN ('ADMIN', 'CLUSTER_LEADER', 'LEAD_GTMP', 'GTMP_LEAD')");
        }

        $rows = $stmt->fetchAll();
        $emails = [];
        foreach ($rows as $row) {
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[$email] = $email;
            }
        }

        return array_values($emails);
    }

    /**
     * @param array<string, mixed> $donnees
     * @return array{subject:string, html:string}
     */
    function renderNotificationTemplate(array $config, string $type, array $donnees): array
    {
        $templates = [
            'creation_compte' => 'creation_compte.php',
            'reinitialisation_mdp' => 'reinitialisation_mdp.php',
            'nouvelle_alerte_soumise' => 'nouvelle_alerte_soumise.php',
            'alerte_validee' => 'alerte_validee.php',
            'alerte_rejetee' => 'alerte_rejetee.php',
            'alerte_rejet_automatique' => 'alerte_rejet_automatique.php',
            'demande_correction' => 'demande_correction.php',
            'demande_information' => 'demande_information.php',
            'rappel_validation_lead' => 'rappel_validation_lead.php',
            'alerte_urgente_critique' => 'alerte_urgente_critique.php',
        ];

        if (!isset($templates[$type])) {
            throw new InvalidArgumentException('Type de notification inconnu: ' . $type);
        }

        $mailDir = dirname(__DIR__) . '/mail';
        $layoutFile = $mailDir . '/layout.php';
        $templateFile = $mailDir . '/' . $templates[$type];

        if (!is_file($layoutFile) || !is_file($templateFile)) {
            throw new RuntimeException('Template email introuvable dans le dossier mail/.');
        }

        require_once $layoutFile;

        $template = require $templateFile;
        if (!is_array($template)) {
            throw new RuntimeException('Le template email doit retourner un tableau.');
        }

        $mailMeta = [
            'subject' => (string) ($template['subject'] ?? 'Notification SyDRA'),
            'title' => (string) ($template['title'] ?? 'Notification'),
            'intro' => (string) ($template['intro'] ?? ''),
            'body_html' => (string) ($template['body_html'] ?? ''),
            'cta_label' => (string) ($template['cta_label'] ?? ''),
            'cta_url' => (string) ($template['cta_url'] ?? ''),
            'variant' => (string) ($template['variant'] ?? 'standard'),
            'app_name' => (string) ($config['app_name'] ?? 'SyDRA'),
            'logo_url' => rtrim((string) ($config['app_url'] ?? ''), '/') . '/assets/img/sydra-logo/WHITE-PRIMARY-SYDRA-LOGO.png',
        ];

        $html = sydra_mail_render_layout($mailMeta);

        return [
            'subject' => $mailMeta['subject'],
            'html' => $html,
        ];
    }

    /**
     * Fonction centralisee de notification email par type de template.
     *
     * @param string|array<int, string> $destinataire
     * @param array<string, mixed> $donnees
     */
    function envoyerNotificationEmail(string $type, string|array $destinataire, array $donnees = []): array
    {
        global $config;

        if (!is_array($config)) {
            return ['success' => false, 'error' => 'Configuration applicative indisponible.'];
        }

        $to = normalizeEmailList($destinataire);
        $cc = [];
        $leadAdminEmails = getLeadAndAdminEmails($config);

        // Ces notifications administratives doivent toujours inclure Lead GTMP + Admin.
        $adminTypes = ['nouvelle_alerte_soumise', 'rappel_validation_lead', 'alerte_urgente_critique'];
        if (in_array($type, $adminTypes, true)) {
            if ($to === []) {
                $to = $leadAdminEmails;
            } else {
                $cc = $leadAdminEmails;
            }
        }

        if ($to === []) {
            return ['success' => false, 'error' => 'Aucun destinataire valide pour la notification.'];
        }

        try {
            $rendered = renderNotificationTemplate($config, $type, $donnees);
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }

        return sendAppMailMultiDetailed(
            $config,
            $to,
            $cc,
            (string) ($rendered['subject'] ?? 'Notification SyDRA'),
            (string) ($rendered['html'] ?? ''),
            true
        );
    }
}
