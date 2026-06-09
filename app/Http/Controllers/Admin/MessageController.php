<?php

namespace App\Http\Controllers\Admin;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Message;
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


        $conversation = Conversation::whereHas('users', function ($q) use ($senderId) {
                $q->where('user_id', $senderId);
            })
            ->whereHas('users', function ($q) use ($receiverId) {
                $q->where('user_id', $receiverId);
            })
            ->where('is_group', false)
            ->first();


        if (!$conversation) {
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
                'receiver:id,name,image'
            ])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,

                    'sender' => [
                        'id'    => $msg->sender?->id,
                        'name'  => $msg->sender?->name,
                        'image' => $msg->sender?->image,
                    ],

                    'receiver' => [
                        'id'    => $msg->receiver?->id,
                        'name'  => $msg->receiver?->name,
                        'image' => $msg->receiver?->image,
                    ],

                    'message' => $msg->message,

                    'attachments' => $msg->attachments->map(function ($file) {
                        return [
                            'id'         => $file->id,
                            'file_name'  => $file->file_name,
                            'file_type'  => $file->file_type,
                            'file_size'  => $file->file_size,
                            'file_url'   => $file->file_path ? asset('storage/' . $file->file_path) : null,
                        ];
                    }),

                    'created_at' => $msg->created_at->format('Y-m-d H:i:s'),
                ];
    });

    return response()->json([
        'status' => 'success',
        'data' => $messages,
    ]);
    }

    public function messageslist()
    {
        $messages = Message::where('sender_id', auth()->id())
            ->orWhere('receiver_id', auth()->id())
            ->with([
                'sender:id,name,image',
                'attachments'
            ])
            ->get()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'message' => $msg->message,
                    'sender_id' => $msg->sender_id,
                    'receiver_id' => $msg->receiver_id,
                    'created_at' => $msg->created_at,

                    'sender' => [
                        'id' => $msg->sender?->id,
                        'name' => $msg->sender?->name,
                        'image' => $msg->sender?->image,
                    ],

                    'attachments' => $msg->attachments->map(function ($file) {
                        return [
                            'id'        => $file->id,
                            'file_name' => $file->file_name,
                            'file_type' => $file->file_type,
                            'file_size' => $file->file_size,
                            'file_url'  => $file->file_path
                                ? asset('storage/' . $file->file_path)
                                : null,
                        ];
                    }),
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

            event(new \App\Events\MessageSent($message));

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
