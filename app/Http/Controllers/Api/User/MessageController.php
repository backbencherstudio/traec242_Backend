<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\ChatService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('user-message', weight: 2)]
class MessageController extends Controller
{
    public function __construct(
        protected ChatService $chatService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
        ]);

        $senderId = auth()->id();
        $receiverId = (int) $request->receiver_id;

        $conversation = Conversation::whereHas('users', fn ($q) => $q->where('user_id', $senderId))
            ->whereHas('users', fn ($q) => $q->where('user_id', $receiverId))
            ->where('is_group', false)
            ->first();

        if (! $conversation) {
            return response()->json([
                'status' => 'success',
                'data' => [],
                'message' => 'No conversation found',
            ]);
        }

        $messages = $conversation->messages()
            ->with([
                'attachments',
                'sender:id,name,image',
                'receiver:id,name,image',
            ])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => MessageResource::collection($messages),
        ]);
    }

    public function messageslist(): JsonResponse
    {
        $authId = auth()->id();

        $messages = Message::where('sender_id', $authId)
            ->orWhere('receiver_id', $authId)
            ->with([
                'sender:id,name,image',
                'receiver:id,name,image',
                'attachments',
            ])
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => MessageResource::collection($messages),
        ]);
    }

    public function sendMessage(SendMessageRequest $request): JsonResponse
    {
        $senderId = auth()->id();
        $receiverId = (int) $request->receiver_id;
        $files = $request->hasFile('files') ? $request->file('files') : null;

        $message = $this->chatService->sendMessage(
            $senderId,
            $receiverId,
            $request->message,
            $files
        );

        return response()->json([
            'status' => 'success',
            'data' => new MessageResource($message),
        ]);
    }
}
