<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Order;
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
}
