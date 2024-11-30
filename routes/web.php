<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SizeController;
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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/get-products/{filter}', [ProductController::class, 'getProductsByCategory']);

Route::post('/create-product', [ProductController::class, 'store']);

Route::post('/register', [UserController::class, 'register']);

Route::post('/login', [UserController::class, 'login']);

Route::post('/check-username', [UserController::class, 'checkUsername']);

Route::post('/logout', [UserController::class, 'logout']);

Route::get('/check-auth', [UserController::class, 'checkAuth']);

Route::get('/get-user', [UserController::class, 'getUser']);

Route::put('update-profile', [UserController::class, 'changeProfileInfor']);

Route::get('get-addresses', [AddressController::class, 'getAddresses']);

Route::put('update-address', [AddressController::class, 'changeAddress']);

Route::get('/get-address/{id}', [AddressController::class, 'getAddress']);

Route::post('/create-address', [AddressController::class, 'createAddress']);

Route::post('/update-default-address', [AddressController::class, 'updateAddressDefault']);

Route::post('/delete-address', [AddressController::class, 'deleteAddress']);

Route::post('/create-product', [ProductController::class, 'createProduct']);

Route::get('/get-categories', [CategoryController::class, 'getCategories']);

Route::get('/get-sizes', [SizeController::class, 'getSizes']);

Route::get('/get-colors', [ColorController::class, 'getColors']);

Route::get('/get-product/{id}', [ProductController::class, 'getProduct']);

Route::get('/get-products/{id}', [ProductController::class, 'getProducts']);

Route::post('/add-to-cart', [CartController::class, 'addToCart']);

Route::get('/get-cart-product-count', [CartController::class, 'getCartProductCount']);

Route::get('/get-cart', [CartController::class, 'getCartDetail']);

Route::put('/update-cart-product-quantity', [CartController::class, 'updateCartProductQuantity']);

Route::post('/delete-cart-product', [CartController::class, 'deleteCartProduct']);

Route::get('/get-default-address', [AddressController::class, 'getAddressDefault']);

Route::get('/payment/{id}', [PaymentController::class, 'processPayment']);

Route::post('/create-order', [OrderController::class, 'createOrder']);

Route::get('/get-orders', [OrderController::class, 'getOrders']);

Route::get('/get-order/{id}', [OrderController::class, 'getOrderDetail']);

Route::post('/vnpay_payment', [PaymentController::class, 'vnpayPayment']);

Route::post('/confirm-payment', [PaymentController::class, 'confirmPayment']);

// Admin
Route::get('/get-orders-admin', [OrderController::class, 'getOrdersByAdmin']);

Route::get('/get-order-admin/{id}', [OrderController::class, 'getOrerDetailAdmin']);

Route::post('/confirm-order', [OrderController::class, 'confirmOrder']);

Route::post('/start-shipping', [OrderController::class, 'startShipping']);

Route::post('/complete-shipping', [OrderController::class, 'completeShipping']);

Route::post('/cancel-order', [OrderController::class, 'cancelOrder']);

Route::get('/get-users', [UserController::class, 'getAllUser']);

Route::get('/get-all-products', [ProductController::class, 'getAllProduct']);

Route::get('/get-categories-admin', [CategoryController::class, 'getCategoriesByAdmin']);

Route::post('/check-slug', [CategoryController::class, 'checkSlug']);

Route::post('/create-category', [CategoryController::class, 'createCategory']);

Route::get('/get-payments', [PaymentController::class, 'getPaymentsByAdmin']);

Route::post('/confirm-payment-admin', [PaymentController::class, 'confirmPaymentByAdmin']);

Route::get('email/verify', [UserController::class, 'verifyEmail'])->name('verification.verify');

Route::post('/verify-email', [UserController::class, 'verifyEmail']);











