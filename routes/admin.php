<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\BrandController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\ContentController;
use App\Http\Controllers\Api\Admin\EmailController;
use App\Http\Controllers\Api\Admin\FaqCategoryController;
use App\Http\Controllers\Api\Admin\FaqController;
use App\Http\Controllers\Api\Admin\OrderManagementController;
use App\Http\Controllers\Api\Admin\PermissionController;
use App\Http\Controllers\Api\Admin\PlanController;
use App\Http\Controllers\Api\Admin\PromotionController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\SettingController;
use App\Http\Controllers\Api\Admin\SliderController;
use App\Http\Controllers\Api\Admin\StripeController;
use App\Http\Controllers\Api\Admin\SubcategoryController;
use App\Http\Controllers\Api\Admin\SubscriptionManagementController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Public\SubscriberController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes (Authenticated & role:admin)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:api', 'role:admin'])->prefix('admin')->group(function (): void {
    // Admin Account & Auth
    Route::get('/index', [AuthController::class, 'index']);
    Route::post('/register', [AuthController::class, 'adminregister']);
    Route::get('/edit/{id}', [AuthController::class, 'edit']);
    Route::post('/update/{id}', [AuthController::class, 'adminUpdate']);
    Route::delete('/delete/{id}', [AuthController::class, 'delete']);
    Route::get('/password/{id}', [AuthController::class, 'password']);

    // Role Management
    Route::prefix('role')->group(function (): void {
        Route::get('index', [RoleController::class, 'index']);
        Route::post('store', [RoleController::class, 'store']);
        Route::get('edit/{id}', [RoleController::class, 'edit']);
        Route::post('update/{id}', [RoleController::class, 'update']);
    });

    // Permission Management
    Route::prefix('permission')->group(function (): void {
        Route::get('index', [PermissionController::class, 'index']);
        Route::post('store', [PermissionController::class, 'store']);
        Route::get('edit/{id}', [PermissionController::class, 'edit']);
        Route::post('update/{id}', [PermissionController::class, 'update']);
        Route::delete('delete/{id}', [PermissionController::class, 'destroy']);
    });

    // Category Management
    Route::prefix('category')->group(function (): void {
        Route::get('index', [CategoryController::class, 'index']);
        Route::post('store', [CategoryController::class, 'store']);
        Route::get('edit/{id}', [CategoryController::class, 'edit']);
        Route::post('update/{id}', [CategoryController::class, 'update']);
        Route::delete('/delete/{id}', [CategoryController::class, 'destroy']);
    });

    // Plan Management
    Route::prefix('plan')->group(function (): void {
        Route::get('index', [PlanController::class, 'index']);
        Route::post('store', [PlanController::class, 'store']);
        Route::put('update/{id}', [PlanController::class, 'update']);
        Route::delete('delete/{id}', [PlanController::class, 'destroy']);
    });

    // Stripe Configuration
    Route::prefix('stripe')->group(function (): void {
        Route::post('upsert', [StripeController::class, 'upsert']);
        Route::get('show', [StripeController::class, 'show']);
    });

    // Subcategory Management
    Route::prefix('subcategory')->group(function (): void {
        Route::get('index', [SubcategoryController::class, 'index']);
        Route::post('store', [SubcategoryController::class, 'store']);
        Route::get('edit/{id}', [SubcategoryController::class, 'edit']);
        Route::post('update/{id}', [SubcategoryController::class, 'update']);
        Route::delete('/delete/{id}', [SubcategoryController::class, 'destroy']);
    });

    // Brand Management
    Route::prefix('brand')->group(function (): void {
        Route::get('index', [BrandController::class, 'index']);
        Route::post('store', [BrandController::class, 'store']);
        Route::get('edit/{id}', [BrandController::class, 'edit']);
        Route::post('update/{id}', [BrandController::class, 'update']);
        Route::delete('/delete/{id}', [BrandController::class, 'destroy']);
    });

    // Slider Management
    Route::prefix('slider')->group(function (): void {
        Route::get('index', [SliderController::class, 'index']);
        Route::post('store', [SliderController::class, 'store']);
        Route::get('edit/{id}', [SliderController::class, 'edit']);
        Route::post('update/{id}', [SliderController::class, 'update']);
        Route::delete('delete/{id}', [SliderController::class, 'destroy']);
    });

    // FAQ Categories
    Route::prefix('faq-categories')->group(function (): void {
        Route::get('index', [FaqCategoryController::class, 'index']);
        Route::post('store', [FaqCategoryController::class, 'store']);
        Route::get('edit/{id}', [FaqCategoryController::class, 'edit']);
        Route::post('update/{id}', [FaqCategoryController::class, 'update']);
        Route::delete('delete/{id}', [FaqCategoryController::class, 'destroy']);
    });

    // Promotions
    Route::prefix('promotions')->group(function (): void {
        Route::get('index', [PromotionController::class, 'index']);
        Route::post('store', [PromotionController::class, 'store']);
        Route::get('edit/{id}', [PromotionController::class, 'edit']);
        Route::post('update/{id}', [PromotionController::class, 'update']);
        Route::delete('delete/{id}', [PromotionController::class, 'destroy']);
    });

    // FAQ Management
    Route::prefix('faq')->group(function (): void {
        Route::get('index', [FaqController::class, 'index']);
        Route::post('store', [FaqController::class, 'store']);
        Route::get('edit/{id}', [FaqController::class, 'edit']);
        Route::post('update/{id}', [FaqController::class, 'update']);
        Route::delete('delete/{id}', [FaqController::class, 'destroy']);
    });

    // Content Management
    Route::prefix('content')->group(function (): void {
        Route::get('home_index', [ContentController::class, 'home_index']);
        Route::post('home_update', [ContentController::class, 'home_update']);
        Route::get('faq_index', [ContentController::class, 'faq_index']);
        Route::post('faq_store', [ContentController::class, 'faq_store']);
        Route::delete('faq_delete/{faq}', [ContentController::class, 'faq_delete']);
        Route::get('privacy_index', [ContentController::class, 'privacy_index']);
        Route::post('privacy_update', [ContentController::class, 'privacy_update']);
    });

    // Settings
    Route::prefix('setting')->group(function (): void {
        Route::get('index', [SettingController::class, 'index']);
        Route::post('update', [SettingController::class, 'update']);
    });

    // Mail
    Route::prefix('mail')->group(function (): void {
        Route::post('/send-email', [EmailController::class, 'sendEmail']);
    });

    // Subscribers List
    Route::prefix('subscriber')->group(function (): void {
        Route::get('index', [SubscriberController::class, 'index']);
    });

    // Subscriptions Management
    Route::prefix('subscriptions')->group(function (): void {
        Route::get('providers', [SubscriptionManagementController::class, 'index']);
        Route::get('providers/{providerId}', [SubscriptionManagementController::class, 'show']);
        Route::get('all', [SubscriptionManagementController::class, 'allSubscriptions']);
        Route::post('providers/{providerId}/cancel', [SubscriptionManagementController::class, 'cancel']);
        Route::post('providers/{providerId}/cancel-now', [SubscriptionManagementController::class, 'cancelNow']);
        Route::post('providers/{providerId}/pause', [SubscriptionManagementController::class, 'pause']);
        Route::post('providers/{providerId}/resume', [SubscriptionManagementController::class, 'resume']);
    });

    // Clients & Sellers Management
    Route::prefix('client')->group(function (): void {
        Route::get('index', [UserManagementController::class, 'clients']);
        Route::get('show-details/{id}', [UserManagementController::class, 'showDetails']);
        Route::get('seller-index', [UserManagementController::class, 'sellers']);
        Route::patch('change-status/{id}', [UserManagementController::class, 'changeStatus']);
        Route::delete('delete-user/{id}', [UserManagementController::class, 'deleteUser']);
    });

    // Admin Dashboard
    Route::prefix('admin-dashboard')->group(function (): void {
        Route::get('index', [AdminDashboardController::class, 'index']);
    });

    // Admin Orders
    Route::prefix('admin-order')->group(function (): void {
        Route::get('index', [OrderManagementController::class, 'index']);
        Route::get('show-details/{id}', [OrderManagementController::class, 'showOrderDetails']);
    });
});
