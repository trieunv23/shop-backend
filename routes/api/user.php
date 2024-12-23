<?php

use App\Http\Controllers\User\AddressController;
use App\Http\Controllers\User\AuthController;
use App\Http\Controllers\User\BannerController;
use App\Http\Controllers\User\CartController;
use App\Http\Controllers\User\CategoryController;
use App\Http\Controllers\User\ColorController;
use App\Http\Controllers\User\OrderController;
use App\Http\Controllers\User\PaymentController;
use App\Http\Controllers\User\ProductController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\SizeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);

    Route::post('/username/check', [AuthController::class, 'checkUsername']);

    Route::post('/create', [AuthController::class, 'register']);

    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/status', [AuthController::class, 'checkAuth']);

    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::post('/email/send', [AuthController::class, 'sendEmailVerification']);

    Route::post('/email/verify', [AuthController::class, 'verifyEmail']);

    Route::post('/email/update', [AuthController::class, 'updateEmail']);

    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::prefix('user')->group(function () {
    Route::get('/profile', [ProfileController::class, 'showProfile']);

    Route::put('/profile', [ProfileController::class, 'updateProfile']);

    Route::get('/addresses', [AddressController::class, 'showAddresses']);

    Route::get('/addresses/default', [AddressController::class, 'showAddressDefault']);
    
    Route::get('/addresses/{id}', [AddressController::class, 'showAddress']);

    Route::post('/addresses', [AddressController::class, 'createAddress']);

    Route::patch('/addresses/{id}', [AddressController::class, 'updateAddress']);

    Route::delete('/addresses/{id}', [AddressController::class, 'deleteAddress']);

    Route::patch('/addresses/{id}/default', [AddressController::class, 'updateAddressDefault']);
});

Route::prefix('products')->group(function () {
    Route::get('/{id}', [ProductController::class, 'showProduct']);

    Route::get('/category/{filter}', [ProductController::class, 'showProducts']);
});

Route::prefix('sizes')->group(function () {
    Route::get('/', [SizeController::class, 'showSizes']);
});

Route::prefix('colors')->group(function () {
    Route::get('/', [ColorController::class, 'showColors']);
});

Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'showCart']);

    Route::post('/products', [CartController::class, 'addToCart']);

    Route::get('/products/count', [CartController::class, 'showCartProductCount']);

    Route::patch('/products/{id}', [CartController::class, 'updateCartProduct']);   

    Route::delete('/products/{id}', [CartController::class, 'deleteCartProduct']);
});

Route::prefix('orders')->group(function () {
    Route::post('/create', [OrderController::class, 'createOrder']);

    Route::get('/', [OrderController::class, 'showOrders']);

    Route::get('/{id}', [OrderController::class, 'showOrder']);

    Route::patch('/{id}/cancel', [OrderController::class, 'cancel']);

    Route::patch('/confirmed-receipt', [OrderController::class, 'confirmedReceiptOrsder']);
    
    Route::post('/{id}/confirm-payment', [PaymentController::class, 'confirmPayment']);

    Route::get('/{id}/payment', [PaymentController::class, 'showPayment']);
});

Route::prefix('banners')->group(function () {
    Route::get('/', [BannerController::class, 'showBanners']);
});

