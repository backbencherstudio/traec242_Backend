<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProviderPayment;
use App\Models\User;
use Illuminate\Http\Request;

class OrderManagementController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;
        $status = $request->status;
        $period = $request->period;

        $query = User::where('type', '0');

        self::applyPeriodFilter($query, $period);

        $query = $query->withCount([

            'orders as total_order' => function ($q) use ($period) {

                self::applyPeriodFilter($q, $period);
            },

            'orders as complete_order' => function ($q) use ($period) {

                $q->where('status', 'completed');

                self::applyPeriodFilter($q, $period);
            },

            'orders as pending_order' => function ($q) use ($period) {

                $q->whereIn('status', ['pending', 'confirmed']);

                self::applyPeriodFilter($q, $period);
            }
        ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('last_name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }

        if (!is_null($status)) {
            $query->where('status', $status);
        }

        $perPage = $request->per_page ?? 10;

        $users = $query->paginate($perPage);

        $orderQuery = Order::query();

        self::applyPeriodFilter($orderQuery, $period);

        $summary = (clone $orderQuery)->count();

        $processing = (clone $orderQuery)
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();

        $delivered = (clone $orderQuery)
            ->where('status', 'completed')
            ->count();

        $activeOrder = (clone $orderQuery)
            ->where('status', 'confirmed')
            ->whereHas('providerPayments', function ($q) {
                $q->where('status', 'successful');
            })
            ->count();

        $data = $users->getCollection()->map(function ($user) use ($period) {

            $totalSpentQuery = ProviderPayment::join(
                'orders',
                'provider_payments.order_id',
                '=',
                'orders.id'
            )
                ->where('provider_payments.user_id', $user->id)
                ->where('orders.status', 'completed')
                ->where('provider_payments.status', 'successful');

            self::applyPeriodFilter(
                $totalSpentQuery,
                $period,
                'provider_payments.created_at'
            );

            $totalSpent = $totalSpentQuery->sum('provider_payments.amount');

            return [
                'customer_info' => [
                    'id' => $user->id,
                    'image' => $user->image,
                    'name' => trim(($user->name ?? '') . ' ' . ($user->last_name ?? '')),
                    'email' => $user->email,
                    'total_order' => $user->total_order,
                    'complete_order' => $user->complete_order,
                    'pending_order' => $user->pending_order,
                    'total_spent' => '$' . number_format($totalSpent, 2),
                ]
            ];
        });

        $users->setCollection($data);

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ]
        ]);
    }

    private static function applyPeriodFilter($query, $period, $column = 'created_at')
    {
        if ($period == 'monthly') {

            $query->whereMonth($column, now()->month)
                ->whereYear($column, now()->year);
        } elseif ($period == 'weekly') {

            $query->whereBetween($column, [
                now()->startOfWeek(),
                now()->endOfWeek()
            ]);
        } elseif ($period == 'yearly') {

            $query->whereYear($column, now()->year);
        }

        return $query;
    }
}
