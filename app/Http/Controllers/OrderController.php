<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\StringHelper;
use App\Models\OrderPayment;
use App\Models\OrderSchedule;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function createOrder(Request $request) 
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'User not authenticated'
            ], 401);
        }

        $validatedData = $request->validate([ 
            'shipping_method' => 'required|string', 
            'payment_method' => 'required|string'
        ]);

        $user = Auth::user();

        $cart = $user->cart;

        if (!$cart || $cart->cartProducts->count() == 0) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        $totalPrice = $cart->cartProducts->reduce(function ($total, $cartProduct) { 
            return $total + $cartProduct->product->price * $cartProduct->quantity;
        }, 0);

        $address = $user->addresses->where('is_default', true)->first();

        if (!$address) {
            return response()->json(['message' => 'Shipping address not found'], 400);
        }

        $fullAddress = $address->address_detail . ', ' . $address->ward_name . ', ' . $address->district_name . ', ' . $address->province_name;

        DB::beginTransaction(); 
        
        try {
            $order = Order::create([
                'user_id' => $user->id,
                'total_amount' => $totalPrice,
            ]); 

            $cartProducts = $cart->cartProducts;
    
            foreach ($cartProducts as $cartProduct) {
                $order->orderProducts()->create([
                    'product_id' => $cartProduct->product->id,
                    'quantity' => $cartProduct->quantity,
                    'price' => $cartProduct->product->price
                ]);
            }

            OrderSchedule::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'status' => 'pending',
                'order_date' => Carbon::now()->setTimezone('Asia/Ho_Chi_Minh'),
                'shipping_address' => $fullAddress,
                'recipient_name' => $address->customer_name,
                'recipient_phone' => $address->phone_number,
                'shipping_cost' => 0,
                'schedule_description' => '', 
                'notes' => ''
            ]);

            OrderPayment::create([
                'order_id' => $order->id,
                'payment_method' => $validatedData['payment_method'],
                'payment_status' => 'pending',
                'payment_amount' => $totalPrice,
            ]);

            $cart->cartProducts()->delete();

            DB::commit();

            return response()->json(['message' => 'Order successful', 'order' => $order], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Order creation failed', 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getOrders() 
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'User not authenticated'
            ], 401);
        }

        $user = Auth::user();

        $orders = Order::where('user_id', $user->id)->with([
            'orderProducts:id,order_id,product_id,price,quantity',
            'orderProducts.product:id,name',
            'orderProducts.product.firstProductImage:product_id,img_url',
            'orderSchedule:id,order_id,status,user_id',
            'orderPayment:id,order_id,payment_method,payment_status,payment_date'
        ])->get();

        $optimizeOrders = $orders->map(function ($order) {
            return [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'order_date' => $order->created_at->format('Y-m-d'),
                'total_price' => $order->orderProducts->sum(function ($orderProduct) {
                    return $orderProduct->price * $orderProduct->quantity;
                }),
                'products' => $order->orderProducts->map(function ($orderProduct) {
                    return [
                        'product_id' => $orderProduct->product->id,
                        'product_name' => $orderProduct->product->name,
                        'quantity' => $orderProduct->quantity,
                        'price' => $orderProduct->price,
                        'total' => $orderProduct->price * $orderProduct->quantity,
                        'image_url' => $orderProduct->product->firstProductImage->img_url,
                    ];
                }),
                'order_schedule' => $order->orderSchedule,
                'order_payment' => $order->orderPayment
            ];
        });

        $camelCaseOrders = StringHelper::convertListKeysToCamelCase($optimizeOrders->toArray());

        return response()->json([
            'orders' => $camelCaseOrders,
        ], 200);
    }

    public function getOrderDetail($id) 
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'User not authenticated'
            ], 401);
        }

        $user = Auth::user();

        $order = Order::where('id', $id)
                    ->where('user_id', $user->id)
                    ->with([
                        'orderProducts.product',
                        'orderProducts.product.firstProductImage',
                        'orderSchedule',
                        'orderPayment'
                    ])->first();

        if ($order) {
            $order_schedule = $order->orderSchedule;
            $order_payment = $order->orderPayment;
        } else {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        $optimizeOrder = [
            'order_code' => $order->order_code,
            'total_amount' => $order->total_amount,
            'order_status' => $order->order_status,
            'order_schedule' => [
                'status' => $order_schedule->status,
                'order_date' => $order_schedule->order_date,
                'confirmation_date' => $order_schedule->confirmation_date,
                'delivery_date' => $order_schedule->delivery_date,
                'delivered_date' => $order_schedule->delivered_date,
                'cancelled_date' => $order_schedule->cancelled_date,
                'shipping_address' => [
                    'phone' => $order_schedule->recipient_phone,
                    'name' => $order_schedule->recipient_name,
                    'address' => $order_schedule->shipping_address,
                ],
                'shipping_cost' => $order_schedule->shipping_cost,
                'delivered_date' => $order_schedule->delivered_date,
                'delivery_date' => $order_schedule->delivery_date
            ],
            'order_payment' => [
                'payment_method' => $order_payment->payment_method,
                'payment_status' => $order_payment->payment_status,
                'payment_amount' => $order_payment->payment_amount,
                'payment_date' => $order_payment->payment_date
            ],
            'products' => $order->orderProducts->map(function ($orderProduct) {
                return [
                    'product_id' => $orderProduct->product->id,
                    'product_name' => $orderProduct->product->name,
                    'quantity' => $orderProduct->quantity,
                    'price' => $orderProduct->price,
                    'total' => $orderProduct->price * $orderProduct->quantity,
                    'image_url' => $orderProduct->product->firstProductImage->img_url,
                ];
            })
        ];
        
        return response()->json([
            'order' => StringHelper::convertKeysToCamelCase($optimizeOrder),
        ]);
    }

    public function getOrdersByAdmin() {
        // Authentication Admin

        $orders = Order::with(['user.profile' => function ($query) {
            $query->select('id', 'name', 'user_id');
        }])->get();

        $camelCaseOrders = StringHelper::convertListKeysToCamelCase($orders->toArray());

        return response()->json([
            'orders' =>  $camelCaseOrders,
        ]);
    }

    public function getOrerDetailAdmin ($id)
    {
        // Authentication Admin

        $order = Order::where('id', $id)
                      ->with([
                        'user',
                        'user.profile',
                        'orderProducts.product',
                        'orderProducts.product.firstProductImage',
                        'orderSchedule',
                        'orderPayment',
                      ])  
                      ->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        $user = $order->user;
        $order_schedule = $order->orderSchedule;
        $order_payment = $order->orderPayment;

        $optimizeOrder = [
            'order_id' => $order->id,
            'order_code' => $order->order_code,
            'total_amount' => $order->total_amount,
            'order_status' => $order->order_status,
            'user' => [
                'user_id' => $user->id,
                'user_code' => $user->code,
                'name' => $user->profile->name,
            ],
            'order_schedule' => [
                'status' => $order_schedule->status,
                'order_date' => $order_schedule->order_date,
                'confirmation_date' => $order_schedule->confirmation_date,
                'delivery_date' => $order_schedule->delivery_date,
                'delivered_date' => $order_schedule->delivered_date,
                'cancelled_date' => $order_schedule->cancelled_date,
                'shipping_address' => [
                    'phone' => $order_schedule->recipient_phone,
                    'name' => $order_schedule->recipient_name,
                    'address' => $order_schedule->shipping_address,
                ],
                'shipping_cost' => $order_schedule->shipping_cost,
                'delivered_date' => $order_schedule->delivered_date,
                'delivery_date' => $order_schedule->delivery_date
            ],
            'orderPayment' => [
                'id' => $order_payment->id,
                'payment_method' => $order_payment->payment_method,
                'payment_status' => $order_payment->payment_status,
                'payment_amount' => $order_payment->payment_amount,
                'payment_date' => $order_payment->payment_date,
                'payment_image' => $order_payment->payment_image,
                'payment_code' => $order_payment->payment_code,
            ],
            'orderProducts' => $order->orderProducts->map(function ($orderProduct) {
                return [
                    'product_id' => $orderProduct->product->id,
                    'product_name' => $orderProduct->product->name,
                    'quantity' => $orderProduct->quantity,
                    'price' => $orderProduct->price,
                    'total' => $orderProduct->price * $orderProduct->quantity,
                    'image_url' => $orderProduct->product->firstProductImage->img_url,
                ];
            })
        ];

        return response()->json([
            'order' => StringHelper::convertKeysToCamelCase($optimizeOrder),
        ]);
    }

    public function confirmOrder(Request $request)
    {
        // Authentication Admin

        $id = $request->input('order_id');
        $order = Order::findOrFail($id);

        if ($order->order_status !== 'pending') {
            return response()->json(['message' => 'Order cannot be confirmed'], 400);
        }

        DB::beginTransaction();

        try {
            $order->update([
                'order_status' => 'confirmed', 
                'confirmation_date' => Carbon::now()->setTimezone('Asia/Ho_Chi_Minh'),
            ]);
            $order->orderSchedule()->update([
                'status' => 'confirmed',
                'schedule_description' => 'Order confirmed successfully', 
                'notes' => 'Order has been confirmed',
                'confirmation_date' => Carbon::now()->setTimezone('Asia/Ho_Chi_Minh'),
            ]);

            DB::commit();

            return response()->json(['message' => 'Order confirmed successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Order confirmation failed'], 500);
        }
    }

    public function startShipping(Request $request)
    {
        // Authentication Admin

        $id = $request->input('order_id');
        $order = Order::findOrFail($id);

        if ($order->order_status !== 'confirmed') { // Nếu chưa được xác nhận => Chưa được gửi đi
            return response()->json(['message' => 'Order not confirmed'], 400);
        }

        DB::beginTransaction();

        try {
            $order->orderSchedule()->update([
                'status' => 'in_transit',
                'delivery_date' => Carbon::now()->setTimezone('Asia/Ho_Chi_Minh'),
                'schedule_description' => 'Started delivery successfully', 
                'notes' => 'Order has started shipping'
            ]); 

            DB::commit();

            return response()->json(['message' => 'Started delivery successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Delivery start failed', 'error' => $e->getMessage()], 500);
        }
    }

    public function completeShipping(Request $request)
    {
        // Authentication Admin

        $id = $request->input('order_id');
        $order = Order::findOrFail($id);
        
        if ($order->order_status !== 'confirmed' || $order->orderSchedule->status !== 'in_transit') {
            return response()->json(['message' => 'Order not yet started shipping'], 400);
        }

        DB::beginTransaction();

        try {
            $order->update(['order_status' => 'delivered']);
            $order->orderSchedule()->update([
                'status' => 'shipped',
                'delivered_date' => Carbon::now()->setTimezone('Asia/Ho_Chi_Minh'),
                'schedule_description' => 'Order completed successfully', 
                'notes' => 'Order delivered successfully'
            ]); 

            DB::commit();

            return response()->json(['message' => 'Order delivered successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Delivery start failed', 'error' => $e->getMessage()], 500);
        }
    }

    public function cancelOrder(Request $request)
    {
        // Authentication Admin

        $id = $request->input('order_id');
        $order = Order::findOrFail($id);

        DB::beginTransaction();

        try {
            $order->update(['order_status' => 'cancelled']);
            $order->orderSchedule()->update([
                'status' => 'cancelled',
                'cancelled_date' => Carbon::now()->setTimezone('Asia/Ho_Chi_Minh'),
                'schedule_description' => 'Order Cancellation Successful', 
                'notes' => 'Order has been cancelled'
            ]); 

            DB::commit();

            return response()->json(['message' => 'Order Cancellation Successful']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Cancel order failed', 'error' => $e->getMessage()], 500);
        }
    }
}
