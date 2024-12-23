<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
        $this->middleware('modify.response');
    }

    public function showPayments()
    {
        $payments = OrderPayment::get();

        $payments = $payments->map(function ($payment) {
            unset($payment['created_at'], $payment['updated_at']);

            return $payment;
        });

        return response()->json([
            'payments' => $payments,
        ], 200);
    }

    public function confirm($id) 
    {
        $order = Order::findOrFail($id);

        $payment = $order->orderPayment()->first();

        if (!$payment) {
            return response()->json([
                'message' => 'No payment found'
            ], 404);
        }

        if ($payment->status === 'confirmed') {
            return response()->json([
                'message' => 'Payment already confirmed'
            ], 400); 
        }

        DB::beginTransaction();

        try {
            $payment->update([
                'status' => 'confirmed',
                'notes' => 'Payment confirmation successful'
            ]);
            
            DB::commit();

            return response()->json([
                'message' => 'Confirm payment success'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Order creation failed', 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function refuse($id) 
    {
        $order = Order::findOrFail($id);

        $payment = $order->orderPayment()->first();

        if (!$payment) {
            return response()->json([
                'message' => 'No payment found'
            ], 404);
        }

        if (in_array($payment->status, ['refused'])) {
            return response()->json([
                'message' => 'Payment already refused'
            ], 400); 
        }

        DB::beginTransaction();

        try {
            $payment->update([
                'status' => 'refused',
                'notes' => 'Payment request refused',
            ]);
            
            DB::commit();

            return response()->json([
                'message' => 'Payment refused successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Payment refuse failed', 
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
