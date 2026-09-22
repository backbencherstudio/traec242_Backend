<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Order;
use App\Models\ProviderPayment;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Get user dashboard summary statistics.
     *
     * @return array<string, mixed>
     */
    public function getUserSummary(int $userId): array
    {
        $upcomingEvents = Order::where('user_id', $userId)
            ->where('event_start_date', '>=', now()->toDateString())
            ->count();

        $pastOrders = Order::where('user_id', $userId)
            ->where('event_start_date', '<', now()->toDateString())
            ->count();

        $unreadMessages = Message::where('receiver_id', $userId)
            ->whereNull('read_at')
            ->count();

        $rating = Review::where('user_id', $userId)->avg('rating');

        return [
            'upcoming_events' => $upcomingEvents,
            'past_orders' => $pastOrders,
            'unread_messages' => $unreadMessages,
            'avg_rating_score' => $rating ? round((float) $rating, 2) : null,
        ];
    }

    /**
     * Get recent orders for user dashboard.
     */
    public function getUserRecentOrders(int $userId, int $limit = 4): Collection
    {
        return Order::with(['service.user'])
            ->where('user_id', $userId)
            ->orderBy('event_start_date', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Get recent activities for user dashboard.
     *
     * @return array<int, array{title: string, time: string}>
     */
    public function getUserRecentActivity(int $userId): array
    {
        $activities = [];

        $completedOrder = Order::where('user_id', $userId)
            ->where('status', 'completed')
            ->latest('updated_at')
            ->first();

        if ($completedOrder) {
            $activities[] = [
                'title' => 'Completed order #'.$completedOrder->id,
                'time' => Carbon::parse($completedOrder->updated_at)->diffForHumans(),
            ];
        }

        $review = Review::where('user_id', $userId)
            ->where('rating', 5)
            ->latest()
            ->first();

        if ($review) {
            $activities[] = [
                'title' => 'Received a 5-star review',
                'time' => Carbon::parse($review->created_at)->diffForHumans(),
            ];
        }

        return $activities;
    }

    /**
     * Get admin dashboard metrics.
     *
     * @return array<string, mixed>
     */
    public function getAdminMetrics(string $filter = 'yearly'): array
    {
        $totalUser = User::whereIn('type', [0, 2])->count();

        $totalRevenue = ProviderPayment::where('status', 'successful')
            ->whereHas('order', fn ($q) => $q->where('status', 'completed'))
            ->sum('amount');

        $activeOrder = Order::where('status', 'confirmed')
            ->whereHas('providerPayments', fn ($q) => $q->where('status', 'successful'))
            ->count();

        if ($filter === 'monthly') {
            $labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'];
            $thisMonthStart = now()->startOfMonth();
            $thisMonthEnd = now()->endOfMonth();
            $lastMonthStart = now()->subMonth()->startOfMonth();
            $lastMonthEnd = now()->subMonth()->endOfMonth();

            $thisPeriod = $this->aggregateWeeklySales($thisMonthStart, $thisMonthEnd);
            $lastPeriod = $this->aggregateWeeklySales($lastMonthStart, $lastMonthEnd);
        } else {
            $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $thisYear = (int) now()->year;
            $lastYear = $thisYear - 1;

            $thisPeriod = $this->aggregateMonthlySales($thisYear);
            $lastPeriod = $this->aggregateMonthlySales($lastYear);
        }

        return [
            'total_user' => $totalUser,
            'total_revenue' => (float) $totalRevenue,
            'active_order' => $activeOrder,
            'labels' => $labels,
            'this_period' => $thisPeriod,
            'last_period' => $lastPeriod,
        ];
    }

    /**
     * Calculate weekly sales buckets in a database-agnostic manner.
     *
     * @return array<int, float>
     */
    protected function aggregateWeeklySales(Carbon $start, Carbon $end): array
    {
        $payments = ProviderPayment::where('status', 'successful')
            ->whereHas('order', fn ($q) => $q->where('status', 'completed'))
            ->whereBetween('created_at', [$start, $end])
            ->get(['amount', 'created_at']);

        $weeks = [1 => 0.0, 2 => 0.0, 3 => 0.0, 4 => 0.0, 5 => 0.0];

        foreach ($payments as $payment) {
            $day = (int) Carbon::parse($payment->created_at)->day;
            $weekNum = min(5, (int) ceil($day / 7));
            $weeks[$weekNum] += (float) $payment->amount;
        }

        return array_values($weeks);
    }

    /**
     * Calculate monthly sales buckets for a given year in a database-agnostic manner.
     *
     * @return array<int, float>
     */
    protected function aggregateMonthlySales(int $year): array
    {
        $payments = ProviderPayment::where('status', 'successful')
            ->whereHas('order', fn ($q) => $q->where('status', 'completed'))
            ->whereYear('created_at', $year)
            ->get(['amount', 'created_at']);

        $months = array_fill(1, 12, 0.0);

        foreach ($payments as $payment) {
            $month = (int) Carbon::parse($payment->created_at)->month;
            $months[$month] += (float) $payment->amount;
        }

        return array_values($months);
    }
}
