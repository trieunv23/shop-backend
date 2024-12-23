<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\User\UserController as UserUserController;
// use App\Http\Controllers\User\AddressController;
use App\Http\Controllers\User\AuthController;
use App\Http\Controllers\User\CartController;
use App\Http\Controllers\User\CategoryController;   
use App\Http\Controllers\User\ColorController;
use App\Http\Controllers\User\OrderController;
use App\Http\Controllers\User\PaymentController;
use App\Http\Controllers\User\ProductController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\SizeController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// User

Route::prefix('users')->group(function () {
    Route::get('/profile', [ProfileController::class, 'showProfile']);

    Route::put('/profile', [ProfileController::class, 'updateProfile']);


    Route::get('address', [AddressController::class, 'getAddresses']);

    Route::put('update-address', [AddressController::class, 'updateAddress']);

    Route::get('/get-address/{id}', [AddressController::class, 'getAddress']);

    Route::post('/create-address', [AddressController::class, 'createAddress']);

    Route::post('/update-default-address', [AddressController::class, 'updateAddressDefault']);

    Route::post('/delete-address', [AddressController::class, 'deleteAddress']);

    Route::get('/get-default-address', [AddressController::class, 'getAddressDefault']);
});

// Product

Route::get('/get-products/{filter}', [ProductController::class, 'getProductsByCategory']);

Route::post('/create-product', [ProductController::class, 'createProduct']);

Route::get('/get-categories', [CategoryController::class, 'getCategories']);

Route::get('/get-sizes', [SizeController::class, 'getSizes']);

Route::get('/get-colors', [ColorController::class, 'getColors']);

Route::get('/get-product/{id}', [ProductController::class, 'getProduct']);

Route::get('/get-products/{id}', [ProductController::class, 'getProducts']);

Route::post('/add-to-cart', [CartController::class, 'addToCart']);

// Cart

Route::get('/get-cart-product-count', [CartController::class, 'getCartProductCount']);

Route::get('/get-cart', [CartController::class, 'getCartDetail']);

Route::put('/update-cart-product-quantity', [CartController::class, 'updateCartProductQuantity']);

Route::post('/delete-cart-product', [CartController::class, 'deleteCartProduct']);

// Payment

Route::get('/payment/{id}', [PaymentController::class, 'processPayment']);

// Order

Route::post('/create-order', [OrderController::class, 'createOrder']);

Route::get('/get-orders', [OrderController::class, 'getOrders']);

Route::get('/get-order/{id}', [OrderController::class, 'getOrderDetail']);

Route::post('/cancel-order', [OrderController::class, 'cancelOrder']);

Route::post('/confirmed_receipt', [OrderController::class, 'confirmedReceiptOrder']);

Route::post('/vnpay_payment', [PaymentController::class, 'vnpayPayment']);

Route::post('/confirm-payment', [PaymentController::class, 'confirmPayment']);

// Admin

Route::get('/get-orders-admin', [OrderController::class, 'getOrdersByAdmin']);

Route::get('/get-order-admin/{id}', [OrderController::class, 'getOrerDetailAdmin']);

Route::post('/confirm-order', [OrderController::class, 'confirmOrder']);

Route::post('/start-shipping', [OrderController::class, 'startShipping']);

Route::post('/complete-shipping', [OrderController::class, 'completeShipping']);

Route::post('/cancel-order-admin', [OrderController::class, 'cancelOrderAdmin']);

Route::post('/revert-order-status', [OrderController::class, 'revertOrderStatus']);

Route::get('/get-users', [UserController::class, 'getAllUser']);

Route::get('/get-all-products', [ProductController::class, 'getAllProduct']);

Route::get('/get-categories-admin', [CategoryController::class, 'getCategoriesByAdmin']);

Route::post('/check-slug', [CategoryController::class, 'checkSlug']);

Route::post('/create-category', [CategoryController::class, 'createCategory']);

Route::get('/get-payments', [PaymentController::class, 'getPaymentsByAdmin']);

Route::post('/confirm-payment-admin', [PaymentController::class, 'confirmPaymentByAdmin']);










