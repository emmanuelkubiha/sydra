<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Report;

final class DashboardController
{
    public function index(): void
    {
        Auth::requireLogin();

        View::render('dashboard/index', [
            'title' => 'Dashboard SyDRA',
            'stats' => Report::dashboardStats(),
            'reports' => Report::latest(10),
        ]);
    }
}
