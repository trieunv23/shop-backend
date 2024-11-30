<?php

namespace App\Helpers;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QRHelper
{
    public static function generateBankQR($bankCode, $accountNumber, $amount, $note)
    {
        $noteEncoded = urlencode($note); // Encode nội dung để tránh lỗi URL

        // Tạo URL VietQR
        $vietQRUrl = "https://vietqr.net/?bank=$bankCode&account=$accountNumber&amount=$amount&note=$noteEncoded";

        $qrCodePng = QrCode::format('png')->size(300)->generate($vietQRUrl);

        $qrCodeBase64 = base64_encode($qrCodePng);

        // Tạo mã QR code từ URL
        return $qrCodeBase64;
    }

    public static function generateVietQr($bankCode, $amount, $note)
    {
        if ($bankCode === 'MB') {
            $accountNumber = env('MBBANK_ACCOUNT');
        } else if ($bankCode === 'VIETTELPAY') {
            $accountNumber = env('VIETTELPAY_ACCOUNT');
        }

        $noteEncoded = urlencode($note);
        $accountNameEncoded = urlencode(env('ACCOUNT_NAME'));

        // Tạo URL VietQR
        $vietQRUrl = "https://img.vietqr.io/image/{$bankCode}-{$accountNumber}-compact2.jpg?amount={$amount}&addInfo={$noteEncoded}&accountName={$accountNameEncoded}";

        return $vietQRUrl;
    }
}
