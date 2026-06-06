<?php

declare(strict_types=1);

if (!function_exists('sydra_mail_render_layout')) {
    /**
     * Rend un template email HTML commun avec charte SyDRA.
     *
     * @param array<string, string> $meta
     */
    function sydra_mail_render_layout(array $meta): string
    {
        $subject = $meta['subject'] ?? 'Notification SyDRA';
        $title = $meta['title'] ?? 'Notification';
        $intro = $meta['intro'] ?? '';
        $bodyHtml = $meta['body_html'] ?? '';
        $ctaLabel = $meta['cta_label'] ?? '';
        $ctaUrl = $meta['cta_url'] ?? '';
        $appName = $meta['app_name'] ?? 'SyDRA';
        $variant = strtolower($meta['variant'] ?? 'standard');

        $headerBg = '#005bbb';
        $badgeBg = '#eaf2fb';
        $badgeColor = '#005bbb';

        if ($variant === 'critical') {
            $headerBg = '#9b1c1c';
            $badgeBg = '#fee2e2';
            $badgeColor = '#9b1c1c';
        }

        $ctaHtml = '';
        if ($ctaLabel !== '' && $ctaUrl !== '') {
            $ctaHtml = '<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:20px 0 0;">'
                . '<tr><td style="border-radius:8px;background:' . $headerBg . ';">'
                . '<a href="' . htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8') . '" '
                . 'style="display:inline-block;padding:12px 18px;border-radius:8px;text-decoration:none;background:' . $headerBg . ';color:#ffffff;font-weight:700;font-family:Arial,Helvetica,sans-serif;">'
                . htmlspecialchars($ctaLabel, ENT_QUOTES, 'UTF-8')
                . '</a>'
                . '</td></tr></table>';
        }

        $logo = rtrim((string) ($meta['logo_url'] ?? ''), '/');
        $logoHtml = $logo !== ''
            ? '<img src="' . htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') . '" alt="SyDRA" width="136" style="display:block;border:0;outline:none;text-decoration:none;">'
            : '<div style="font-size:20px;font-weight:700;color:#ffffff;">' . htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') . '</div>';

        return '<!doctype html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'
            . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8')
            . '</title></head><body style="margin:0;padding:0;background:#edf2f7;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">'
            . '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#edf2f7;">'
            . '<tr><td align="center" style="padding:20px 10px;">'
            . '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="700" style="width:100%;max-width:700px;background:#ffffff;border:1px solid #d9e5f3;border-radius:12px;overflow:hidden;">'
            . '<tr><td style="padding:16px 20px;background:' . $headerBg . ';">'
            . $logoHtml
            . '<div style="margin-top:8px;font-size:12px;color:#dbeafe;">Système GTMP - Cluster Protection</div>'
            . '</td></tr>'
            . '<tr><td style="padding:22px 22px 14px;">'
            . '<span style="display:inline-block;background:' . $badgeBg . ';color:' . $badgeColor . ';padding:5px 10px;border-radius:999px;font-size:12px;font-weight:700;">Notification officielle</span>'
            . '<h2 style="margin:12px 0 10px;font-size:20px;line-height:1.3;">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>'
            . '<p style="margin:0 0 12px;line-height:1.6;">' . htmlspecialchars($intro, ENT_QUOTES, 'UTF-8') . '</p>'
            . $bodyHtml
            . $ctaHtml
            . '</td></tr>'
            . '<tr><td style="padding:14px 22px 22px;border-top:1px solid #e2e8f0;font-size:12px;line-height:1.6;color:#64748b;">'
            . 'Email automatique SyDRA.<br>'
            . '<a href="https://www.gtmp.org" style="color:#005bbb;text-decoration:none;">GTMP</a> · '
            . '<a href="https://www.fosip-drc.org" style="color:#005bbb;text-decoration:none;">FOSIP-DRC</a>'
            . '</td></tr>'
            . '</table></td></tr></table>'
            . '</body></html>';
    }
}
