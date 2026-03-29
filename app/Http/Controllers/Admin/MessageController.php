<?php

namespace App\Http\Controllers\Admin;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
// use App\Models\Message;
use App\Models\Conversation;
// use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
        ]);

        $senderId = auth()->id();
        $receiverId = $request->receiver_id;

        // Find conversation
        $conversation = Conversation::whereHas('users', function ($q) use ($senderId) {
            $q->where('user_id', $senderId);
        })
            ->whereHas('users', function ($q) use ($receiverId) {
                $q->where('user_id', $receiverId);
            })
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
            ->with(['attachments', 'sender'])
            ->orderBy('created_at', 'asc')
            ->get()->map(function ($msg) {
                return [
                    'receiver_id' => $msg->receiver_id,
                    'sender_id'   => $msg->sender_id,
                    'message'     => $msg->message,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $messages,
        ]);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'nullable|string',
            'files.*' => 'nullable|file|max:5120',
        ]);

        $senderId = auth()->id();
        $receiverId = $request->receiver_id;

        return DB::transaction(function () use ($request, $senderId, $receiverId) {

            $conversation = Conversation::whereHas('users', function ($q) use ($senderId) {
                $q->where('user_id', $senderId);
            })
                ->whereHas('users', function ($q) use ($receiverId) {
                    $q->where('user_id', $receiverId);
                })
                ->where('is_group', false)
                ->first();

            if (! $conversation) {
                $conversation = Conversation::create([
                    'is_group' => false,
                ]);

                $conversation->users()->attach([$senderId, $receiverId]);
            }

            $message = $conversation->messages()->create([
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'message' => $request->message,
                'type' => $request->hasFile('files') ? 'media' : 'text',
                'read_at' => null, // unread
            ]);

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $path = $file->store('messenger/attachments', 'public');

                    $message->attachments()->create([
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            $conversation->touch();

            broadcast(new MessageSent(
                $message->load(['attachments', 'sender'])
            ))->toOthers();

            return response()->json([
                'status' => 'success',
                'data' => $message->load(['attachments', 'sender']),
            ]);
        });
    }
}
