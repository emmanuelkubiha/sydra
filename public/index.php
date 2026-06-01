<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\MapController;
use App\Controllers\OrganisationController;
use App\Controllers\ProfilController;
use App\Controllers\ReportController;
use App\Core\Auth;

session_start();

require __DIR__ . '/../app/Core/Env.php';
$envPath = __DIR__ . '/../.env';
if (!is_file($envPath) && is_file(__DIR__ . '/../.env.')) {
    $envPath = __DIR__ . '/../.env.';
}
loadEnv($envPath);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = __DIR__ . '/../app/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

$route = $_GET['r'] ?? 'login';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$publicRoutes = ['login'];
if (!in_array($route, $publicRoutes, true) && !Auth::check()) {
    if (strpos($route, 'api/') === 0) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'Non authentifie']);
    } else {
        header('Location: ?r=login');
    }
    exit;
}

$authController = new AuthController();
$dashboardController = new DashboardController();
$mapController = new MapController();
$organisationController = new OrganisationController();
$profilController = new ProfilController();
$reportController = new ReportController();

switch ($route) {
    case 'login':
        if ($method === 'POST') {
            $authController->login();
            break;
        }
        $authController->showLogin();
        break;

    case 'logout':
        $authController->logout();
        break;

    case 'dashboard':
        $dashboardController->index();
        break;

    case 'reports/create':
        if ($method === 'POST') {
            $reportController->store();
            break;
        }
        $reportController->create();
        break;

    case 'reports/list':
        $reportController->index();
        break;

    case 'cartographie':
        $mapController->index();
        break;

    case 'api/map/data':
        $mapController->data();
        break;

    case 'organisations':
        $organisationController->index();
        break;

    case 'organisations/store':
        if ($method === 'POST') {
            $organisationController->store();
            break;
        }
        http_response_code(405);
        echo 'Methode non autorisee.';
        break;

    case 'organisations/toggle':
        if ($method === 'POST') {
            $organisationController->toggle();
            break;
        }
        http_response_code(405);
        echo 'Methode non autorisee.';
        break;

    case 'profil/mot-de-passe':
        if ($method === 'POST') {
            $profilController->changerMotDePasse();
            break;
        }
        $profilController->motDePasseForm();
        break;

    case 'api/locations/search':
        $reportController->locationSearch();
        break;

    case 'api/locations/reverse':
        $reportController->locationReverse();
        break;

    case 'api/ai/assist':
        if ($method === 'POST') {
            $reportController->aiAssist();
            break;
        }
        http_response_code(405);
        echo 'Methode non autorisee.';
        break;

    case 'api/reports/export-pdf':
        $reportController->exportPdf();
        break;

    case 'api/reports/export-excel':
        $reportController->exportExcel();
        break;

    case 'api/notifications/email':
        if ($method === 'POST') {
            $reportController->sendEmail();
            break;
        }
        http_response_code(405);
        echo 'Methode non autorisee.';
        break;

    default:
        http_response_code(404);
        echo 'Route introuvable.';
}
