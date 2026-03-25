<?php

use App\Http\Controllers\Api\ProviderRegisterController;
use Illuminate\Support\Facades\Route;

Route::post('register_provider', [ProviderRegisterController::class, 'store']);
