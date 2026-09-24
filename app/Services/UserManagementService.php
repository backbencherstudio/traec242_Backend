<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProviderPayment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class UserManagementService
{
    public function __construct(
        protected FileUploadService $fileUploadService
    ) {}

    /**
     * Get paginated clients with optimized order counts and total spent without N+1 queries.
     */
    public function getPaginatedClients(
        ?string $search = null,
        ?string $status = null,
        ?string $period = null,
        int $perPage = 10
    ): LengthAwarePaginator {
        $query = User::role('user');

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

        $this->applyPeriodFilter($query, $period);

        $query->withCount([
            'orders as total_orders' => function (Builder $q) use ($period): void {
                $this->applyPeriodFilter($q, $period);
            },
        ]);

        $query->withSum([
            'providerPayments as total_spent' => function (Builder $q) use ($period): void {
                $q->where('status', 'successful')
                    ->whereHas('order', fn (Builder $oq) => $oq->where('status', 'completed'));
                $this->applyPeriodFilter($q, $period);
            },
        ], 'amount');

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get paginated sellers (providers) with service counts and total earnings.
     */
    public function getPaginatedSellers(
        ?string $search = null,
        ?string $status = null,
        ?string $period = null,
        int $perPage = 10
    ): LengthAwarePaginator {
        $query = User::role('provider');

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

        $this->applyPeriodFilter($query, $period);

        $query->withCount([
            'services as total_services' => function (Builder $q): void {
                $q->where('status', 1);
            },
        ]);

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get single client details with order and spend statistics.
     */
    public function getClientDetails(int $id, ?string $period = null): ?User
    {
        $user = User::role('user')->find($id);

        if (! $user) {
            return null;
        }

        $ordersQuery = Order::where('user_id', $user->id);
        $this->applyPeriodFilter($ordersQuery, $period);
        $totalOrders = $ordersQuery->count();

        $spentQuery = ProviderPayment::join('orders', 'provider_payments.order_id', '=', 'orders.id')
            ->where('provider_payments.user_id', $user->id)
            ->where('orders.status', 'completed')
            ->where('provider_payments.status', 'successful');
        $this->applyPeriodFilter($spentQuery, $period, 'provider_payments.created_at');
        $totalSpent = (float) $spentQuery->sum('provider_payments.amount');

        $user->total_orders = $totalOrders;
        $user->total_spent = $totalSpent;

        return $user;
    }

    /**
     * Update user status.
     */
    public function updateUserStatus(int $id, int $status): ?User
    {
        $user = User::find($id);
        if (! $user) {
            return null;
        }

        $user->status = $status;
        $user->save();

        return $user;
    }

    /**
     * Delete user and their associated uploaded image.
     */
    public function deleteUser(int $id): bool
    {
        $user = User::find($id);
        if (! $user) {
            return false;
        }

        $this->fileUploadService->delete($user->image);
        $user->delete();

        return true;
    }

    /**
     * Apply date period filter to query builder.
     */
    protected function applyPeriodFilter(Builder $query, ?string $period, string $column = 'created_at'): void
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
    }
}
