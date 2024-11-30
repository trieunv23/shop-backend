<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartProduct;
// use App\Models\CartProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use function PHPSTORM_META\map;

class CartController extends Controller
{
    public function addToCart(Request $request) 
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'User not validate',
            ], 401);
        }

        $validated = $request->validate([
            'product_id' => 'required|integer',
            'color_id' => 'required|integer',
            'size_id' => 'required|integer',
            'quantity' => 'required|integer'
        ]);

        $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);

        $product = Product::find($validated['product_id']);

        if (!$product) {
            return response()->json(['error' => 'Product not found.'], 404);
        }

        $cartProduct = CartProduct::where('cart_id', $cart->id)
                                ->where('product_id', $validated['product_id'])
                                ->where('color_id', $validated['color_id'])
                                ->where('size_id', $validated['size_id'])
                                ->first();

        if ($cartProduct) { 
            $cartProduct->quantity += $validated['quantity'];   
            $cartProduct->save();
        } else {
            CartProduct::create([
                'cart_id' => $cart->id,
                'product_id' => $validated['product_id'],
                'color_id' => $validated['color_id'],
                'size_id' => $validated['size_id'],
                'quantity' => $validated['quantity'],
            ]);
        }

        return response()->json(['message' => 'Product has been added to cart'], 200);  
    }

    public function getCartProductCount()
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'User not validate',
            ], 401);
        }

        $cart = Auth::user()->cart;

        if ($cart) {
            $cartProductCount = $cart->cartProducts()->count();
            return response()->json($cartProductCount);
        } else {
            return response()->json(['message'=>'Cart not found'], 401);
        }
    }

    public function getCartDetail() 
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'User not validate',
            ], 401);
        }

        $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);

        $cartProducts = $cart->cartProducts()->with('product')->get();

        $totalPrice = 0;

        $products = $cartProducts->map(function ($cartProduct) use (&$totalPrice) {
            $cartProduct->load('product');
            $firstImageUrl = $cartProduct->product->productImages()->first()?->img_url;
            $cartProduct->product->image = $firstImageUrl;

            $productTotalPrice = $cartProduct->quantity * $cartProduct->product->price;
            $cartProduct->totalPrice = $productTotalPrice;

            $totalPrice += $productTotalPrice;

            return $cartProduct;
        });

        return response()->json([
            'cart' => $cart,
            'products' => $products,
            'totalPrice' => $totalPrice
        ], 200);
    }

    public function updateCartProductQuantity(Request $request)
    {
        if (!Auth::check()) { 
            return response()->json([ 'message' => 'User not validated', ], 401); 
        }

        $user = Auth::user();

        $validated = $request->validate([ 
            'cart_product_id' => 'required|integer',
            'quantity' => 'required|integer' 
        ]);

        $cart = $user->cart;

        if (!$cart) {
            return response()->json(['message' => 'Cart not found'], 404);
        }

        $cartProduct = $cart->cartProducts()->where('id', $validated['cart_product_id'])->first();

        if (!$cartProduct) {
            return response()->json(['message' => 'Product not in cart'], 404);
        }

        $cartProduct->quantity = $validated['quantity'];
        $cartProduct->save();

        return response()->json([
            'message' => 'Cart product quantity updated successfully'
        ], 200);
    }

    public function deleteCartProduct(Request $request)
    {
        if (!Auth::check()) { 
            return response()->json([ 'message' => 'User not validated', ], 401); 
        }

        $validated = $request->validate([ 
            'cart_product_id' => 'required|integer' 
        ]);

        $cart = Auth::user()->cart;

        if (!$cart) {
            return response()->json([
                'message' => 'Cart not found'
            ], 404);
        }

        $cartProduct = $cart->cartProducts()->where('id', $validated['cart_product_id'])->first();

        if (!$cartProduct) {
            return response()->json([
                'message' => 'Product not found in cart'
            ], 404);
        }

        $cartProduct->delete();
        
        return response()->json([
            'message' => 'Product removed from cart successfully'
        ], 200);
    }
}
