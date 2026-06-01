<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'SyDRA'),
    'env' => env('APP_ENV', 'local'),
    'url' => env('APP_URL', 'http://localhost:8888/SyDRA/public'),
    'debug' => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
];
