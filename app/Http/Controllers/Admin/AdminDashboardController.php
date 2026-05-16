<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProviderPayment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
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

        $filter = $request->filter ?? 'yearly';

        if ($filter == 'monthly') {

            $thisMonthSales = ProviderPayment::join('orders', 'provider_payments.order_id', '=', 'orders.id')
                ->where('provider_payments.status', 'successful')
                ->where('orders.status', 'completed')
                ->whereMonth('provider_payments.created_at', now()->month)
                ->whereYear('provider_payments.created_at', now()->year)
                ->select(
                    DB::raw('WEEK(provider_payments.created_at, 1) - WEEK(DATE_SUB(provider_payments.created_at, INTERVAL DAYOFMONTH(provider_payments.created_at)-1 DAY),1) + 1 as week'),
                    DB::raw('SUM(provider_payments.amount) as total')
                )
                ->groupBy('week')
                ->pluck('total', 'week');

            $lastMonth = now()->subMonth();

            $lastMonthSales = ProviderPayment::join('orders', 'provider_payments.order_id', '=', 'orders.id')
                ->where('provider_payments.status', 'successful')
                ->where('orders.status', 'completed')
                ->whereMonth('provider_payments.created_at', $lastMonth->month)
                ->whereYear('provider_payments.created_at', $lastMonth->year)
                ->select(
                    DB::raw('WEEK(provider_payments.created_at, 1) - WEEK(DATE_SUB(provider_payments.created_at, INTERVAL DAYOFMONTH(provider_payments.created_at)-1 DAY),1) + 1 as week'),
                    DB::raw('SUM(provider_payments.amount) as total')
                )
                ->groupBy('week')
                ->pluck('total', 'week');

            $labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];

            $thisPeriod = [];
            $lastPeriod = [];

            for ($i = 1; $i <= 4; $i++) {
                $thisPeriod[] = $thisMonthSales[$i] ?? 0;
                $lastPeriod[] = $lastMonthSales[$i] ?? 0;
            }
        } else {

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

            $labels = [
                'Jan',
                'Feb',
                'Mar',
                'Apr',
                'May',
                'Jun',
                'Jul',
                'Aug',
                'Sep',
                'Oct',
                'Nov',
                'Dec'
            ];

            $thisPeriod = [];
            $lastPeriod = [];

            for ($i = 1; $i <= 12; $i++) {
                $thisPeriod[] = $thisYearSales[$i] ?? 0;
                $lastPeriod[] = $lastYearSales[$i] ?? 0;
            }
        }
        return response()->json([
            'success' => true,
            'message' => 'Dashboard data fetched successfully',
            'overview' => [
                'total_users' => $totalUser,
                'total_revenue' => '$' . number_format($totalRevenue),
                'active_orders' => $activeOrder,
            ],
            'sales_details' => [
                'filter' => $filter,
                'labels' => $labels,
                'last' => $lastPeriod,
                'this' => $thisPeriod,
            ]
        ]);
    }
}
