<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ColorController;
use App\Http\Controllers\Admin\SizeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('login', [AdminController::class, 'login']);

Route::post('register', [AdminController::class, 'create']);

Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);

    Route::post('/{id}/update', [UserController::class, 'updateUser']);
});

Route::prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'showOrders']);

    Route::get('/{id}', [OrderController::class, 'showOrder']);

    Route::patch('/{id}/confirm', [OrderController::class, 'confirm']);

    Route::patch('/{id}/processing', [OrderController::class, 'processing']);

    Route::patch('/{id}/ship', [OrderController::class, 'ship']);

    Route::patch('/{id}/delivery', [OrderController::class, 'delivery']);

    Route::patch('/{id}/cancel', [OrderController::class, 'cancel']);

    Route::patch('/{id}/confirm-payment', [PaymentController::class, 'confirm']);

    Route::patch('/{id}/refuse-payment', [PaymentController::class, 'refuse']);

    Route::patch('/{id}/rollback', [OrderController::class, 'rollBack']);
});

Route::prefix('payments')->group(function () {
    Route::get('/', [PaymentController::class, 'showPayments']);
});

Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'showProducts']);

    Route::get('/{id}', [ProductController::class, 'showProduct']);

    Route::post('/create', [ProductController::class, 'createProduct']);

    Route::post('/{id}/update', [ProductController::class, 'updateProduct']);

    Route::delete('/{id}/delete', [ProductController::class, 'deleteProduct']);
});

Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'showCategories']);
});

Route::prefix('colors')->group(function () {
    Route::get('/', [ColorController::class, 'showColors']);
});

Route::prefix('sizes')->group(function () {
    Route::get('/', [SizeController::class, 'showSizes']);
});

Route::prefix('banners')->group(function () {
    Route::get('/', [BannerController::class, 'showBanners']);

    Route::post('/create', [BannerController::class, 'createBanner']);

    Route::post('/{id}/update', [BannerController::class, 'updateBanner']);

    Route::post('/update', [BannerController::class, 'updateBanners']);
});
