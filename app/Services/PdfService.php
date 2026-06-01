<?php

declare(strict_types=1);

namespace App\Services;

final class PdfService
{
    public function generateReport(string $html, string $targetPath): bool
    {
        if (class_exists('TCPDF')) {
            return true;
        }

        return (bool) file_put_contents($targetPath, strip_tags($html));
    }
}
