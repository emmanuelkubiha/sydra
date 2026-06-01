<?php

declare(strict_types=1);

namespace App\Services;

final class ExcelService
{
    public function exportRows(array $rows, string $targetPath): bool
    {
        if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            return true;
        }

        $fp = fopen($targetPath, 'wb');
        if ($fp === false) {
            return false;
        }

        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }

        fclose($fp);
        return true;
    }
}
