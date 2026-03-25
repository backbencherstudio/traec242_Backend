<?php

use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Api\ProviderRegisterController;
use Illuminate\Support\Facades\Route;

Route::post('register_provider', [ProviderRegisterController::class, 'store']);

Route::prefix('admin')->group(function () {
    Route::prefix('content')->group(function () {
        Route::get('home_index', [ContentController::class, 'home_index']);
        Route::post('home_update', [ContentController::class, 'home_update']);



        Route::get('faq_index', [ContentController::class, 'faq_index']);
        Route::post('faq_store', [ContentController::class, 'faq_store']);
        Route::delete('faq_delete/{faq}', [ContentController::class, 'faq_delete']);
    });
});
