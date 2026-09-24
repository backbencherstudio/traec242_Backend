<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserDashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function summary(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $summary = $this->dashboardService->getUserSummary($userId);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    public function recentOrders(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $orders = $this->dashboardService->getUserRecentOrders($userId);

        $recentOrders = $orders->map(fn ($order) => [
            'event_name' => $order->event_name,
            'order_by' => trim(
                ($order->service?->user?->name ?? '').' '.
                ($order->service?->user?->last_name ?? '')
            ),
            'date' => Carbon::parse($order->event_start_date)->format('M d, Y'),
            'status' => ucfirst($order->status),
        ]);

        return response()->json([
            'success' => true,
            'data' => $recentOrders,
        ]);
    }

    public function recentActivity(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $activities = $this->dashboardService->getUserRecentActivity($userId);

        return response()->json([
            'success' => true,
            'recent_activity' => $activities,
        ]);
    }

    public function recentMessages(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        $messages = Message::where(function ($query) use ($userId): void {
            $query->where('sender_id', $userId)
                ->orWhere('receiver_id', $userId);
        })
            ->with(['sender', 'receiver'])
            ->latest()
            ->get()
            ->unique('conversation_id')
            ->take(4);

        $data = $messages->map(function ($message) use ($userId): array {
            $otherUser = (int) $message->sender_id === $userId
                ? $message->receiver
                : $message->sender;

            return [
                'image' => $otherUser?->image,
                'name' => $otherUser ? trim("{$otherUser->name} {$otherUser->last_name}") : 'Unknown',
                'message' => $message->message,
            ];
        });

        return response()->json([
            'recent_messages' => $data,
        ]);
    }

    public function chat(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $search = $request->input('search');

        $latestMessages = Message::select('conversation_id', DB::raw('MAX(id) as last_id'))
            ->where(function ($q) use ($userId): void {
                $q->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->groupBy('conversation_id');

        $messages = Message::with(['sender', 'receiver'])
            ->whereIn('id', $latestMessages->pluck('last_id'))
            ->when($search, function ($query) use ($search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('message', 'like', "%{$search}%")
                        ->orWhereHas('sender', function ($q2) use ($search): void {
                            $q2->where('name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('receiver', function ($q3) use ($search): void {
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

        $conversations = $messages->map(function ($message) use ($userId, $unreadCounts): array {
            $otherUser = (int) $message->sender_id === $userId
                ? $message->receiver
                : $message->sender;

            return [
                'conversation_id' => $message->conversation_id,
                'image' => $otherUser?->image,
                'name' => $otherUser ? trim(($otherUser->name ?? '').' '.($otherUser->last_name ?? '')) : 'Unknown',
                'time' => $message->created_at->format('h:i A'),
                'last_message' => Str::limit($message->message, 50),
                'unread_count' => $unreadCounts[$message->conversation_id] ?? 0,
            ];
        });

        return response()->json([
            'success' => true,
            'conversations' => $conversations->values(),
        ]);
    }
}
