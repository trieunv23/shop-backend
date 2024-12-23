<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartProduct;
use App\Models\Color;
use App\Models\Product;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    public function __construct() {
        $this->middleware('auth.jwt');
        $this->middleware('modify.response');
    }

    public function addToCart(Request $request) 
    {
        $user = Auth::user();

        $rules = [
            'product_id' => 'required|integer',
            'color_id' => 'required|integer',
            'size_id' => 'required|integer',
            'quantity' => 'required|integer'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([ 
                'status' => 'error', 
                'errors' => $validator->errors() 
            ], 422); 
        }

        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        $product = Product::find($request['product_id']);

        if (!$product) {
            return response()->json([
                'error' => 'Product not found.'
            ], 404);
        }

        if ($product->sold_out) {
            return response()->json([
                'message' => 'Product is sold out'
            ], 400);
        }
        
        $cartProduct = CartProduct::where('cart_id', $cart->id)
                                ->where('product_id', $request['product_id'])
                                ->where('color_id', $request['color_id'])
                                ->where('size_id', $request['size_id'])
                                ->first();

        if ($cartProduct) { 
            $newQuantity = $cartProduct->quantity + $request['quantity'];
            $cartProduct->quantity = $newQuantity;   
            $cartProduct->save();
        } else {
            CartProduct::create([
                'cart_id' => $cart->id,
                'product_id' => $request['product_id'],
                'color_id' => $request['color_id'],
                'size_id' => $request['size_id'],
                'quantity' => $request['quantity'],
            ]);
        }

        return response()->json([
            'message' => 'Product has been added to cart'
        ], 200);  
    }

    public function showCartProductCount()
    {
        $cart = Auth::user()->cart;

        if ($cart) {
            $cartProductCount = $cart->cartProducts()->count();
            return response()->json($cartProductCount);
        } else {
            return response()->json([
                'message'=>'Cart not found'
            ], 401);
        }
    }

    public function showCart() 
    {
        $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);

        $cartProducts = $cart->cartProducts()->with('product')->get();

        $totalPrice = 0;

        $customizedCartProducts = $cartProducts->map(function ($cartProduct) use (&$totalPrice) {
            $cartProduct->load('product');
            $imagePath = $cartProduct->product->image?->path;

            $productTotalPrice = $cartProduct->quantity * $cartProduct->product->price;
            $cartProduct->totalPrice = $productTotalPrice;

            $productColor = Color::where('id', $cartProduct->color_id)->first();
            $productSize = Size::where('id', $cartProduct->size_id)->first();

            $totalPrice += $productTotalPrice;

            return [
                'id' => $cartProduct->id,
                'product_id' => $cartProduct->product_id,
                'product_code' => $cartProduct->product->code,
                'color' => [
                    'id' => $productColor->id,
                    'name' => $productColor->name
                ], 
                'size' => [
                    'id' => $productSize->id,
                    'name' => $productSize->name
                ],
                'name' => $cartProduct->product->name,
                'price' => $cartProduct->product->price,
                'is_active' => $cartProduct->product->is_active,
                'img_path' => $imagePath,
                'quantity' => $cartProduct->quantity,
                'total_price' => $productTotalPrice,
            ];
        });

        return response()->json([
            'cart' => $cart,
            'products' => $customizedCartProducts,
            'total_price' => $totalPrice
        ], 200);
    }

    public function updateCartProduct(Request $request, $id)
    {
        $rules = [
            'quantity' => 'required|integer|min:1' 
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([ 
                'status' => 'error', 
                'errors' => $validator->errors() 
            ], 422); 
        }

        $cart = Auth::user()->cart;

        if (!$cart) {
            return response()->json([
                'message' => 'Cart not found'
            ], 404);
        }

        $cartProduct = $cart->cartProducts()->where('id', $id)->first();

        if (!$cartProduct) {
            return response()->json([
                'message' => 'Product not in cart'
            ], 404);
        }

        $cartProduct->update($request->only(array_keys($rules)));

        return response()->json([
            'message' => 'Cart product updated successfully'
        ], 200);
    }

    public function deleteCartProduct($id)
    {
        $cart = Auth::user()->cart;

        if (!$cart) {
            return response()->json([
                'message' => 'Cart not found'
            ], 404);
        }

        $cartProduct = $cart->cartProducts()->where('id', $id)->first();

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
