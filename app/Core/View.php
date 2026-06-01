<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        $app = require __DIR__ . '/../../config/app.php';
        $contentView = __DIR__ . '/../Views/' . $view . '.php';

        if (!is_file($contentView)) {
            http_response_code(404);
            exit('Vue introuvable: ' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8'));
        }

        require __DIR__ . '/../Views/layouts/main.php';
    }
}
