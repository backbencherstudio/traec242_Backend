<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ChatService
{
    public function __construct(
        protected FileUploadService $fileUploadService
    ) {}

    /**
     * Find or create conversation between two users.
     */
    public function getOrCreateConversation(int $userId1, int $userId2): Conversation
    {
        $conversation = Conversation::whereHas('users', fn ($q) => $q->where('user_id', $userId1))
            ->whereHas('users', fn ($q) => $q->where('user_id', $userId2))
            ->where('is_group', false)
            ->first();

        if (! $conversation) {
            $conversation = Conversation::create(['is_group' => false]);
            $conversation->users()->attach([$userId1, $userId2]);
        }

        return $conversation;
    }

    /**
     * Send a message within a conversation.
     *
     * @param  array<UploadedFile>|null  $files
     */
    public function sendMessage(int $senderId, int $receiverId, ?string $messageContent, ?array $files = null): Message
    {
        return DB::transaction(function () use ($senderId, $receiverId, $messageContent, $files) {
            $conversation = $this->getOrCreateConversation($senderId, $receiverId);

            $message = $conversation->messages()->create([
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'message' => $messageContent,
                'type' => ! empty($files) ? 'media' : 'text',
                'read_at' => null,
            ]);

            if (! empty($files)) {
                foreach ($files as $file) {
                    $path = $this->fileUploadService->upload($file, 'messenger/attachments');

                    $message->attachments()->create([
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            $conversation->touch();

            $loadedMessage = $message->load(['attachments', 'sender', 'receiver']);

            // Broadcast once to others
            broadcast(new MessageSent($loadedMessage))->toOthers();

            return $loadedMessage;
        });
    }

    /**
     * Mark messages between receiver and sender as read.
     */
    public function markAsRead(int $authUserId, int $senderId): int
    {
        return Message::where('sender_id', $senderId)
            ->where('receiver_id', $authUserId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
