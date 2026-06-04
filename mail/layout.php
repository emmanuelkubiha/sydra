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
            $ctaHtml = '<p style="margin:20px 0 0;">'
                . '<a href="' . htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8') . '" '
                . 'style="display:inline-block;padding:12px 18px;border-radius:8px;text-decoration:none;background:' . $headerBg . ';color:#ffffff;font-weight:700;">'
                . htmlspecialchars($ctaLabel, ENT_QUOTES, 'UTF-8')
                . '</a></p>';
        }

        return '<!doctype html><html><head><meta charset="UTF-8"><title>'
            . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8')
            . '</title></head><body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">'
            . '<div style="max-width:760px;margin:22px auto;background:#ffffff;border:1px solid #d9e5f3;border-radius:12px;overflow:hidden;">'
            . '<div style="padding:14px 18px;background:' . $headerBg . ';color:#ffffff;">'
            . '<div style="font-size:12px;opacity:.9;">SYSTEME GTMP - CLUSTER PROTECTION</div>'
            . '<h1 style="margin:6px 0 0;font-size:21px;">' . htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') . '</h1>'
            . '</div>'
            . '<div style="padding:20px 22px;">'
            . '<span style="display:inline-block;background:' . $badgeBg . ';color:' . $badgeColor . ';padding:5px 10px;border-radius:999px;font-size:12px;font-weight:700;">Notification officielle</span>'
            . '<h2 style="margin:12px 0 10px;font-size:20px;">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>'
            . '<p style="margin:0 0 12px;line-height:1.5;">' . htmlspecialchars($intro, ENT_QUOTES, 'UTF-8') . '</p>'
            . $bodyHtml
            . $ctaHtml
            . '<p style="margin:22px 0 0;font-size:12px;color:#64748b;">Email automatique SyDRA. Merci de ne pas repondre directement.</p>'
            . '</div>'
            . '</div>'
            . '</body></html>';
    }
}
