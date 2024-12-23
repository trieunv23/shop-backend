<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function __construct() {
        $this->middleware('auth.jwt');
        $this->middleware('modify.response');
    }

    public function createOrder(Request $request) 
    {
        $rules = [
            'method' => 'required|string'
        ];
        
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([ 
                'status' => 'error', 
                'errors' => $validator->errors() 
            ], 422); 
        }

        $user = Auth::user();

        $cart = $user->cart;

        if (!$cart || $cart->cartProducts->count() == 0) {
            return response()->json([
                'message' => 'Cart is empty'
            ], 400);
        }

        $totalAmount = $cart->cartProducts->reduce(function ($total, $cartProduct) { 
            return $total + $cartProduct->product->price * $cartProduct->quantity;
        }, 0);

        $address = $user->addresses->where('is_default', true)->first();

        if (!$address) {
            return response()->json([
                'message' => 'Shipping address not found'
            ], 400);
        }

        foreach ($cart->cartProducts as $cartProduct) {
            if ($cartProduct->product->sold_out) {
                return response()->json([
                    'message' => 'Product is Sold Out: ' . $cartProduct->product->name
                ], 400);
            }
        }

        DB::beginTransaction(); 
        
        try {
            $order = Order::create([
                'user_id' => $user->id,
                'total_amount' => $totalAmount,
                'status' => 'pending',
            ]); 

            $cartProducts = $cart->cartProducts;
    
            foreach ($cartProducts as $cartProduct) {
                $order->orderProducts()->create([
                    'product_id' => $cartProduct->product->id,
                    'size_id' => $cartProduct->size_id,
                    'color_id' => $cartProduct->color_id,
                    'quantity' => $cartProduct->quantity,
                    'price' => $cartProduct->product->price
                ]);
            }

            $order->orderAddress()->create([
                'order_id' => $order->id,
                'name' => $address->name,
                'phone_number' => $address->phone_number,
                'province' => $address->province,
                'district' => $address->district,
                'ward' => $address->ward,
                'detail' => $address->detail,
            ]);

            $order->orderStatuses()->create([
                'status' => 'pending',  
                'date' => Carbon::now(), 
                'updated_by' => 'user',
                'notes' => 'Order successful',
                'is_rollback' => false,
                'is_rolled_back' => false,
            ]);

            $order->orderPayment()->create([
                'method' => $request['method'], 
                'status' => 'pending', 
                'total_amount' => $totalAmount, 
                'notes' => 'Payment setup successful',
            ]); 

            $cart->cartProducts()->delete();

            DB::commit();

            return response()->json([
                'message' => 'Order successful', 
                'order' => $order
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Order creation failed', 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function showOrders() 
    {
        $user = Auth::user();

        $orders = $user->orders;

        $optimizeOrders = $orders->map(function ($order) {
            $orderProducts = $order->orderProducts;
            $orderPayment = $order->orderPayment;

            return [
                'id' => $order->id,
                'code' => $order->code,
                'total_price' => $order->total_amount,
                'status' => $order->status,
                'products' => $orderProducts->map(function ($orderProduct) {
                    return [
                        'id' => $orderProduct->product->id,
                        'name' => $orderProduct->product->name,
                        'quantity' => $orderProduct->quantity,
                        'price' => $orderProduct->price,
                        'total' => $orderProduct->price * $orderProduct->quantity,
                        'image_url' => $orderProduct->product->image?->path,
                    ];
                }),
                'payment' => [
                    'id' => $orderPayment->id,
                    'method' => $orderPayment->method,
                    'status' => $orderPayment->status,
                    'totalAmount' => $orderPayment->totalAmount,
                    'date' => $orderPayment->date,
                ],
            ];
        });

        return response()->json([
            'orders' => $optimizeOrders,
        ], 200);
    }

    public function showOrder($id) 
    {
        $user = Auth::user();

        $order = $user->orders->find($id);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        $orderProducts = $order->orderProducts;
        $orderPayment = $order->orderPayment;
        $orderStatuses = $order->orderStatuses->where('is_rollback', false)->where('is_rolled_back', false);

        $orderAddress = $order->orderAddress;
        unset($orderAddress['created_at'], $orderAddress['updated_at']);

        $optimizeOrder = [
            'id' => $order->id,
            'code' => $order->code,
            'total_amount' => $order->total_amount,
            'status' => $order->status,
            'address' => $orderAddress,
            'order_date' => $order->created_at,
            'payment' => [
                'id' => $orderPayment->id,
                'method' => $orderPayment->method,
                'receipt_url' => $orderPayment->receipt_url,
                'status' => $orderPayment->status,
                'total_amount' => $orderPayment->total_amount,
                'date' => $orderPayment->date,
            ],
            'products' => $orderProducts->map(function ($orderProduct) {
                return [
                    'id' => $orderProduct->product->id,
                    'name' => $orderProduct->product->name,
                    'quantity' => $orderProduct->quantity,
                    'size_id' => $orderProduct->size_id,
                    'color_id' => $orderProduct->color_id,
                    'price' => $orderProduct->price,
                    'total' => $orderProduct->price * $orderProduct->quantity,
                    'image_url' => $orderProduct->product->image?->path,
                ];
            }),
            'history' => $orderStatuses->map(function ($orderStatus) {
                return [
                    'id' => $orderStatus->id,
                    'status' => $orderStatus->status,
                    'previous_status' => $orderStatus->previous_status,
                    'date' => $orderStatus->date,
                    'updated_by' => $orderStatus->updated_by,
                    'is_rollback' => $orderStatus->is_rollback,
                    'notes' => $orderStatus->notes,
                ];
            }),
        ];
        
        return response()->json([
            'order' => $optimizeOrder,
        ]);
    }

    public function cancel($id)
    {
        $order = Order::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        if (in_array($order->status, ['shipped', 'delivered', 'cancelled', 'returned'])) {
            return response()->json([
                'message' => 'Order cannot be cancelled'
            ], 403);
        }

        DB::beginTransaction();

        try {
            $order->orderStatuses()->create([
                'status' => 'cancelled',  
                'previous_status' => $order->status,
                'date' => Carbon::now(), 
                'updated_by' => 'user',
                'notes' => 'Order cancelled successfully',
                'is_rollback' => false,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Order cancellation successful'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Order cancellation failed'
            ], 500);
        }
    }

    public function confirmedReceiptOrder(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'User not authenticated'
            ], 401);
        }

        $id = $request->input('order_id');

        $user = Auth::user();

        $order = Order::where('id', $id)
                    ->where('user_id', $user->id)
                    ->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        $orderSchedule = $order->orderSchedule()->first();

        if ($orderSchedule->status !== 'delivered') {
            return response()->json([
                'message' => 'Order cannot confirm receipt',
            ], 403);
        }

        DB::beginTransaction();

        try {
            $order->orderSchedule()->update([
                'previous_status' => $orderSchedule->status,
                'status' => 'confirmed_receipt',
                'receipt_date' => Carbon::now(),
                'schedule_description' => 'Receipt confirmation successful', 
                'notes' => 'Receipt confirmation successful'
            ]); 

            DB::commit();

            return response()->json(['message' => 'Receipt confirmation successful']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Receipt confirmation failed', 'error' => $e->getMessage()], 500);
        }
    }
}
