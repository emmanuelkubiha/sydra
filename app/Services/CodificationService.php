<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class CodificationService
{
    public function apply(string $text): string
    {
        $pdo = Database::connection();
        $rules = $pdo->query('SELECT term, replacement_code FROM codification_rules WHERE is_active = 1')->fetchAll();
        $coded = $text;

        foreach ($rules as $rule) {
            $term = (string) ($rule['term'] ?? '');
            $code = (string) ($rule['replacement_code'] ?? '');
            if ($term !== '' && $code !== '') {
                $coded = str_ireplace($term, $code, $coded);
            }
        }

        return $coded;
    }
}
