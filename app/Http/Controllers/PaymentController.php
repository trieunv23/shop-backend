<?php

namespace App\Http\Controllers;

use App\Helpers\QRHelper;
use App\Helpers\StringHelper;
use App\Models\Order;
use App\Models\OrderPayment;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Faker\Provider\ar_EG\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function processPayment($id) 
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'User not authenticated'
            ], 401);
        }

        $user = Auth::user();

        $order = Order::where('user_id', $user->id)->where('id', $id)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $totalAmount = $order->total_amount;
        $paymentCode = $order->orderPayment->payment_code;

        $qrCode = QRHelper::generateVietQr('MB', $totalAmount, $paymentCode);

        $bank = [
            'bankCode' => env('MBBANK_ACCOUNT'),
            'accountName' => env('ACCOUNT_NAME'),
            'paymentCode' => $paymentCode,
        ];
        

        return response()->json([
            'bank' => $bank,
            'order' => StringHelper::convertKeysToCamelCase($order->toArray()),
            'qrCode' => $qrCode
        ], 200);
    }

    public function confirmPayment(Request $request) 
    {
        $validated = $request->validate([ 
            'order_id' => 'required|integer', 
            'file' => 'required|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $order = Order::where('id', $validated['order_id'])->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $orderPayment = $order->orderPayment;

        try {
            $img_url = $validated['file']->store('payments', 'public');
        } catch (\Exception $e) {
            return response()->json(['message' => 'File upload failed', 'error' => $e->getMessage()], 500);
        }

        $orderPayment->update([
            'payment_image' => $img_url,
            'payment_status' => 'waiting_for_confirmation',
            'payment_date' => Carbon::now()->setTimezone('Asia/Ho_Chi_Minh'),
        ]);

        return response()->json([
            'message' => 'Payment success.'
        ], 200);
    }

    public function getPaymentsByAdmin()
    {
        // Authentication admin

        $orderPayments = OrderPayment::with('order')->get();

        if ($orderPayments->isEmpty()) {
            return response()->json(['message' => 'No payments found'], 404);
        }

        $payments = $orderPayments->map(function ($orderPayment) {
            return [
                'id' => $orderPayment->id,
                'order_code' => $orderPayment->order->order_code,
                'payment_amount' => $orderPayment->payment_amount,
                'payment_code' => $orderPayment->payment_code,
                'payment_date' => $orderPayment->payment_date,
                'payment_image' => $orderPayment->payment_image,
                'payment_method' => $orderPayment->payment_method,
                'payment_status' => $orderPayment->payment_status,
            ];
        });

        return response()->json([
            'message' => 'Payments retrieved successfully',
            'payments' => StringHelper::convertListKeysToCamelCase($payments->toArray()),
        ], 200);
    }

    public function confirmPaymentByAdmin(Request $request) 
    {
        // Authentication admin

        $validated = $request->validate([ 
            'payment_id' => 'required|integer', 
        ]);

        $payment = OrderPayment::where('id', $validated['payment_id'])->first();

        if (!$payment) {
            return response()->json(['message' => 'No payment found'], 404);
        }

        $payment->update([
            'payment_status' => 'confirmed',
        ]);

        return response()->json([
            'message' => 'Confirm payment success'
        ], 200);
    }

    public function vnpayPayment(Request $request)
    {
        $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
        $vnp_Returnurl = "https://localhost/vnpay_php/vnpay_return.php";
        $vnp_TmnCode = "BFN2SBUX";
        $vnp_HashSecret = "QPKJCDA9NNU54TOIQTLVZ0Z1M0CJH1CB";

        $vnp_TxnRef = '1000'; // Mã đơn hàng (thực tế sẽ lấy từ cơ sở dữ liệu)
        $vnp_OrderInfo = 'Thanh Toán Hóa Đơn';
        $vnp_OrderType = 'Easy Buy';
        $vnp_Amount = 10000 * 100; // Số tiền (10,000 VND)
        $vnp_Locale = 'VN';
        $vnp_BankCode = 'NCB'; // Chọn ngân hàng
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];

        // Tạo dữ liệu để gửi đi
        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,
        );

        if (isset($vnp_BankCode) && $vnp_BankCode != "") {
            $inputData['vnp_BankCode'] = $vnp_BankCode;
        }

        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;

        if (isset($vnp_HashSecret)) {
            // Tạo chữ ký bảo mật
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }

        // Trả về URL thanh toán cho người dùn

        /*
        $qrCode = new QrCode($vnp_Url);
        $qrCode->setSize(300);

        // Tạo hình ảnh PNG từ mã QR
        $writer = new PngWriter();
        $qrImage = $writer->write($qrCode);
        */

        $returnData = array(
            'code' => '00',
            'message' => 'success',
            'data' => $vnp_Url,
            // 'qr_code' => $qrImage,
        );

        // Trả về dữ liệu dưới dạng JSON
        return response()->json($returnData);
    }
}
