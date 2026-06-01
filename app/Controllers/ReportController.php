<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Report;
use App\Services\AIService;
use App\Services\CodificationService;
use App\Services\ExcelService;
use App\Services\MailService;
use App\Services\PdfService;

final class ReportController
{
    public function create(): void
    {
        Auth::requireLogin();

        View::render('reports/create', [
            'title' => 'Nouveau rapport',
            'incidentTypes' => Report::referenceData('incident_types'),
            'severityLevels' => Report::referenceData('severity_levels'),
            'urgencies' => Report::referenceData('urgencies'),
            'success' => $_SESSION['success'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ]);

        unset($_SESSION['success'], $_SESSION['error']);
    }

    public function store(): void
    {
        Auth::requireLogin();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['error'] = 'Token CSRF invalide.';
            header('Location: ?r=reports/create');
            exit;
        }

        $user = Auth::user();
        if ($user === null) {
            header('Location: ?r=login');
            exit;
        }

        $reportType = strtoupper(trim((string) ($_POST['report_type'] ?? 'FLASH')));
        if (!in_array($reportType, ['FLASH', 'NOTE'], true)) {
            $reportType = 'FLASH';
        }

        $codifier = new CodificationService();

        $vulnerableSummary = $this->buildVulnerableSummary([
            'Enfants' => (int) ($_POST['vulnerable_children_count'] ?? 0),
            'Personnes agees' => (int) ($_POST['vulnerable_elderly_count'] ?? 0),
            'Femmes' => (int) ($_POST['vulnerable_women_count'] ?? 0),
            'Hommes' => (int) ($_POST['vulnerable_men_count'] ?? 0),
            'Personnes avec handicap' => (int) ($_POST['vulnerable_disability_count'] ?? 0),
            'Autres vulnerabilites' => (int) ($_POST['vulnerable_other_count'] ?? 0),
        ]);

        $organizationId = $this->resolveOrganizationIdByUserId((int) $user['id']);
        if ($organizationId === null) {
            $_SESSION['error'] = 'Utilisateur sans organisation valide.';
            header('Location: ?r=reports/create');
            exit;
        }

        Report::create([
            'organization_id' => $organizationId,
            'reporter_user_id' => (int) $user['id'],
            'report_type' => $reportType,
            'is_submit' => isset($_POST['submit_report']) && $_POST['submit_report'] === '1',
            'incident_type_id' => trim((string) ($_POST['incident_type_id'] ?? '')),
            'severity_id' => trim((string) ($_POST['severity_id'] ?? '')),
            'urgency_id' => trim((string) ($_POST['urgency_id'] ?? '')),
            'province' => trim((string) ($_POST['province'] ?? '')),
            'territory' => trim((string) ($_POST['territory'] ?? '')),
            'health_zone' => trim((string) ($_POST['health_zone'] ?? '')),
            'groupement' => trim((string) ($_POST['groupement'] ?? '')),
            'village' => trim((string) ($_POST['village'] ?? '')),
            'locality' => trim((string) ($_POST['locality'] ?? '')),
            'place_search_text' => trim((string) ($_POST['place_search_text'] ?? '')),
            'latitude' => trim((string) ($_POST['latitude'] ?? '')),
            'longitude' => trim((string) ($_POST['longitude'] ?? '')),
            'households_count' => trim((string) ($_POST['households_count'] ?? '')),
            'people_count' => trim((string) ($_POST['people_count'] ?? '')),
            'vulnerable_children_count' => trim((string) ($_POST['vulnerable_children_count'] ?? '0')),
            'vulnerable_elderly_count' => trim((string) ($_POST['vulnerable_elderly_count'] ?? '0')),
            'vulnerable_women_count' => trim((string) ($_POST['vulnerable_women_count'] ?? '0')),
            'vulnerable_men_count' => trim((string) ($_POST['vulnerable_men_count'] ?? '0')),
            'vulnerable_disability_count' => trim((string) ($_POST['vulnerable_disability_count'] ?? '0')),
            'vulnerable_other_count' => trim((string) ($_POST['vulnerable_other_count'] ?? '0')),
            'vulnerable_categories' => $vulnerableSummary,
            'context_text' => $codifier->apply(trim((string) ($_POST['context_text'] ?? ''))),
            'facts_text' => $codifier->apply(trim((string) ($_POST['facts_text'] ?? ''))),
            'analysis_text' => $codifier->apply(trim((string) ($_POST['analysis_text'] ?? ''))),
            'impacts_text' => $codifier->apply(trim((string) ($_POST['impacts_text'] ?? ''))),
            'needs_text' => $codifier->apply(trim((string) ($_POST['needs_text'] ?? ''))),
            'recommendations_text' => $codifier->apply(trim((string) ($_POST['recommendations_text'] ?? ''))),
        ]);

        $_SESSION['success'] = 'Rapport enregistre avec succes.';
        header('Location: ?r=reports/create');
        exit;
    }

    public function index(): void
    {
        Auth::requireLogin();

        View::render('reports/index', [
            'title' => 'Liste des rapports',
            'reports' => Report::latest(100),
        ]);
    }

    public function locationSearch(): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json; charset=utf-8');

        $query = trim((string) ($_GET['q'] ?? ''));
        if ($query === '' || strlen($query) < 3) {
            echo json_encode(['items' => []]);
            return;
        }

        $url = 'https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=8&q=' . urlencode($query);
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: SyDRA/1.0\r\n",
                'timeout' => 8,
            ],
        ];

        $context = stream_context_create($opts);
        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            echo json_encode(['items' => []]);
            return;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            echo json_encode(['items' => []]);
            return;
        }

        $items = [];
        foreach ($decoded as $item) {
            $items[] = [
                'label' => $item['display_name'] ?? '',
                'lat' => $item['lat'] ?? null,
                'lng' => $item['lon'] ?? null,
                'address' => $item['address'] ?? [],
            ];
        }

        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
    }

    public function locationReverse(): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json; charset=utf-8');

        $lat = trim((string) ($_GET['lat'] ?? ''));
        $lng = trim((string) ($_GET['lng'] ?? ''));

        if ($lat === '' || $lng === '') {
            echo json_encode(['item' => null]);
            return;
        }

        $url = 'https://nominatim.openstreetmap.org/reverse?format=json&addressdetails=1&lat=' . urlencode($lat) . '&lon=' . urlencode($lng);
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: SyDRA/1.0\r\n",
                'timeout' => 8,
            ],
        ];

        $context = stream_context_create($opts);
        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            echo json_encode(['item' => null]);
            return;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            echo json_encode(['item' => null]);
            return;
        }

        echo json_encode([
            'item' => [
                'label' => $decoded['display_name'] ?? '',
                'lat' => $decoded['lat'] ?? null,
                'lng' => $decoded['lon'] ?? null,
                'address' => $decoded['address'] ?? [],
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    public function aiAssist(): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json; charset=utf-8');

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            echo json_encode(['ok' => false, 'message' => 'Token CSRF invalide']);
            return;
        }

        $text = trim((string) ($_POST['text'] ?? ''));
        $ai = new AIService();
        echo json_encode([
            'ok' => true,
            'reformulated' => $ai->reformulate($text),
            'sections' => $ai->generateSections($text),
            'questions' => [
                'Quel est le nombre estime de personnes affectees ?',
                'Quels besoins prioritaires immediats sont identifies ?',
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    public function exportPdf(): void
    {
        Auth::requireLogin();
        $service = new PdfService();
        $file = __DIR__ . '/../../public/uploads/export_' . date('Ymd_His') . '.pdf';
        $service->generateReport('<h1>Export rapport SyDRA</h1>', $file);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'file' => 'public/uploads/' . basename($file)]);
    }

    public function exportExcel(): void
    {
        Auth::requireLogin();
        $service = new ExcelService();
        $file = __DIR__ . '/../../public/uploads/export_' . date('Ymd_His') . '.csv';
        $service->exportRows([
            ['Reference', 'Type', 'Date'],
            ['DEMO-001', 'FLASH', date('Y-m-d H:i:s')],
        ], $file);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'file' => 'public/uploads/' . basename($file)]);
    }

    public function sendEmail(): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json; charset=utf-8');

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            echo json_encode(['ok' => false, 'message' => 'Token CSRF invalide']);
            return;
        }

        $to = trim((string) ($_POST['to'] ?? ''));
        if ($to === '') {
            echo json_encode(['ok' => false, 'message' => 'Destinataire requis']);
            return;
        }

        $mailer = new MailService();
        $ok = $mailer->send($to, 'Alerte SyDRA', 'Notification automatique SyDRA');
        echo json_encode(['ok' => $ok], JSON_UNESCAPED_UNICODE);
    }

    private function resolveOrganizationIdByUserId(int $userId): ?int
    {
        $pdo = \App\Core\Database::connection();
        $stmt = $pdo->prepare('SELECT organization_id FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();

        if (!$row || !isset($row['organization_id']) || $row['organization_id'] === null) {
            return null;
        }

        return (int) $row['organization_id'];
    }

    private function buildVulnerableSummary(array $values): string
    {
        $parts = [];
        foreach ($values as $label => $count) {
            if ($count > 0) {
                $parts[] = $label . ': ' . $count;
            }
        }

        return implode(', ', $parts);
    }
}
