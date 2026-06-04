<?php
/** @var string|null $reportHeaderTitle */
$reportHeaderTitle = isset($reportHeaderTitle) ? (string) $reportHeaderTitle : 'Rapport SyDRA';
?>
<div class="report-header-branding">
    <img src="assets/img/sydra-logo/BLEU-PRIMARY-SYDRA-LOGO.png" alt="Logo SyDRA" height="40">
    <div class="report-header-text">
        <strong>GTMP - Cluster Protection</strong>
        <span><?= htmlspecialchars($reportHeaderTitle, ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
</div>
