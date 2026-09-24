<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderManagementCustomerDetailResource;
use App\Http\Resources\OrderManagementCustomerResource;
use App\Models\Order;
use App\Models\ProviderPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $period = $request->query('period');
        $perPage = (int) $request->query('per_page', 10);

        $query = User::role('user');

        $this->applyPeriodFilter($query, $period);

        $query->withCount([
            'orders as total_order' => function (Builder $q) use ($period): void {
                $this->applyPeriodFilter($q, $period);
            },
            'orders as complete_order' => function (Builder $q) use ($period): void {
                $q->where('status', 'completed');
                $this->applyPeriodFilter($q, $period);
            },
            'orders as pending_order' => function (Builder $q) use ($period): void {
                $q->whereIn('status', ['pending', 'confirmed']);
                $this->applyPeriodFilter($q, $period);
            },
        ]);

        $query->withSum([
            'providerPayments as total_spent' => function (Builder $q) use ($period): void {
                $q->where('provider_payments.status', 'successful')
                    ->whereHas('order', fn (Builder $oq) => $oq->where('status', 'completed'));
                $this->applyPeriodFilter($q, $period, 'provider_payments.created_at');
            },
        ], 'amount');

        if ($search) {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (! is_null($status) && $status !== '') {
            $query->where('status', $status);
        }

        $users = $query->paginate($perPage);

        $orderQuery = Order::query();
        $this->applyPeriodFilter($orderQuery, $period);

        $summary = [
            'total_orders' => (clone $orderQuery)->count(),
            'processing' => (clone $orderQuery)->whereIn('status', ['pending', 'confirmed'])->count(),
            'active_orders' => (clone $orderQuery)->where('status', 'confirmed')
                ->whereHas('providerPayments', fn (Builder $q) => $q->where('status', 'successful'))
                ->count(),
            'delivered' => (clone $orderQuery)->where('status', 'completed')->count(),
        ];

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'data' => OrderManagementCustomerResource::collection($users)->response()->getData(true)['data'],
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'next_page_url' => $users->nextPageUrl(),
                'prev_page_url' => $users->previousPageUrl(),
            ],
        ]);
    }

    public function showOrderDetails($id, Request $request): JsonResponse
    {
        $period = $request->query('period');

        $user = User::role('user')->find($id);

        if (! $user) {
            return $this->sendError('Customer not found or not available.', [], 404);
        }

        $ordersQuery = Order::where('user_id', $user->id);
        $this->applyPeriodFilter($ordersQuery, $period);

        $user->total_orders = (clone $ordersQuery)->count();
        $user->completed_orders = (clone $ordersQuery)->where('status', 'completed')->count();
        $user->pending_orders = (clone $ordersQuery)->whereIn('status', ['pending', 'confirmed'])->count();

        $paymentQuery = ProviderPayment::where('user_id', $user->id)
            ->where('status', 'successful')
            ->whereHas('order', fn (Builder $q) => $q->where('status', 'completed'));
        $this->applyPeriodFilter($paymentQuery, $period, 'created_at');

        $user->total_spent = (clone $paymentQuery)->sum('amount');

        return $this->sendResponse(OrderManagementCustomerDetailResource::make($user));
    }

    private function applyPeriodFilter(Builder $query, ?string $period, string $column = 'created_at'): Builder
    {
        if ($period === 'monthly') {
            $query->whereMonth($column, now()->month)
                ->whereYear($column, now()->year);
        } elseif ($period === 'weekly') {
            $query->whereBetween($column, [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ]);
        } elseif ($period === 'yearly') {
            $query->whereYear($column, now()->year);
        }

        return $query;
    }
}
