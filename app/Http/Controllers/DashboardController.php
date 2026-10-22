<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Role to dashboard view.
     *
     * A whitelist rather than interpolating the role straight into a view name:
     * the role name comes from the database, and view() will happily try to
     * render whatever it is handed.
     *
     * @var array<string, string>
     */
    private const VIEWS = [
        DashboardService::ROLE_ADMIN => 'dashboard.admin',
        DashboardService::ROLE_MANAGER => 'dashboard.manager',
        DashboardService::ROLE_STAFF => 'dashboard.staff',
    ];

    /**
     * Roles whose dashboard plots the movement chart.
     *
     * Staff have no chart on their dashboard, and the query behind it groups a
     * year of movements - so it is only run when something will actually draw it.
     *
     * @var array<int, string>
     */
    private const NEEDS_CHART = [
        DashboardService::ROLE_ADMIN,
        DashboardService::ROLE_MANAGER,
    ];

    /**
     * Render the dashboard widgets for the signed-in user's role.
     */
    public function index(DashboardService $dashboard): View
    {
        $user = auth()->user();
        $role = $user?->role?->name;

        $view = self::VIEWS[(string) $role] ?? 'dashboard.fallback';

        return view('dashboard', [
            'dashboardView' => $view,
            'metrics' => $dashboard->forUser($user),
            'chart' => in_array($role, self::NEEDS_CHART, true)
                ? $dashboard->stockMovementChart()
                : null,
        ]);
    }
}