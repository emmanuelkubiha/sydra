<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

final class MapController
{
    public function index(): void
    {
        Auth::requireLogin();
        View::render('cartographie/index', [
            'title' => 'Cartographie des incidents',
        ]);
    }

    public function data(): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json; charset=utf-8');

        $territory = trim((string) ($_GET['territory'] ?? ''));
        $severity = trim((string) ($_GET['severity'] ?? ''));

        $pdo = Database::connection();
        $sql = 'SELECT r.id, r.reference_code, r.report_type, r.territory, r.locality,
                       r.latitude, r.longitude, sl.label AS severity_label
                FROM reports r
                LEFT JOIN severity_levels sl ON sl.id = r.severity_id
                WHERE r.latitude IS NOT NULL AND r.longitude IS NOT NULL';

        $params = [];
        if ($territory !== '') {
            $sql .= ' AND r.territory LIKE :territory';
            $params['territory'] = '%' . $territory . '%';
        }
        if ($severity !== '') {
            $sql .= ' AND sl.code = :severity';
            $params['severity'] = $severity;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['items' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
    }
}
