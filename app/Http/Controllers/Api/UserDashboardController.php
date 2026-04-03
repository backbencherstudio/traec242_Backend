<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
    public function summary(Request $request)
    {
        $userId = $request->user()->id;

        $upComingEvents = Order::where('user_id', $userId)
            ->where('event_start_date', '>=', now()->toDateString())
            ->count();

        $pastOrders = Order::where('event_start_date', '<', now()->toDateString())
            ->count();

        $unreadMessage = Message::where('receiver_id', $userId)
            ->whereNull('read_at')
            ->count();

        $rating = 'Not set';

        return response()->json([
            'success' => true,
            'date' => [
                'upcoming_events' => $upComingEvents,
                'past_orders' => $pastOrders,
                'unread_messages' => $unreadMessage,
                'avg_rating_score' => $rating,
            ],
        ]);
    }

    public function recentOrders(Request $request)
    {
        $userId = $request->user()->id;

        $orders = Order::where('user_id', $userId)
            ->orderBy('event_start_date', 'desc')
            ->take(5)
            ->get();

        $recentOrders = $orders->map(function ($order) {
            return [
                'event_name' => $order->event_name,
                'order_by' => "{$order->user->name} {$order->user->last_name}",
                'date' => Carbon::parse($order->event_start_date)->format('M d, Y'),
                'status' => ucfirst($order->status),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $recentOrders,
        ]);
    }

    public function recentActivity(Request $request)
    {
        $userId = $request->user()->id;

        $completedOrders = Order::where('user_id', $userId)
            ->where('status', 'completed')
            ->orderBy('event_start_date', 'desc')
            ->take(5)
            ->get();

        $activities = [];

        foreach ($completedOrders as $order) {
            $activities[] = [
                'title' => "Completed order #" . $order->id,
                'time' => Carbon::parse($order->updated_at)->diffForHumans(),
            ];
        }

        $activities[] = [
            'title' => "Received 5-star review",
            'time' => 'response static',
        ];

        $activities[] = [
            'title' => "Earned Party Pro",
            'time' => 'response static',
        ];

        return response()->json([
            'success' => true,
            'recent_activity' => $activities
        ]);
    }
}
