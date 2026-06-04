<?php

declare(strict_types=1);

if (!function_exists('sydra_report_logo_web')) {
    function sydra_report_logo_web(): string
    {
        return 'assets/img/sydra-logo/BLEU-PRIMARY-SYDRA-LOGO.png';
    }
}

if (!function_exists('sydra_report_logo_fs')) {
    function sydra_report_logo_fs(): string
    {
        return dirname(__DIR__) . '/assets/img/sydra-logo/BLEU-PRIMARY-SYDRA-LOGO.png';
    }
}

if (!function_exists('sydra_apply_pdf_header')) {
    /**
     * Helper reutilisable pour scripts TCPDF (si installes ensuite).
     *
     * @param mixed $pdf Instance TCPDF
     */
    function sydra_apply_pdf_header($pdf): void
    {
        if (!is_object($pdf) || !method_exists($pdf, 'Image')) {
            return;
        }

        $logo = sydra_report_logo_fs();
        if (!is_file($logo)) {
            return;
        }

        // Position et dimensions standards pour fond blanc (papier/pdf).
        $pdf->Image($logo, 12, 8, 34, 0, 'PNG');
        if (method_exists($pdf, 'SetY')) {
            $pdf->SetY(28);
        }
    }
}
