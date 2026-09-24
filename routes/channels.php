<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('conversation.{conversationId}', fn ($user, $conversationId): bool => $user !== null);

Broadcast::channel('notifications.{userId}', fn ($user, $userId): bool => (int) $user->id === (int) $userId);
