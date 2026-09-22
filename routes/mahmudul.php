<?php

use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\ContentController;
use App\Http\Controllers\Api\Admin\PlanController;
use App\Http\Controllers\Api\Provider\ProviderRegisterController;
use App\Http\Controllers\Api\Provider\ServiceController;
use App\Http\Controllers\Api\Public\AllServiceController;
use App\Http\Controllers\Api\Public\BasicContentController;
use Illuminate\Support\Facades\Route;

Route::post('register_provider', [ProviderRegisterController::class, 'store']);
Route::get('register_provider/config', [ProviderRegisterController::class, 'subscriptionConfig']);
Route::get('home_response', [BasicContentController::class, 'home_response']);
Route::get('faq', [BasicContentController::class, 'faq']);
Route::get('privacy', [BasicContentController::class, 'privacy']);
Route::get('services', [AllServiceController::class, 'index']);
Route::get('services/{id}', [AllServiceController::class, 'show']);
Route::get('plans', [PlanController::class, 'index']);
Route::get('categories', [CategoryController::class, 'index']);

Route::prefix('admin')->group(function () {
    Route::prefix('content')->group(function () {
        Route::get('home_index', [ContentController::class, 'home_index']);
        Route::post('home_update', [ContentController::class, 'home_update']);

        Route::get('faq_index', [ContentController::class, 'faq_index']);
        Route::post('faq_store', [ContentController::class, 'faq_store']);
        Route::delete('faq_delete/{faq}', [ContentController::class, 'faq_delete']);

        Route::get('privacy_index', [ContentController::class, 'privacy_index']);
        Route::post('privacy_update', [ContentController::class, 'privacy_update']);
    });
});

Route::middleware('auth:api')->group(function () {
    Route::get('provider/services', [ServiceController::class, 'index']);
    Route::get('provider/services/{id}', [ServiceController::class, 'show']);
    Route::post('provider/services/store', [ServiceController::class, 'store']);
    Route::post('provider/services/update/{service}', [ServiceController::class, 'update']);
});
