<?php

declare(strict_types=1);

namespace App\Services;

final class AIService
{
    public function reformulate(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return $text;
        }

        return ucfirst($text);
    }

    public function generateSections(string $text): array
    {
        $base = trim($text);

        return [
            'contexte' => $base,
            'resume' => $base,
            'analyse' => $base,
            'recommandations' => 'A completer par le validateur.',
        ];
    }
}
