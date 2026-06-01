<?php

declare(strict_types=1);

namespace App\Helpers;

final class Lang
{
    public static function current(): string
    {
        if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'], true)) {
            $_SESSION['lang'] = $_GET['lang'];
        }

        return $_SESSION['lang'] ?? 'fr';
    }

    public static function tr(string $key): string
    {
        static $cache = [];
        $locale = self::current();

        if (!isset($cache[$locale])) {
            $path = __DIR__ . '/../Lang/' . $locale . '.php';
            $cache[$locale] = is_file($path) ? require $path : [];
        }

        return $cache[$locale][$key] ?? $key;
    }
}
