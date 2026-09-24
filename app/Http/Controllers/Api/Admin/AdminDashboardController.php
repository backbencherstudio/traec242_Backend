<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('admin-dashboard', weight: 4)]
class AdminDashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filter = $request->input('filter', 'yearly');

        $metrics = $this->dashboardService->getAdminMetrics($filter);

        return response()->json([
            'success' => true,
            'data' => [
                'total_user' => $metrics['total_user'],
                'total_revenue' => $metrics['total_revenue'],
                'active_order' => $metrics['active_order'],
                'chart_data' => [
                    'filter' => $filter,
                    'labels' => $metrics['labels'],
                    'this_period' => $metrics['this_period'],
                    'last_period' => $metrics['last_period'],
                ],
            ],
        ]);
    }
}
