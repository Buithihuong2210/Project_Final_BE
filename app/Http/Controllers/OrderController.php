<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Shipping;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ShoppingCart;
use App\Http\Controllers\CartController;
use Illuminate\Database\QueryException;
use Exception;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Validate the incoming request
            $request->validate([
                'shipping_id' => 'required|exists:shippings,id',
                'shipping_address' => 'required|string',
                'voucher_id' => 'nullable|exists:vouchers,voucher_id',
                'payment_method' => 'required|in:Cash on Delivery,VNpay Payment',
            ]);

            $userId = auth()->id();
            $cart = ShoppingCart::with('items.product')->where('user_id', $userId)->first();

            if (!$cart) {
                return response()->json(['error' => 'Cart not found.'], 404);
            }

            $cartController = new CartController();
            $cartData = $cartController->getCartWithSubtotal($cart);
            $subtotalOfCart = floatval($cartData['subtotal']);
            $shipping = Shipping::findOrFail($request->shipping_id);
            $shippingCost = floatval($shipping->shipping_amount);

            $discountAmount = 0;
            $voucherId = null;

            if ($request->voucher_id) {
                $voucher = Voucher::findOrFail($request->voucher_id);
                if ($voucher->status === 'active' && now()->between($voucher->start_date, $voucher->expiry_date)) {
                    $discountAmount = floatval($voucher->discount_amount);
                    $voucherId = $voucher->voucher_id;
                }
            }

            // Calculate total amount
            $totalAmount = $subtotalOfCart + $shippingCost - $discountAmount;

            // Create the order and set the initial status and payment status
            $order = Order::create([
                'user_id' => $userId,
                'subtotal_of_cart' => $subtotalOfCart,
                'total_amount' => round($totalAmount, 2),
                'shipping_id' => $request->shipping_id,
                'voucher_id' => $voucherId,
                'shipping_name' => $shipping->name,
                'shipping_cost' => $shippingCost,
                'shipping_address' => $request->shipping_address,
                'status' => 'Processing',
                'payment_method' => $request->payment_method,
                'payment_status' => ($request->payment_method == 'Cash on Delivery') ? 'Pending' : 'Pending', // Đặt trạng thái thanh toán ban đầu là Pending
            ]);

            // Xử lý thanh toán dựa trên phương thức thanh toán được chọn
            if ($request->payment_method === 'VNpay Payment') {
                // Gọi cổng thanh toán VNpay
                $paymentResult = $this->processVNPayPayment($order); // Gọi hàm xử lý VNpay

                if ($paymentResult['status'] === 'success') {
                    $order->update(['payment_status' => 'Paid']); // Cập nhật trạng thái thanh toán thành Paid
                } else {
                    $order->update(['payment_status' => 'Failed']); // Cập nhật trạng thái thanh toán nếu thất bại
                }
            } // Nếu là Cash on Delivery, không cần thay đổi gì vì payment_status vẫn là Pending

            return response()->json([
                'user_id' => $order->user_id,
                'shipping_address' => $order->shipping_address,
                'shipping_id' => $order->shipping_id,
                'voucher_id' => $order->voucher_id,
                'shipping_name' => $order->shipping_name,
                'subtotal_of_cart' => number_format($order->subtotal_of_cart, 2),
                'shipping_cost' => number_format($order->shipping_cost, 2),
                'discount_amount' => number_format($discountAmount, 2),
                'total_amount' => number_format($order->total_amount, 2),
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status, // Thêm payment_status vào response
                'status' => 'Processing',
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
                'id' => $order->order_id,
            ], 201);

        } catch (QueryException $e) {
            return response()->json(['error' => 'Database error: ' . $e->getMessage()], 500);
        } catch (Exception $e) {
            return response()->json(['error' => 'An unexpected error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function showAll()
    {
        try {
            // Fetch all orders from the database
            $orders = Order::all();

            // Return only relevant fields in the JSON response
            return response()->json($orders->map(function ($order) {
                return [
                    'order_id' => $order->order_id,
                    'user_id' => $order->user_id,
                    'shipping_address' => $order->shipping_address,
                    'shipping_id' => $order->shipping_id,
                    'voucher_id' => $order->voucher_id,
                    'shipping_name' => $order->shipping_name,
                    'subtotal_of_cart' => number_format($order->subtotal_of_cart, 2),
                    'shipping_cost' => number_format($order->shipping_cost, 2),
                    'total_amount' => number_format($order->total_amount, 2),
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status,
                    'status' => $order->status,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ];
            }));

        } catch (QueryException $e) {
            return response()->json(['error' => 'Database error: ' . $e->getMessage()], 500);
        } catch (Exception $e) {
            return response()->json(['error' => 'An unexpected error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            // Validate the incoming request
            $request->validate([
                'status' => 'required|in:Processing,Shipping,Delivered,Completed', // Ensure valid status
            ]);

            // Find the order by ID
            $order = Order::findOrFail($id);
            $order->status = $request->status; // Update the status
            $order->save(); // Save the changes

            return response()->json(['message' => 'Order status updated successfully.', 'order' => $order], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Order not found'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred while updating the order status.', 'error' => $e->getMessage()], 500);
        }
    }

    protected function processVNPayPayment(Order $order)
    {
        // Thực hiện thanh toán qua VNpay
        // Thực hiện logic gọi VNpay API và nhận kết quả thanh toán
        // Đây là một ví dụ giả định, bạn cần thay đổi theo cách bạn thực hiện thanh toán

        // Giả lập xử lý thanh toán thành công
        return [
            'status' => 'success', // Hoặc 'failed' tùy thuộc vào kết quả
        ];

        // Nếu thất bại, có thể trả về
        // return ['status' => 'failed'];
    }
}
