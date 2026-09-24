<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\PlanController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\GoogleAuthController;
use App\Http\Controllers\Api\Auth\VerifyRegistrationOtpController;
use App\Http\Controllers\Api\Provider\ProviderRegisterController;
use App\Http\Controllers\Api\Provider\ProviderStripeController;
use App\Http\Controllers\Api\Public\AllServiceController;
use App\Http\Controllers\Api\Public\BasicContentController;
use App\Http\Controllers\Api\Public\ProviderDirectoryController;
use App\Http\Controllers\Api\Public\SubscriberController;
use App\Http\Controllers\Api\User\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public & Guest Routes
|--------------------------------------------------------------------------
*/

// Authentication (Guest)
Route::post('/user-register', [AuthController::class, 'register']);
Route::post('/verify-email-otp', [VerifyRegistrationOtpController::class, 'verify']);
Route::post('/resend-email-otp', [VerifyRegistrationOtpController::class, 'resend']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'sendOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPasswordWithOtp']);

// Social Auth
Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);

// Provider Public Onboarding & Registration
Route::post('/register_provider', [ProviderRegisterController::class, 'store']);
Route::get('/register_provider/config', [ProviderRegisterController::class, 'subscriptionConfig']);

// Public Content & Discovery
Route::get('/home_response', [BasicContentController::class, 'home_response']);
Route::get('/faq', [BasicContentController::class, 'faq']);
Route::get('/privacy', [BasicContentController::class, 'privacy']);
Route::get('/services', [AllServiceController::class, 'index']);
Route::get('/services/{id}', [AllServiceController::class, 'show']);
Route::get('/plans', [PlanController::class, 'index']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/providers', [ProviderDirectoryController::class, 'index']);
Route::get('/providers/{id}', [ProviderDirectoryController::class, 'show']);

// Newsletter Subscription
Route::post('/subscriber', [SubscriberController::class, 'store']);

// Order Public Callbacks & Invoices
Route::get('/order/success/{orderId}', [OrderController::class, 'success']);
Route::get('/order/cancel/{orderId}', [OrderController::class, 'cancel']);
Route::get('/order/invoice/{orderId}', [OrderController::class, 'generateInvoice']);

// Provider Public Stripe Key
Route::get('/stripe/p-k/{service}', [ProviderStripeController::class, 'getPublicKey']);
