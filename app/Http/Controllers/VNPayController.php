<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\Payment;



class VNPayController extends Controller
{
    // Tạo yêu cầu thanh toán VNPay
    public function createPayment(Request $request, $order_id)
    {
        try {
            // Xác thực order_id từ URL
            if (!is_numeric($order_id)) {
                return response()->json(['error' => 'Định dạng order_id không hợp lệ. Nó phải là một số nguyên.'], 400);
            }

            // Kiểm tra xem đơn hàng có tồn tại không
            $order = Order::find($order_id);
            if (!$order) {
                return response()->json(['error' => 'Đơn hàng không tồn tại. Vui lòng kiểm tra lại order_id.'], 404);
            }

            // Lấy phương thức thanh toán từ đơn hàng
            $paymentMethod = $order->payment_method;

            // Đặt trạng thái đơn hàng dựa trên phương thức thanh toán
            if ($paymentMethod === 'Cash on Delivery') {
                $order->status = 'Pending';
                $order->save();
                return response()->json(['message' => 'Đơn hàng đã được đặt thành công.'], 200);
            }

            // Nếu là VNpay Payment, tiếp tục với quy trình thanh toán
            if ($paymentMethod === 'VNpay Payment') {
                // Chuyển đổi số tiền sang đơn vị nhỏ nhất
                $amount = $order->total_amount * 100;

                // Kiểm tra số tiền giao dịch
                $minAmount = 10000; // Thay đổi theo quy định của ngân hàng
                $maxAmount = 50000000; // Thay đổi theo quy định của ngân hàng

                if ($amount < $minAmount || $amount > $maxAmount) {
                    return response()->json(['error' => 'Số tiền giao dịch không hợp lệ. Số tiền hợp lệ phải nằm trong khoảng ' . ($minAmount / 100) . ' và ' . ($maxAmount / 100) . ' VND.'], 400);
                }

                // Dữ liệu để gửi
                $inputData = [
                    "vnp_Version" => "2.1.0",
                    "vnp_TmnCode" => env('VNPAY_TMNCODE'),
                    "vnp_Amount" => $amount,
                    "vnp_Command" => "pay",
                    "vnp_CreateDate" => date('YmdHis'),
                    "vnp_CurrCode" => "VND",
                    "vnp_IpAddr" => $request->ip(),
                    "vnp_Locale" => $request->input('locale', 'vn'),
                    "vnp_OrderInfo" => "Thanh toán cho đơn hàng #" . $order->order_id,
                    "vnp_OrderType" => 'billpayment',
                    "vnp_ReturnUrl" => env('VNPAY_RETURN_URL'),
                    "vnp_TxnRef" => $order->order_id,
                ];

                // Nếu có mã ngân hàng, thêm vào dữ liệu
                if ($request->bank_code) {
                    $inputData['vnp_BankCode'] = $request->bank_code;
                }

                // Tạo checksum
                ksort($inputData);
                $hashdata = http_build_query($inputData);
                $vnp_Url = env('VNPAY_URL') . "?" . $hashdata;

                // Tính toán secure hash
                $vnpHashSecret = env('VNPAY_HASH_SECRET');
                if ($vnpHashSecret !== null) {
                    $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnpHashSecret);
                    $vnp_Url .= '&vnp_SecureHash=' . $vnpSecureHash;
                }

                // Trả về URL thanh toán
                return response()->json(['payment_url' => $vnp_Url]);
            }

            return response()->json(['error' => 'Phương thức thanh toán không hợp lệ.'], 400);

        } catch (\Exception $e) {
            // Xử lý lỗi
            return response()->json(['error' => 'Đã xảy ra lỗi trong quá trình tạo yêu cầu thanh toán: ' . $e->getMessage()], 500);
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

            // Ghi lại giao dịch vào bảng payments
            DB::table('payments')->insert([
                'order_id' => $orderId, // The order ID
                'transaction_no' => $transactionNo,
                'bank_code' => $request->input('vnp_BankCode'),
                'card_type' => $request->input('vnp_CardType'),
                'pay_date' => now(), // Use current timestamp
                'status' => 'success', // Transaction status
                'created_at' => now(), // Thêm created_at
                'updated_at' => now(), // Thêm updated_at
            ]);

            return response()->json(['message' => 'Payment successful. Order updated.'], 200);
        } else {
            // Cập nhật trạng thái đơn hàng thành 'Failed'
            DB::table('orders')->where('order_id', $orderId)->update(['status' => 'Failed']);

            return response()->json(['message' => 'Payment failed.'], 400);
        }
    }

    public function getAllPayments()
    {
        try {
            // Giả sử bạn có một bảng 'payments' lưu trữ tất cả các giao dịch thanh toán
            // Lấy tất cả thông tin từ bảng payments
            $payments = Payment::all();

            // Trả về danh sách các payment dưới dạng JSON
            return response()->json(['payments' => $payments], 200);
        } catch (\Exception $e) {
            // Trả về lỗi nếu có sự cố xảy ra
            return response()->json(['error' => 'Unable to fetch payments: ' . $e->getMessage()], 500);
        }
    }


}