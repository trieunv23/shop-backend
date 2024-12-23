<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    protected $validStatuses = [
        'pending', 
        'confirmed', 
        'processed', 
        'shipped', 
        'delivered', 
        'cancelled', 
        'returned'
    ];

    public function __construct()
    {
        $this->middleware('auth:admin');
        $this->middleware('modify.response');
    }

    public function showOrders() {
        $orders = Order::with('user.profile')->get();

        return response()->json([
            'orders' =>  $orders,
        ]);
    }

    public function showOrder($id)
    {
        $order = Order::where('id', $id)->with([
            'user',
            'orderProducts',
            'orderPayment',
        ])->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        $user = $order->user;
        $orderStatuses = $order->orderStatuses;
        $orderPayment = $order->orderPayment;
        $orderAddress = $order->orderAddress;
        $orderProducts = $order->orderProducts;

        unset($orderPayment['created_at'], $orderPayment['updated_at']);
        unset($orderAddress['created_at'], $orderAddress['updated_at']);

        $optimizeOrder = [
            'id' => $order->id,
            'code' => $order->code,
            'total_amount' => $order->total_amount,
            'status' => $order->status,
            'user' => [
                'id' => $user->id,
                'code' => $user->code,
                'name' => $user->profile->name,
            ],
            'history' => $orderStatuses->map(function ($orderStatus) {
                unset($orderStatus['created_at'], $orderStatus['updated_at'], $orderStatus['order_id']);
                return $orderStatus;
            }),
            'address' => $orderAddress,
            'payment' => $orderPayment,
            'orderProducts' => $orderProducts->map(function ($orderProduct) {
                return [
                    'id' => $orderProduct->id,
                    'product_id' => $orderProduct->product->id,
                    'name' => $orderProduct->product->name,
                    'quantity' => $orderProduct->quantity,
                    'price' => $orderProduct->price,
                    'total' => $orderProduct->price * $orderProduct->quantity,
                    'path' => $orderProduct->product->image?->path,
                ];
            })
        ];

        return response()->json([
            'order' => $optimizeOrder,
        ]);
    }

    public function confirm($id)
    {
        $order = Order::findOrFail($id);

        if ($order->status !== 'pending') { 
            return response()->json([ 
                'error' => 'Order cannot be confirmed unless it is pending'
            ], 400); 
        }

        DB::beginTransaction();

        try {
            $order->orderStatuses()->create([
                'status' => 'confirmed',  
                'previous_status' => $order->status,
                'date' => Carbon::now(), 
                'updated_by' => 'admin',
                'notes' => 'Order confirmed successfully',
                'is_rollback' => false,
                'is_rolled_back' => false,
            ]);

            $order->update(['status' => 'confirmed']);

            DB::commit();

            return response()->json([
                'message' => 'Order confirmed successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Order confirmation failed'
            ], 500);
        }
    }

    public function processing($id) 
    {
        $order = Order::findOrFail($id);

        if (!$order) {
            return response()->json([
                'error' => 'Order not found'
            ], 400);
        }

        if ($order->status !== 'confirmed') { 
            return response()->json([ 
                'error' => 'Orders require confirmation before processing'
            ], 400); 
        }

        DB::beginTransaction();

        try {
            $order->orderStatuses()->create([
                'status' => 'processed',  
                'previous_status' => $order->status,
                'date' => Carbon::now(), 
                'updated_by' => 'admin',
                'notes' => 'Order processed successfully',
                'is_rollback' => false,
                'is_rolled_back' => false,
            ]);

            $order->update(['status' => 'processed']);

            DB::commit();

            return response()->json([
                'message' => 'Order processed successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Order processing failed'
            ], 500);
        }
    }

    public function ship($id)
    {
        $order = Order::findOrFail($id);

        if ($order->status !== 'processed') { 
            return response()->json([ 
                'error' => 'Orders need to be processed before shipping'
            ], 400); 
        }

        DB::beginTransaction();

        try {
            $order->orderStatuses()->create([
                'status' => 'shipped',  
                'previous_status' => $order->status,
                'date' => Carbon::now(), 
                'updated_by' => 'admin',
                'notes' => 'Order shipped successfully',
                'is_rollback' => false,
                'is_rolled_back' => false,
            ]);

            $order->update(['status' => 'shipped']);

            DB::commit();

            return response()->json([
                'message' => 'Order shipped successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Order shipping failed'
            ], 500);
        }
    }

    public function delivery($id) 
    {
        $order = Order::findOrFail($id);

        if ($order->status !== 'shipped') { 
            return response()->json([ 
                'error' => 'Orders need to be shipped before delivery'
            ], 400); 
        }

        DB::beginTransaction();

        try {
            $order->orderStatuses()->create([
                'status' => 'delivered',  
                'previous_status' => $order->status,
                'date' => Carbon::now(), 
                'updated_by' => 'admin',
                'notes' => 'Order delivered successfully',
                'is_rollback' => false,
                'is_rolled_back' => false,
            ]);

            $order->update(['status' => 'delivered']);

            DB::commit();

            return response()->json([
                'message' => 'Order delivered successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Order delivery failed'
            ], 500);
        }
    }

    public function cancel($id) 
    {
        $order = Order::findOrFail($id);

        DB::beginTransaction();

        if (in_array($order->status, ['cancelled', 'returned'])) {
            return response()->json([ 
                'error' => 'Order cannot be cancelled'
            ], 400); 
        }

        try {
            $order->orderStatuses()->create([
                'status' => 'cancelled',  
                'previous_status' => $order->status,
                'date' => Carbon::now(), 
                'updated_by' => 'admin',
                'notes' => 'Order cancelled successfully',
                'is_rollback' => false,
                'is_rolled_back' => false,
            ]);

            $order->update(['status' => 'cancelled']);

            DB::commit();

            return response()->json([
                'message' => 'Order cancelled successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Order cancellation failed'
            ], 500);
        }
    }

    public function rollBack($id)
    {
        $order = Order::findOrFail($id);

        $statuses = $order->orderStatuses;

        $lastStatus = $statuses->last();

        if (!$lastStatus || !$lastStatus->previous_status) {
            return response()->json([
                'message' => 'No previous status available to rollback',
            ], 404);
        }

        if ($lastStatus->is_rollback) {
            $targetStatus = $statuses->where('status', $lastStatus->status)
                                    ->where('is_rollback', false)
                                    ->where('is_rolled_back', false)
                                    ->last();
        } else {
            $targetStatus = $lastStatus;
        }

        if (!$targetStatus->previous_status) {
            return response()->json([
                'message' => 'No previous status available to rollback',
            ], 404);
        }

        DB::beginTransaction();

        try {
            $order->update(['status' => $targetStatus->previous_status]);

            $order->orderStatuses()->create([
                'status' => $targetStatus->previous_status,  
                'previous_status' => $targetStatus->status,
                'date' => Carbon::now(), 
                'updated_by' => 'admin',
                'notes' => 'Rolled back to ' . $targetStatus->previous_status,
                'is_rollback' => true,
                'is_rolled_back' => false,
            ]);
            
            $targetStatus->update([
                'is_rolled_back' => true,
            ]);
            
            DB::commit();

            return response()->json([
                'message' => 'Order status rolled back successfully',
                'target_status' => $targetStatus
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

        return response()->json([
            'message' => 'Failed to rollback status',
            'error' => $e->getMessage()
        ], 500);
        }
    }
}