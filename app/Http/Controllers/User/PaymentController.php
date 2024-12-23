<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Helpers\QRHelper;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function __construct() {
        $this->middleware('auth.jwt');
        $this->middleware('modify.response');
    }

    public function showPayment($id) 
    {
        $user = Auth::user();

        $order = Order::where('user_id', $user->id)->where('id', $id)->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        $orderPayment = $order->orderPayment;

        if (!$orderPayment) {
            return response()->json([
                'message' => 'Payment information not found'
            ], 404);
        }

        if (in_array($orderPayment->status, ['confirmed'])) {
            return response()->json([
                'message' => 'Payment already confirmed'
            ], 400); 
        }

        $qrCode = QRHelper::generateVietQr('MB', $orderPayment->total_amount, $orderPayment->code);

        if (!$qrCode) {
            return response()->json([
                'message' => 'Failed to generate QR code'
            ], 500);
        }

        $bank = [
            'bankCode' => env('MBBANK_ACCOUNT'),
            'accountName' => env('ACCOUNT_NAME'),
        ];
        
        return response()->json([
            'bank' => $bank,
            'order' =>  [
                'id' => $order->id,
                'code' => $order->code,
                'status' => $order->status,
            ],
            'payment' => [
                'code' => $orderPayment->code,
                'method' => $orderPayment->method,
                'status' => $orderPayment->status,
                'total_amount' => $order->total_amount,
            ],
            'qrCode' => $qrCode
        ], 200);
    }

    public function confirmPayment($id, Request $request) 
    {        
        $rules = [
            'file' => 'required|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors(),
            ], 422);
        }

        $order = Order::findOrFail($id);

        $payment = $order->orderPayment;

        if (!$payment) {
            return response()->json([
                'error' => 'Payment information not found',
            ], 422);
        }

        $imgUrl = $request['file']->store('payments', 'public');

        $payment->update([
            'receipt_url' => $imgUrl,
            'status' => 'waiting_for_confirmation',
            'date' => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Payment confirmation successful'
        ], 200);
    }
}
