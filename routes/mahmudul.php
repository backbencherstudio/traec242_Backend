<?php

use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Api\ProviderRegisterController;
use App\Http\Controllers\Frontend\BasicContentController;
use App\Http\Controllers\Provider\ServiceController;
use Illuminate\Support\Facades\Route;

Route::post('register_provider', [ProviderRegisterController::class, 'store']);
Route::get('home_response', [BasicContentController::class, 'home_response']);
Route::get('faq', [BasicContentController::class, 'faq']);
Route::get('privacy', [BasicContentController::class, 'privacy']);


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
    Route::post('provider/services/store', [ServiceController::class, 'store']);
});
