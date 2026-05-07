<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProviderPayment;
use App\Models\User;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $totalUser = User::whereIn('type', ['0', '2'])->count();

        $totalRevenue = ProviderPayment::where('status', 'successful')
            ->whereHas('order', function ($q) {
                $q->where('status', 'completed');
            })->sum('amount');

        $activeOrder = Order::where('status', 'confirmed')
            ->whereHas('providerPayments', function ($q) {
                $q->where('status', 'successful');
            })->count();

        return response()->json([
            'success' => true,
            'overview' => [
                'total_users' => $totalUser,
                'total_revenue' => '$' . number_format($totalRevenue),
                'active_orders' => $activeOrder,
            ]
        ]);
    }
}
