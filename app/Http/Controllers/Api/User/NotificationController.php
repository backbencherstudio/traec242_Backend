<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\MarkChatAsReadRequest;
use App\Models\Message;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function __construct(
        protected ChatService $chatService
    ) {}

    public function getTotalUnreadCount(): JsonResponse
    {
        $userId = auth()->id();

        $totalUnread = Message::where('receiver_id', $userId)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'status' => 'success',
            'total_unread_count' => $totalUnread,
        ]);
    }

    public function getChatListWithUnreadCount(): JsonResponse
    {
        $userId = auth()->id();

        $unreadGrouped = Message::where('receiver_id', $userId)
            ->whereNull('read_at')
            ->select('sender_id', DB::raw('count(*) as unread_count'))
            ->groupBy('sender_id')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $unreadGrouped,
        ]);
    }

    public function markChatAsRead(MarkChatAsReadRequest $request): JsonResponse
    {
        $authUserId = (int) auth()->id();
        $senderId = (int) $request->sender_id;

        $updatedRows = $this->chatService->markAsRead($authUserId, $senderId);

        return response()->json([
            'status' => 'success',
            'message' => 'Messages marked as read',
            'read_messages_count' => $updatedRows,
        ]);
    }
}
