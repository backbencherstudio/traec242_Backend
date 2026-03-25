<?php

use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Api\ProviderRegisterController;
use Illuminate\Support\Facades\Route;

Route::post('register_provider', [ProviderRegisterController::class, 'store']);



Route::prefix('admin')->group(function () {
    Route::prefix('content')->group(function () {
        Route::get('index', [ContentController::class, 'index']);
        Route::post('update', [ContentController::class, 'update']);
    });
});
