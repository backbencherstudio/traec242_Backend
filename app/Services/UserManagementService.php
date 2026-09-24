<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class UserManagementService
{
    /**
     * Get paginated clients with optimized order counts and total spent without N+1 queries.
     */
    public function getPaginatedClients(
        ?string $search = null,
        ?string $status = null,
        ?string $period = null,
        int $perPage = 10
    ): LengthAwarePaginator {
        $query = User::where('type', 0);

        if ($search) {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
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
        $query = User::where('type', 2);

        if ($search) {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
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
