<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserDashboardController extends Controller
{
    public function summary(Request $request)
    {
        $userId = $request->user()->id;

        $upComingEvents = Order::where('user_id', $userId)
            ->where('event_start_date', '>=', now()->toDateString())
            ->count();

        $pastOrders = Order::where('user_id', $userId)
            ->where('event_start_date', '<', now()->toDateString())
            ->count();

        $unreadMessage = Message::where('receiver_id', $userId)
            ->whereNull('read_at')
            ->count();

        $rating = Review::where('user_id', $userId)
            ->avg('rating');

        $rating = $rating ? round($rating, 2) : null;


        return response()->json([
            'success' => true,
            'data' => [
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

        $orders = Order::with(['service.user'])
            ->where('user_id', $userId)
            ->orderBy('event_start_date', 'desc')
            ->take(4)
            ->get();

        $recentOrders = $orders->map(function ($order) {
            return [
                'event_name' => $order->event_name,
                'order_by' => trim(
                    ($order->service?->user?->name ?? '') . ' ' .
                        ($order->service?->user?->last_name ?? '')
                ),
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
            ->latest('updated_at')
            ->first();

        $activities = [];

        if ($completedOrders) {
            $activities[] = [
                'title' => 'Completed order #' . $completedOrders->id,
                'time'  => Carbon::parse($completedOrders->updated_at)->diffForHumans(),
            ];
        }

        $review = Review::where('user_id', $userId)
            ->where('rating', 5)
            ->latest()
            ->first();

        if ($review) {
            $activities[] = [
                'title' => 'Received 5-star review',
                'time'  => Carbon::parse($review->created_at)->diffForHumans(),
            ];
        }

        return response()->json([
            'success' => true,
            'recent_activity' => $activities,
        ]);
    }

    public function recentMessages(Request $request)
    {
        $userId = $request->user()->id;

        $messages = Message::where(function ($query) use ($userId) {
            $query->where('sender_id', $userId)
                ->orWhere('receiver_id', $userId);
        })
            ->latest()
            ->get()
            ->unique('conversation_id')
            ->take(4);

        $data = $messages->map(function ($message) use ($userId) {

            $otherUserId = $message->sender_id == $userId
                ? $message->receiver_id
                : $message->sender_id;

            $user = User::find($otherUserId);

            return [
                'image' => $user->image,
                'name' => "{$user->name} {$user->last_name}" ?? 'Unknown',
                'message' => $message->message,
            ];
        });

        return response()->json([
            'recent_messages' => $data,
        ]);
    }

    public function chat(Request $request)
    {
        $userId = $request->user()->id;
        $search = $request->input('search');

        $latestMessages = Message::select('conversation_id', DB::raw('MAX(id) as last_id'))
            ->where(function ($q) use ($userId) {
                $q->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->groupBy('conversation_id');

        $messages = Message::with(['sender', 'receiver'])
            ->whereIn('id', $latestMessages->pluck('last_id'))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('message', 'like', "%{$search}%")
                        ->orWhereHas('sender', function ($q2) use ($search) {
                            $q2->where('name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('receiver', function ($q3) use ($search) {
                            $q3->where('name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->get();

        $unreadCounts = Message::where('receiver_id', $userId)
            ->whereNull('read_at')
            ->select('conversation_id', DB::raw('COUNT(*) as total'))
            ->groupBy('conversation_id')
            ->pluck('total', 'conversation_id');

        $conversations = $messages->map(function ($message) use ($userId, $unreadCounts) {

            $otherUser = $message->sender_id == $userId
                ? $message->receiver
                : $message->sender;

            return [
                'conversation_id' => $message->conversation_id,
                'image' => $otherUser->image,
                'name' => trim(($otherUser->name ?? '') . ' ' . ($otherUser->last_name ?? '')) ?: 'Unknown',
                'time' => $message->created_at->format('h:i A'),
                'last_message' => Str::limit($message->message, 50),
                'unread_count' => $unreadCounts[$message->conversation_id] ?? 0,
                // 'is_online' => $otherUser->is_online ?? false,
            ];
        });

        return response()->json([
            'success' => true,
            'conversations' => $conversations->values(),
        ]);
    }
}
