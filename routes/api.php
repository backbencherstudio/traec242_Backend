<?php

use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\BrandController;
use App\Http\Controllers\Api\Admin\CategoryController;
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
use App\Http\Controllers\Api\Auth\GoogleAuthController;
use App\Http\Controllers\Api\Auth\VerifyRegistrationOtpController;
use App\Http\Controllers\Api\Provider\ProviderProfileController;
use App\Http\Controllers\Api\Provider\ProviderStripeController;
use App\Http\Controllers\Api\Public\ProviderDirectoryController;
use App\Http\Controllers\Api\Public\SubscriberController;
use App\Http\Controllers\Api\User\MessageController;
use App\Http\Controllers\Api\User\NotificationController;
use App\Http\Controllers\Api\User\OrderController;
use App\Http\Controllers\Api\User\ReviewController;
use App\Http\Controllers\Api\User\UserDashboardController;
use App\Http\Controllers\Api\User\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::post('/user-register', [AuthController::class, 'register']);
Route::post('/verify-email-otp', [VerifyRegistrationOtpController::class, 'verify']);
Route::post('/resend-email-otp', [VerifyRegistrationOtpController::class, 'resend']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'sendOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPasswordWithOtp']);

Route::post('/subscriber', [SubscriberController::class, 'store'])->name('subscriber.store');

Route::get('/providers', [ProviderDirectoryController::class, 'index']);
Route::get('/providers/{id}', [ProviderDirectoryController::class, 'show']);

Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);

Route::middleware(['auth:api'])->group(function () {
    Route::get('/me', [AuthController::class, 'me'])->name('me');
    Route::prefix('profile')->group(function () {
        Route::put('update', [UserProfileController::class, 'update']);
        Route::post('passwordchange', [AuthController::class, 'passwordchange']);
    });
});

Route::middleware(['auth:api'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/index', [AuthController::class, 'index'])->name('index');
    Route::post('/register', [AuthController::class, 'adminregister'])->name('register');
    Route::get('/edit/{id}', [AuthController::class, 'edit'])->name('edit');
    Route::post('/update/{id}', [AuthController::class, 'adminUpdate'])->name('update');
    Route::delete('/delete/{id}', [AuthController::class, 'delete'])->name('delete');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/password/{id}', [AuthController::class, 'password'])->name('password');

    Route::prefix('role')->group(function () {
        Route::get('index', [RoleController::class, 'index'])->name('role.index');
        Route::post('store', [RoleController::class, 'store'])->name('role.store');
        Route::get('edit/{id}', [RoleController::class, 'edit'])->name('role.edit');
        Route::post('update/{id}', [RoleController::class, 'update'])->name('role.update');
    });

    Route::prefix('permission')->group(function () {
        Route::get('index', [PermissionController::class, 'index'])->name('permission.index');
        Route::post('store', [PermissionController::class, 'store'])->name('permission.store');
        Route::get('edit/{id}', [PermissionController::class, 'edit'])->name('permission.edit');
        Route::post('update/{id}', [PermissionController::class, 'update'])->name('permission.update');
        Route::delete('delete/{id}', [PermissionController::class, 'destroy'])->name('permission.destroy');
    });

    Route::prefix('category')->group(function () {
        Route::get('index', [CategoryController::class, 'index'])->name('category.index');
        Route::post('store', [CategoryController::class, 'store'])->name('category.store');
        Route::get('edit/{id}', [CategoryController::class, 'edit'])->name('category.edit');
        Route::post('update/{id}', [CategoryController::class, 'update'])->name('category.update');
        Route::delete('/delete/{id}', [CategoryController::class, 'destroy'])->name('category.destroy');
    });

    Route::prefix('plan')->group(function () {
        Route::get('index', [PlanController::class, 'index'])->name('plan.index');
        Route::post('store', [PlanController::class, 'store'])->name('plan.store');
        Route::put('update/{id}', [PlanController::class, 'update'])->name('plan.update');
        Route::delete('delete/{id}', [PlanController::class, 'destroy'])->name('plan.destroy');
    });

    Route::prefix('stripe')->group(function () {
        Route::post('upsert', [StripeController::class, 'upsert'])->name('stripe.upsert');
        Route::get('show', [StripeController::class, 'show'])->name('stripe.show');
    });

    Route::prefix('p-stripe')->group(function () {
        Route::post('upsert', [ProviderStripeController::class, 'upsert'])->name('p-stripe.upsert');
        Route::get('show', [ProviderStripeController::class, 'show'])->name('p-stripe.show');
    });

    Route::prefix('subcategory')->group(function () {
        Route::get('index', [SubcategoryController::class, 'index'])->name('subcategory.index');
        Route::post('store', [SubcategoryController::class, 'store'])->name('subcategory.store');
        Route::get('edit/{id}', [SubcategoryController::class, 'edit'])->name('subcategory.edit');
        Route::post('update/{id}', [SubcategoryController::class, 'update'])->name('subcategory.update');
        Route::delete('/delete/{id}', [SubcategoryController::class, 'destroy'])->name('subcategory.destroy');
    });

    Route::prefix('brand')->group(function () {
        Route::get('index', [BrandController::class, 'index'])->name('brand.index');
        Route::post('store', [BrandController::class, 'store'])->name('brand.store');
        Route::get('edit/{id}', [BrandController::class, 'edit'])->name('brand.edit');
        Route::post('update/{id}', [BrandController::class, 'update'])->name('brand.update');
        Route::delete('/delete/{id}', [BrandController::class, 'destroy'])->name('brand.destroy');
    });

    Route::prefix('slider')->group(function () {
        Route::get('index', [SliderController::class, 'index'])->name('slider.index');
        Route::post('store', [SliderController::class, 'store'])->name('slider.store');
        Route::get('edit/{id}', [SliderController::class, 'edit'])->name('slider.edit');
        Route::post('update/{id}', [SliderController::class, 'update'])->name('slider.update');
        Route::delete('delete/{id}', [SliderController::class, 'destroy'])->name('slider.destroy');
    });

    Route::prefix('faq-categories')->group(function () {
        Route::get('index', [FaqCategoryController::class, 'index'])->name('faq-categories.index');
        Route::post('store', [FaqCategoryController::class, 'store'])->name('faq-categories.store');
        Route::get('edit/{id}', [FaqCategoryController::class, 'edit'])->name('faq-categories.edit');
        Route::post('update/{id}', [FaqCategoryController::class, 'update'])->name('faq-categories.update');
        Route::delete('delete/{id}', [FaqCategoryController::class, 'destroy'])->name('faq-categories.destroy');
    });

    Route::prefix('promotions')->group(function () {
        Route::get('index', [PromotionController::class, 'index'])->name('promotions.index');
        Route::post('store', [PromotionController::class, 'store'])->name('promotions.store');
        Route::get('edit/{id}', [PromotionController::class, 'edit'])->name('promotions.edit');
        Route::post('update/{id}', [PromotionController::class, 'update'])->name('promotions.update');
        Route::delete('delete/{id}', [PromotionController::class, 'destroy'])->name('promotions.destroy');
    });

    Route::prefix('faq')->group(function () {
        Route::get('index', [FaqController::class, 'index'])->name('faq.index');
        Route::post('store', [FaqController::class, 'store'])->name('faq.store');
        Route::get('edit/{id}', [FaqController::class, 'edit'])->name('faq.edit');
        Route::post('update/{id}', [FaqController::class, 'update'])->name('faq.update');
        Route::delete('delete/{id}', [FaqController::class, 'destroy'])->name('faq.destroy');
    });

    Route::prefix('setting')->group(function () {
        Route::get('index', [SettingController::class, 'index'])->name('setting.index');
        Route::post('update', [SettingController::class, 'update'])->name('setting.update');
    });

    Route::prefix('notification')->group(function () {
        Route::get('/unread-count', [NotificationController::class, 'getTotalUnreadCount']);
        Route::get('/chat-unread-count', [NotificationController::class, 'getChatListWithUnreadCount']);
        Route::post('/read', [NotificationController::class, 'markChatAsRead']);
    });

    Route::prefix('mail')->group(function () {
        Route::post('/send-email', [EmailController::class, 'sendEmail']);
    });

    Route::prefix('subscriber')->group(function () {
        Route::get('index', [SubscriberController::class, 'index']);
    });

    Route::prefix('message')->group(function () {
        Route::get('index', [MessageController::class, 'index']);
        Route::get('sms-list', [MessageController::class, 'messageslist']);
        Route::post('send', [MessageController::class, 'sendMessage']);
    });

    Route::prefix('order')->group(function () {
        Route::post('/create-order', [OrderController::class, 'store']);
        Route::get('index', [OrderController::class, 'index'])->name('order.index');
        Route::get('show/{id}', [OrderController::class, 'show'])->name('order.show');
        Route::patch('update-status/{id}', [OrderController::class, 'updateStatus'])->name('order.updateStatus');
    });

    Route::prefix('profile')->group(function () {
        Route::get('provider-profile', [ProviderProfileController::class, 'providerProfile']);
        Route::put('update-provider-profile', [ProviderProfileController::class, 'updateProviderProfile']);
    });

    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('providers', [SubscriptionManagementController::class, 'index'])->name('providers');
        Route::get('providers/{providerId}', [SubscriptionManagementController::class, 'show'])->name('show');
        Route::get('all', [SubscriptionManagementController::class, 'allSubscriptions'])->name('all');
        Route::post('providers/{providerId}/cancel', [SubscriptionManagementController::class, 'cancel'])->name('cancel');
        Route::post('providers/{providerId}/cancel-now', [SubscriptionManagementController::class, 'cancelNow'])->name('cancel-now');
        Route::post('providers/{providerId}/pause', [SubscriptionManagementController::class, 'pause'])->name('pause');
        Route::post('providers/{providerId}/resume', [SubscriptionManagementController::class, 'resume'])->name('resume');
    });

    Route::prefix('client')->group(function () {
        Route::get('index', [UserManagementController::class, 'clients']);
        Route::get('show-details/{id}', [UserManagementController::class, 'showDetails']);
        Route::get('seller-index', [UserManagementController::class, 'sellers']);
        Route::patch('change-status/{id}', [UserManagementController::class, 'changeStatus']);
        Route::delete('delete-user/{id}', [UserManagementController::class, 'deleteUser']);
    });

    Route::prefix('admin-dashboard')->group(function () {
        Route::get('index', [AdminDashboardController::class, 'index']);
    });

    Route::prefix('admin-order')->group(function () {
        Route::get('index', [OrderManagementController::class, 'index']);
        Route::get('show-details/{id}', [OrderManagementController::class, 'showOrderDetails']);
    });

    Route::prefix('user-dashboard')->group(function () {
        Route::get('summary', [UserDashboardController::class, 'summary']);
        Route::get('recent-orders', [UserDashboardController::class, 'recentOrders']);
        Route::get('recent-activity', [UserDashboardController::class, 'recentActivity']);
        Route::get('recent-message', [UserDashboardController::class, 'recentMessages']);
        Route::get('chat-list', [UserDashboardController::class, 'chat']);
    });

    Route::prefix('review')->group(function () {
        Route::get('index', [ReviewController::class, 'index']);
        Route::get('received', [ReviewController::class, 'providerReviews']);
        Route::get('show/{id}', [ReviewController::class, 'show']);
        Route::post('store', [ReviewController::class, 'store']);
        Route::patch('reply/{id}', [ReviewController::class, 'reply']);
    });
});

Route::get('/order/success/{orderId}', [OrderController::class, 'success'])->name('order.success');
Route::get('/order/cancel/{orderId}', [OrderController::class, 'cancel'])->name('order.cancel');
Route::get('/order/invoice/{orderId}', [OrderController::class, 'generateInvoice'])->name('order.invoice');

Route::get('stripe/p-k/{service}', [ProviderStripeController::class, 'getPublicKey'])->name('p-stripe.getPublicKey');

require __DIR__.'/mahmudul.php';
