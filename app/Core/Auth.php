<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function attempt(array $user): void
    {
        $_SESSION['user'] = $user;
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: ?r=login');
            exit;
        }
    }

    public static function hasRole(array $roles): bool
    {
        $user = self::user();
        if ($user === null) {
            return false;
        }

        return in_array($user['role_code'] ?? '', $roles, true);
    }
}
