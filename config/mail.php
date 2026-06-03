<?php

declare(strict_types=1);

if (!function_exists('sendAppMail')) {
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
            $issues[] = 'SMTP non configuré: SMTP_HOST est vide.';
        }
        if ($port <= 0) {
            $issues[] = 'SMTP_PORT manquant ou invalide.';
        }
        if (!in_array($secure, ['tls', 'ssl', 'none'], true)) {
            $issues[] = 'SMTP_SECURE doit être tls, ssl ou none.';
        }
        if ($auth && $user === '') {
            $issues[] = 'SMTP_USER manquant alors que SMTP_AUTH=true.';
        }
        if ($auth && $pass === '') {
            $issues[] = 'SMTP_PASS manquant alors que SMTP_AUTH=true.';
        }

        return $issues;
    }

    function sendAppMailDetailed(array $config, string $to, string $subject, string $body): array
    {
        $issues = smtpConfigIssues($config);
        if ($issues !== []) {
            return ['success' => false, 'error' => implode(' ', $issues)];
        }

        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return [
                'success' => false,
                'error' => 'SMTP configuré mais PHPMailer est manquant. Installez PHPMailer via Composer (composer require phpmailer/phpmailer) et chargez vendor/autoload.php.',
            ];
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
            $mail->addAddress($to);
            $mail->CharSet = 'UTF-8';
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $body;

            $sent = $mail->send();
            if ($sent) {
                return ['success' => true, 'error' => ''];
            }

            return ['success' => false, 'error' => 'Envoi SMTP échoué sans message détaillé.'];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => trim((string) $e->getMessage())];
        }
    }

    function sendAppMail(array $config, string $to, string $subject, string $body): bool
    {
        $result = sendAppMailDetailed($config, $to, $subject, $body);
        return (bool) ($result['success'] ?? false);
    }
}
