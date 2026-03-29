<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\FaqCategoryController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\SubcategoryController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Frontend\SubscriberController;
use App\Http\Controllers\Frontend\OrderController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

// Admin Public Routes
// Route::post('/admin/login', [AdminController::class, 'login'])->name('admin.login');
Route::get('index', [CategoryController::class, 'index'])->name('admin.category.index');
// user login
Route::post('/user-register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/forgot-password', [AuthController::class, 'sendOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPasswordWithOtp']);

Route::post('/subscriber', [SubscriberController::class, 'store'])->name('subscriber.store');
// Route::middleware('auth:api')->post('/user/logout', [UserController::class, 'logout']);

// Route::post('/admin/register', [AuthController::class, 'adminregister'])->name('register');

// google login api
Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);

// Admin Protected Routes
Route::middleware(['auth:api'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/index', [AuthController::class, 'index'])->name('index');
    Route::post('/register', [AuthController::class, 'adminregister'])->name('register');
    Route::get('/edit/{id}', [AuthController::class, 'edit'])->name('edit');
    Route::post('/update/{id}', [AuthController::class, 'adminUpdate'])->name('update');
    Route::delete('/delete/{id}', [AuthController::class, 'delete'])->name('delete');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/password/{id}', [AuthController::class, 'password'])->name('password');
    Route::post('/passwordchange', [AuthController::class, 'passwordchange']);

    // Role
    Route::prefix('role')->group(function () {
        Route::get('index', [RoleController::class, 'index'])->name('role.index');
        Route::post('store', [RoleController::class, 'store'])->name('role.store');
        Route::get('edit/{id}', [RoleController::class, 'edit'])->name('role.edit');
        Route::post('update/{id}', [RoleController::class, 'update'])->name('role.update');
    });
    // permission
    Route::prefix('permission')->group(function () {
        Route::get('index', [PermissionController::class, 'index'])->name('permission.index');
        Route::post('store', [PermissionController::class, 'store'])->name('permission.store');
        Route::get('edit/{id}', [PermissionController::class, 'edit'])->name('permission.edit');
        Route::post('update/{id}', [PermissionController::class, 'update'])->name('permission.update');
        Route::delete('delete/{id}', [PermissionController::class, 'destroy'])->name('permission.destroy');
    });
    // category
    Route::prefix('category')->group(function () {
        Route::get('index', [CategoryController::class, 'index'])->name('category.index');
        Route::post('store', [CategoryController::class, 'store'])->name('category.store');
        Route::get('edit/{id}', [CategoryController::class, 'edit'])->name('category.edit');
        Route::post('update/{id}', [CategoryController::class, 'update'])->name('category.update');
        Route::delete('/delete/{id}', [CategoryController::class, 'destroy'])->name('category.destroy');
    });
    // subcategory
    Route::prefix('subcategory')->group(function () {
        Route::get('index', [SubcategoryController::class, 'index'])->name('subcategory.index');
        Route::post('store', [SubcategoryController::class, 'store'])->name('subcategory.store');
        Route::get('edit/{id}', [SubcategoryController::class, 'edit'])->name('subcategory.edit');
        Route::post('update/{id}', [SubcategoryController::class, 'update'])->name('subcategory.update');
        Route::delete('/delete/{id}', [SubcategoryController::class, 'destroy'])->name('subcategory.destroy');
    });

    // Brand
    Route::prefix('brand')->group(function () {
        Route::get('index', [BrandController::class, 'index'])->name('brand.index');
        Route::post('store', [BrandController::class, 'store'])->name('brand.store');
        Route::get('edit/{id}', [BrandController::class, 'edit'])->name('brand.edit');
        Route::post('update/{id}', [BrandController::class, 'update'])->name('brand.update');
        Route::delete('/delete/{id}', [BrandController::class, 'destroy'])->name('brand.destroy');
    });

    // Slider
    Route::prefix('slider')->group(function () {
        Route::get('index', [SliderController::class, 'index'])->name('slider.index');
        Route::post('store', [SliderController::class, 'store'])->name('slider.store');
        Route::get('edit/{id}', [SliderController::class, 'edit'])->name('slider.edit');
        Route::post('update/{id}', [SliderController::class, 'update'])->name('slider.update');
        Route::delete('delete/{id}', [SliderController::class, 'destroy'])->name('slider.destroy');
    });

    // faq-category
    Route::prefix('faq-categories')->group(function () {
        Route::get('index', [FaqCategoryController::class, 'index'])->name('faq-categories.index');
        Route::post('store', [FaqCategoryController::class, 'store'])->name('faq-categories.store');
        Route::get('edit/{id}', [FaqCategoryController::class, 'edit'])->name('faq-categories.edit');
        Route::post('update/{id}', [FaqCategoryController::class, 'update'])->name('faq-categories.update');
        Route::delete('delete/{id}', [FaqCategoryController::class, 'destroy'])->name('faq-categories.destroy');
    });

    // faq
    Route::prefix('faq')->group(function () {
        Route::get('index', [FaqController::class, 'index'])->name('faq.index');
        Route::post('store', [FaqController::class, 'store'])->name('faq.store');
        Route::get('edit/{id}', [FaqController::class, 'edit'])->name('faq.edit');
        Route::post('update/{id}', [FaqController::class, 'update'])->name('faq.update');
        Route::delete('delete/{id}', [FaqController::class, 'destroy'])->name('faq.destroy');
    });



    // setting
    Route::prefix('setting')->group(function () {
        Route::get('index', [SettingController::class, 'index'])->name('setting.index');
        Route::post('update', [SettingController::class, 'update'])->name('setting.update');
    });
    // notification
    Route::prefix('notification')->group(function () {
        Route::post('/send-notification', [NotificationController::class, 'sendNotification'])->name('notification.store');
    });

    // mail
    Route::prefix('mail')->group(function () {
        Route::post('/send-email', [EmailController::class, 'sendEmail']);
    });

    //Subscriber
    Route::prefix('subscriber')->group(function () {
        Route::get('index', [SubscriberController::class, 'index']);
    });

    //Booking
    Route::prefix('order')->group(function () {
        Route::post('/create-order', [OrderController::class, 'store']);
    });
});

Route::get('/order/success/{orderId}', [OrderController::class, 'success'])->name('order.success');
Route::get('/order/cancel/{orderId}', [OrderController::class, 'cancel'])->name('order.cancel');
Route::get('/order/invoice/{orderId}', [OrderController::class, 'generateInvoice'])->name('order.invoice');

// Shanto

Route::middleware('auth:admin')->get('/user-data', [AuthController::class, 'apiData']);

require __DIR__ . '/mahmudul.php';
