<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Order;

class VNPayController extends Controller
{
    // Tạo yêu cầu thanh toán VNPay
    public function createPayment(Request $request)
    {
        try {
            // Validate input data
            $request->validate([
                'order_id' => 'required|integer|exists:orders,order_id',
                'bank_code' => 'nullable|string',
            ], [
                'order_id.exists' => 'Order does not exist. Please check the order_id.',
                'order_id.required' => 'order_id is required.',
                'order_id.integer' => 'order_id must be an integer.',
            ]);

            // Retrieve order information
            $order = Order::find($request->order_id);

            // Convert amount to the smallest unit
            $amount = $order->total_amount * 100;

            // Check transaction amount
            $minAmount = 10000; // Change according to bank regulations
            $maxAmount = 50000000; // Change according to bank regulations

            if ($amount < $minAmount || $amount > $maxAmount) {
                return response()->json(['error' => 'Invalid transaction amount. Valid amount must be between ' . ($minAmount / 100) . ' and ' . ($maxAmount / 100) . ' VND.'], 400);
            }

            // Data to send
            $inputData = [
                "vnp_Version" => "2.1.0",
                "vnp_TmnCode" => env('VNPAY_TMNCODE'),
                "vnp_Amount" => $amount,
                "vnp_Command" => "pay",
                "vnp_CreateDate" => date('YmdHis'),
                "vnp_CurrCode" => "VND",
                "vnp_IpAddr" => $request->ip(),
                "vnp_Locale" => $request->input('locale', 'vn'),
                "vnp_OrderInfo" => "Payment for order #" . $order->order_id,
                "vnp_OrderType" => 'billpayment',
                "vnp_ReturnUrl" => env('VNPAY_RETURN_URL'),
                "vnp_TxnRef" => $order->order_id,
            ];

            // If there is a bank code, add it to the data
            if ($request->bank_code) {
                $inputData['vnp_BankCode'] = $request->bank_code;
            }

            // Create checksum
            ksort($inputData);
            $hashdata = http_build_query($inputData);
            $vnp_Url = env('VNPAY_URL') . "?" . $hashdata;

            // Calculate secure hash
            $vnpHashSecret = env('VNPAY_HASH_SECRET');
            if ($vnpHashSecret !== null) {
                $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnpHashSecret);
                $vnp_Url .= '&vnp_SecureHash=' . $vnpSecureHash;
            }

            // Return payment URL
            return response()->json(['payment_url' => $vnp_Url]);

        } catch (\Exception $e) {
            // Handle errors
            return response()->json(['error' => 'An error occurred while creating the payment request: ' . $e->getMessage()], 500);
        }
    }

    // Xử lý kết quả sau khi thanh toán VNPay
    public function handlePaymentReturn(Request $request)
    {
        // Lấy các tham số từ request
        $transactionNo = $request->input('vnp_TransactionNo');
        $orderId = $request->input('vnp_TxnRef'); // ID đơn hàng
        $responseCode = $request->input('vnp_ResponseCode');
        $payDate = $request->input('vnp_PayDate');

        // Kiểm tra mã phản hồi
        if ($responseCode === '00') {
            // Cập nhật trạng thái đơn hàng thành 'Completed'
            DB::table('orders')->where('order_id', $orderId)->update(['status' => 'Completed']);

            // Ghi lại giao dịch vào bảng transactions
            DB::table('transactions')->insert([
                'order_id' => $orderId,
                'transaction_no' => $transactionNo,
                'bank_code' => $request->input('vnp_BankCode'),
                'card_type' => $request->input('vnp_CardType'),
                'pay_date' => $payDate,
                'status' => 'success', // Trạng thái giao dịch
            ]);

            return response()->json(['message' => 'Payment successful. Order updated.'], 200);
        } else {
            // Cập nhật trạng thái đơn hàng thành 'Failed'
            DB::table('orders')->where('order_id', $orderId)->update(['status' => 'Failed']);

            return response()->json(['message' => 'Payment failed.'], 400);
        }
    }

}