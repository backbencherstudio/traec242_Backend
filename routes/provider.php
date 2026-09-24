<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Provider\ProviderProfileController;
use App\Http\Controllers\Api\Provider\ProviderStripeController;
use App\Http\Controllers\Api\Provider\ServiceController;
use App\Http\Controllers\Api\User\ReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Provider Routes (Authenticated)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:api'])->group(function (): void {
    // Provider Services
    Route::prefix('provider/services')->group(function (): void {
        Route::get('/', [ServiceController::class, 'index']);
        Route::get('/{id}', [ServiceController::class, 'show']);
        Route::post('/store', [ServiceController::class, 'store']);
        Route::post('/update/{service}', [ServiceController::class, 'update']);
    });
});

Route::middleware(['auth:api'])->prefix('admin')->group(function (): void {
    // Provider Stripe Configuration
    Route::prefix('p-stripe')->group(function (): void {
        Route::post('upsert', [ProviderStripeController::class, 'upsert']);
        Route::get('show', [ProviderStripeController::class, 'show']);
    });

    // Provider Profile
    Route::prefix('profile')->group(function (): void {
        Route::get('provider-profile', [ProviderProfileController::class, 'providerProfile']);
        Route::put('update-provider-profile', [ProviderProfileController::class, 'updateProviderProfile']);
    });

    // Provider Reviews & Replies
    Route::prefix('review')->group(function (): void {
        Route::get('received', [ReviewController::class, 'providerReviews']);
        Route::patch('reply/{id}', [ReviewController::class, 'reply']);
    });
});
