<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class MoMoPaymentController extends Controller
{
    public function createPayment(Request $request)
    {
        // Lấy các thông tin từ file .env
        $endpoint = env('MOMO_ENDPOINT');
        $accessKey = env('MOMO_ACCESS_KEY');
        $secretKey = env('MOMO_SECRET_KEY');
        $partnerCode = env('MOMO_PARTNER_CODE');
        $redirectUrl = 'https://your_redirect_url.com'; // Thay thế bằng URL của bạn
        $ipnUrl = 'https://your_ipn_url.com'; // Thay thế bằng IPN URL của bạn
        $orderInfo = 'Thanh toán đơn hàng Alice Skin';
        $amount = $request->input('amount');
        $orderId = time() . "";
        $requestId = time() . "";
        $extraData = ""; // Extra data nếu cần

        // Loại thanh toán (có thể thay đổi)
        $requestType = 'captureWallet';
        $autoCapture = true;
        $lang = 'vi';

        // Chuẩn bị dữ liệu để tạo chữ ký HMAC SHA256
        $rawHash = "accessKey=" . $accessKey .
            "&amount=" . $amount .
            "&extraData=" . $extraData .
            "&ipnUrl=" . $ipnUrl .
            "&orderId=" . $orderId .
            "&orderInfo=" . $orderInfo .
            "&partnerCode=" . $partnerCode .
            "&redirectUrl=" . $redirectUrl .
            "&requestId=" . $requestId .
            "&requestType=" . $requestType;

        // Tạo chữ ký HMAC SHA256
        $signature = hash_hmac("sha256", $rawHash, $secretKey);

        // Chuẩn bị dữ liệu gửi đi
        $data = [
            'partnerCode' => $partnerCode,
            'partnerName' => "Alice Skin",
            'storeId' => 'AliceSkinStore',
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'requestType' => $requestType,
            'ipnUrl' => $ipnUrl,
            'redirectUrl' => $redirectUrl,
            'autoCapture' => $autoCapture,
            'lang' => $lang,
            'extraData' => $extraData,
            'signature' => $signature
        ];

        try {
            // Tạo client Guzzle để gửi yêu cầu
            $client = new Client();
            $response = $client->post($endpoint, [
                'json' => $data
            ]);

            $responseData = json_decode($response->getBody(), true);

            // Kiểm tra kết quả trả về
            if (isset($responseData['payUrl'])) {
                return redirect()->to($responseData['payUrl']);
            } else {
                return response()->json([
                    'message' => 'Không thể tạo liên kết thanh toán MoMo.',
                    'data' => $responseData
                ], 400);
            }

        } catch (\Exception $e) {
            // Ghi log lỗi nếu có
            Log::error('MoMo Payment Error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Đã xảy ra lỗi trong quá trình thanh toán',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
