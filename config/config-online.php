<?php

declare(strict_types=1);

// Charge la configuration depuis .env et .env. (compatibilite locale projet).
// En production, ce fichier ne doit pas contenir de secrets en dur: tout passe par les variables d'environnement.

if (!function_exists('loadEnvFile')) {
    function loadEnvFile(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $name = trim($parts[0]);
            $value = trim($parts[1]);
            if ($name === '') {
                continue;
            }

            // Strip surrounding single/double quotes
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            $_ENV[$name] = $value;
            putenv($name . '=' . $value);
        }
    }
}

$baseDir = dirname(__DIR__);
loadEnvFile($baseDir . '/.env');
loadEnvFile($baseDir . '/.env.');
loadEnvFile($baseDir . '/env.-online');

// Valeurs applicatives normalisees pour toute l'application.
// Production: APP_URL en https (ex: https://sydra.fosip-drc.org), DB_* dedies, SMTP_* valides.
return [
    'app_name' => $_ENV['APP_NAME'] ?? 'SyDRA',
    'app_env' => strtolower($_ENV['APP_ENV'] ?? 'development'),
    'app_debug' => !in_array(strtolower($_ENV['APP_DEBUG'] ?? 'false'), ['0', 'false', 'no'], true),
    'app_url' => rtrim($_ENV['APP_URL'] ?? 'https://sydra.fosip-drc.org', '/'),
    'support_email' => $_ENV['SUPPORT_EMAIL'] ?? $_ENV['ADMIN_EMAIL'] ?? ($_ENV['MAIL_FROM'] ?? 'emmanuelkubiha@gmail.com'),
    'db' => [
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => (int) ($_ENV['DB_PORT'] ?? 8889),
        'name' => $_ENV['DB_NAME'] ?? 'sydra',
        'user' => $_ENV['DB_USER'] ?? 'root',
        'pass' => $_ENV['DB_PASS'] ?? 'root',
    ],
    'mail' => [
        'from' => $_ENV['MAIL_FROM'] ?? 'noreply@sydra.local',
        'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'SyDRA',
        'smtp_host' => $_ENV['SMTP_HOST'] ?? '',
        'smtp_port' => (int) ($_ENV['SMTP_PORT'] ?? 587),
        'smtp_user' => $_ENV['SMTP_USER'] ?? '',
        'smtp_pass' => $_ENV['SMTP_PASS'] ?? '',
        'smtp_secure' => strtolower($_ENV['SMTP_SECURE'] ?? 'tls'),
        'smtp_auth' => !in_array(strtolower($_ENV['SMTP_AUTH'] ?? 'true'), ['0', 'false', 'no'], true),
    ],
];
