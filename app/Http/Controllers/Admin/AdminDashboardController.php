<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProviderPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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

        $thisYearSales = ProviderPayment::join('orders', 'provider_payments.order_id', '=', 'orders.id')
            ->where('provider_payments.status', 'successful')
            ->where('orders.status', 'completed')
            ->whereYear('provider_payments.created_at', now()->year)
            ->select(
                DB::raw('MONTH(provider_payments.created_at) as month'),
                DB::raw('SUM(provider_payments.amount) as total')
            )
            ->groupBy('month')
            ->pluck('total', 'month');

        $lastYearSales = ProviderPayment::join('orders', 'provider_payments.order_id', '=', 'orders.id')
            ->where('provider_payments.status', 'successful')
            ->where('orders.status', 'completed')
            ->whereYear('provider_payments.created_at', now()->subYear()->year)
            ->select(
                DB::raw('MONTH(provider_payments.created_at) as month'),
                DB::raw('SUM(provider_payments.amount) as total')
            )
            ->groupBy('month')
            ->pluck('total', 'month');

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        $thisYear = [];
        $lastYear = [];

        for ($i = 1; $i <= 12; $i++) {
            $thisYear[] = $thisYearSales[$i] ?? 0;
            $lastYear[] = $lastYearSales[$i] ?? 0;
        }

        return response()->json([
            'success' => true,
            'overview' => [
                'total_users' => $totalUser,
                'total_revenue' => '$' . number_format($totalRevenue),
                'active_orders' => $activeOrder,
            ],
            'sales_details' => [
                'labels' => $months,
                'last_year' => $lastYear,
                'this_year' => $thisYear,
            ]
        ]);
    }
}
