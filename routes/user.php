<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\User\MessageController;
use App\Http\Controllers\Api\User\NotificationController;
use App\Http\Controllers\Api\User\OrderController;
use App\Http\Controllers\Api\User\ReviewController;
use App\Http\Controllers\Api\User\UserDashboardController;
use App\Http\Controllers\Api\User\UserProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User / Client Routes (Authenticated)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:api'])->group(function (): void {
    // Current user profile
    Route::get('/me', [AuthController::class, 'me']);

    Route::prefix('profile')->group(function (): void {
        Route::put('update', [UserProfileController::class, 'update']);
        Route::post('passwordchange', [AuthController::class, 'passwordchange']);
    });
});

Route::middleware(['auth:api'])->prefix('admin')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('notification')->group(function (): void {
        Route::get('/unread-count', [NotificationController::class, 'getTotalUnreadCount']);
        Route::get('/chat-unread-count', [NotificationController::class, 'getChatListWithUnreadCount']);
        Route::post('/read', [NotificationController::class, 'markChatAsRead']);
    });

    Route::prefix('message')->group(function (): void {
        Route::get('index', [MessageController::class, 'index']);
        Route::get('sms-list', [MessageController::class, 'messageslist']);
        Route::post('send', [MessageController::class, 'sendMessage']);
    });

    Route::prefix('order')->group(function (): void {
        Route::post('/create-order', [OrderController::class, 'store']);
        Route::get('index', [OrderController::class, 'index']);
        Route::get('show/{id}', [OrderController::class, 'show']);
        Route::patch('update-status/{id}', [OrderController::class, 'updateStatus']);
    });

    Route::prefix('user-dashboard')->group(function (): void {
        Route::get('summary', [UserDashboardController::class, 'summary']);
        Route::get('recent-orders', [UserDashboardController::class, 'recentOrders']);
        Route::get('recent-activity', [UserDashboardController::class, 'recentActivity']);
        Route::get('recent-message', [UserDashboardController::class, 'recentMessages']);
        Route::get('chat-list', [UserDashboardController::class, 'chat']);
    });

    Route::prefix('review')->group(function (): void {
        Route::get('index', [ReviewController::class, 'index']);
        Route::get('show/{id}', [ReviewController::class, 'show']);
        Route::post('store', [ReviewController::class, 'store']);
    });
});
