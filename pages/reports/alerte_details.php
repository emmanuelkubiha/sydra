<?php

declare(strict_types=1);

session_start();

$config = require __DIR__ . '/../../config/config.php';
require __DIR__ . '/../../config/database.php';
$appUrl = rtrim((string) ($config['app_url'] ?? ''), '/');

if (!isset($_SESSION['auth_user_id'])) {
    header('Location: ' . $appUrl . '/?page=connexion');
    exit;
}

$pdo = db($config);
$reportId = (int) ($_GET['id'] ?? 0);
if ($reportId <= 0) {
    http_response_code(400);
    echo 'Alerte invalide.';
    exit;
}

$hasColumn = static function (string $columnName) use ($pdo): bool {
    $stmt = $pdo->prepare('SELECT COUNT(*)
                           FROM information_schema.COLUMNS
                           WHERE TABLE_SCHEMA = DATABASE()
                             AND TABLE_NAME = :table_name
                             AND COLUMN_NAME = :column_name');
    $stmt->execute([
        'table_name' => 'reports',
        'column_name' => $columnName,
    ]);
    return (int) $stmt->fetchColumn() > 0;
};

$hasTitle = $hasColumn('title');
$hasType = $hasColumn('report_type');
$hasContent = $hasColumn('content');
$hasLocation = $hasColumn('location_text');
$hasUrgency = $hasColumn('urgency_level');

$reportUserFk = null;
foreach (['user_id', 'author_id', 'created_by', 'reporter_id'] as $candidate) {
    if ($hasColumn($candidate)) {
        $reportUserFk = $candidate;
        break;
    }
}

$titleExpr = $hasTitle
    ? 'r.title'
    : ($hasContent ? 'SUBSTRING(COALESCE(r.content, ""), 1, 120)' : 'CONCAT("Rapport #", r.id)');
$typeExpr = $hasType ? 'r.report_type' : '"FLASH"';
$contentExpr = $hasContent ? 'r.content' : '""';
$locationExpr = $hasLocation ? 'r.location_text' : 'NULL';
$urgencyExpr = $hasUrgency ? 'r.urgency_level' : '"Moyenne"';

$authorExpr = $reportUserFk !== null ? 'u.full_name' : '"Utilisateur inconnu"';
$joinUserSql = $reportUserFk !== null
        ? 'LEFT JOIN users u ON u.id = r.' . $reportUserFk
        : '';

$stmt = $pdo->prepare('SELECT r.id, '
    . $titleExpr . ' AS title, '
    . $typeExpr . ' AS report_type, '
    . $contentExpr . ' AS content, '
    . $locationExpr . ' AS location_text, '
    . $urgencyExpr . ' AS urgency_level, '
        . 'r.created_at, ' . $authorExpr . ' AS author_name
      FROM reports r
            ' . $joinUserSql . '
      WHERE r.id = :id
      LIMIT 1');
$stmt->execute(['id' => $reportId]);
$report = $stmt->fetch();

if (!is_array($report)) {
    http_response_code(404);
    echo 'Alerte introuvable.';
    exit;
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Détails alerte #<?= (int) $report['id']; ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f1f5f9; margin: 0; padding: 20px; }
        .card { max-width: 860px; margin: 0 auto; background: #fff; border: 1px solid #dbeafe; border-radius: 10px; padding: 20px; }
        .badge { display: inline-block; background: #dbeafe; color: #1e3a8a; border-radius: 999px; padding: 4px 10px; }
        a { color: #0b4f8a; }
    </style>
</head>
<body>
<div class="card">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid #dbeafe;">
        <img src="<?= htmlspecialchars($appUrl . '/assets/img/sydra-logo/BLEU-PRIMARY-SYDRA-LOGO.png', ENT_QUOTES, 'UTF-8'); ?>" alt="Logo SyDRA" height="40">
        <strong style="color:#005bbb;">Rapport officiel GTMP / Cluster Protection</strong>
    </div>
    <h1><?= htmlspecialchars((string) $report['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p><span class="badge">Type: <?= htmlspecialchars((string) $report['report_type'], ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="badge">Urgence: <?= htmlspecialchars((string) $report['urgency_level'], ENT_QUOTES, 'UTF-8'); ?></span></p>
    <p><strong>Lieu:</strong> <?= htmlspecialchars((string) ($report['location_text'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></p>
    <p><strong>Soumis par:</strong> <?= htmlspecialchars((string) $report['author_name'], ENT_QUOTES, 'UTF-8'); ?></p>
    <p><strong>Date:</strong> <?= htmlspecialchars((string) $report['created_at'], ENT_QUOTES, 'UTF-8'); ?></p>
    <hr>
    <p><?= nl2br(htmlspecialchars((string) $report['content'], ENT_QUOTES, 'UTF-8')); ?></p>
    <p><a href="<?= htmlspecialchars($appUrl . '/?page=rapports_liste', ENT_QUOTES, 'UTF-8'); ?>">Retour aux rapports</a></p>
</div>
</body>
</html>
