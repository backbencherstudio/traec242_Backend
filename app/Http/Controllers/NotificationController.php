<?php

namespace App\Http\Controllers;

use App\Models\Message;
use DB;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function getTotalUnreadCount()
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

    public function getChatListWithUnreadCount()
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


    public function markChatAsRead(Request $request)
    {
        $request->validate([
            'sender_id' => 'required|exists:users,id',
        ]);

        $authUserId = auth()->id();

        $updatedRows = Message::where('sender_id', $request->sender_id)
            ->where('receiver_id', $authUserId)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Messages marked as read',
            'read_messages_count' => $updatedRows,
        ]);
    }
}
