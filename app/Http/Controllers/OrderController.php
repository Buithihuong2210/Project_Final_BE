<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Shipping;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\CartController;
use Illuminate\Database\QueryException;
use Exception;
use Carbon\Carbon;
use App\Models\ShoppingCart;
use App\Models\OrderItem;


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

            // Lấy user_id từ thông tin người dùng đã xác thực
            $userId = auth()->id();

            // Tìm giỏ hàng của người dùng
            $cart = ShoppingCart::with('items.product')->where('user_id', $userId)->first();

            if (!$cart) {
                return response()->json(['error' => 'Cart not found.'], 404);
            }

            // Lấy tổng giá trị giỏ hàng
            $cartController = new CartController();
            $cartData = $cartController->getCartWithSubtotal($cart);
            $subtotalOfCart = floatval($cartData['subtotal']);

            // Lấy thông tin vận chuyển
            $shipping = Shipping::findOrFail($request->shipping_id);
            $shippingCost = floatval($shipping->shipping_amount);

            // Xử lý mã giảm giá (nếu có)
            $discountAmount = 0;
            $voucherId = null;

            if ($request->voucher_id) {
                $voucher = Voucher::findOrFail($request->voucher_id);
                if ($voucher->status === 'active' && now()->between($voucher->start_date, $voucher->expiry_date)) {
                    $discountAmount = floatval($voucher->discount_amount);
                    $voucherId = $voucher->voucher_id;
                }
            }

            // Tính toán tổng số tiền
            $totalAmount = $subtotalOfCart + $shippingCost - $discountAmount;

            // Đặt trạng thái đơn hàng và thanh toán ban đầu
            $orderStatus = 'Pending';
            $paymentStatus = 'Pending';

            if ($request->payment_method == 'Cash on Delivery') {
                $orderStatus = 'Waiting for Delivery'; // Nếu chọn COD, chuyển sang Waiting for Delivery
            } else {
                $paymentStatus = 'Waiting for Payment'; // Đợi thanh toán cho các phương thức khác như VNPay
            }

            // Tạo đơn hàng
            $order = Order::create([
                'user_id' => $userId,
                'subtotal_of_cart' => round($subtotalOfCart, 2), // Cần có giá trị hợp lệ
                'total_amount' => round($totalAmount, 2), // Cần có giá trị hợp lệ
                'shipping_id' => $request->shipping_id,
                'voucher_id' => $voucherId,
                'shipping_name' => $shipping->name, // Đảm bảo có giá trị
                'shipping_cost' => $shippingCost, // Đảm bảo có giá trị
                'shipping_address' => $request->shipping_address,
                'payment_method' => $request->payment_method,
                'payment_status' => $paymentStatus,
                'status' => $orderStatus,
                'order_date' => now(),
            ]);


            // Tính toán ngày giao hàng dự kiến
            $processingDays = 2; // Số ngày xử lý (ví dụ)
            $shippingDays = 3; // Số ngày giao hàng (ví dụ)
            $expectedDeliveryDate = $this->calculateExpectedDeliveryDate($order->order_date, $processingDays, $shippingDays);

            // Cập nhật ngày giao hàng dự kiến vào đơn hàng
            $order->update(['expected_delivery_date' => $expectedDeliveryDate]);

            // Lưu các mục đơn hàng vào bảng order_items
            foreach ($cart->items as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->order_id,
                    'product_id' => $cartItem->product->product_id,
                    'quantity' => $cartItem->quantity,
                    'price' => floatval($cartItem->price),
                ]);
            }

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
                'payment_status' => $order->payment_status,
                'status' => $orderStatus,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
                'id' => $order->order_id,
                'order_date' => $order->order_date,
                'expected_delivery_date' => $expectedDeliveryDate,
                'cart_items' => $cart->items->map(function($item) {
                    return [
                        'product_id' => $item->product->product_id,
                        'name' => $item->product->name,
                        'price' => number_format($item->product->discounted_price, 2),
                        'quantity' => $item->quantity,
                    ];
                }),
            ], 201);

        } catch (QueryException $e) {
            return response()->json(['error' => 'Database error: ' . $e->getMessage()], 500);
        } catch (Exception $e) {
            return response()->json(['error' => 'An unexpected error occurred: ' . $e->getMessage()], 500);
        }
    }

    protected function calculateExpectedDeliveryDate($orderDate, $processingDays, $shippingDays)
    {
        // Chuyển đổi orderDate sang đối tượng Carbon
        $expectedDate = Carbon::parse($orderDate)->addDays($processingDays + $shippingDays);

        // Kiểm tra nếu ngày dự kiến rơi vào cuối tuần
        while ($expectedDate->isWeekend()) {
            $expectedDate->addDay(); // Nếu rơi vào cuối tuần, cộng thêm 1 ngày
        }

        return $expectedDate->format('Y-m-d'); // Trả về ngày theo định dạng 'YYYY-MM-DD'
    }

    public function showAll()
    {
        try {
            // Fetch all orders with related cart items and products
            $orders = Order::with('cart.items.product')->get();

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
                    'cart_items' => $order->cart->items->map(function ($item) {
                        return [
                            'product_id' => $item->product->product_id ?? null, // Ensure product_id is fetched
                            'name' => $item->product->name ?? 'N/A',
                            'price' => number_format($item->product->discounted_price, 2),
                            'price_of_cart_item' => number_format($item->price, 2),
                            'quantity' => $item->quantity,             // Get quantity
                        ];
                    }),
                ];
            }));
        } catch (QueryException $e) {
            return response()->json(['error' => 'Database error: ' . $e->getMessage()], 500);
        } catch (Exception $e) {
            return response()->json(['error' => 'An unexpected error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function updateOrderStatus(Request $request, $order_id)
    {
        // Tìm đơn hàng theo ID
        $order = Order::find($order_id);
        if (!$order) {
            return response()->json(['error' => 'Đơn hàng không tồn tại.'], 404);
        }

        // Kiểm tra nếu đơn hàng đã hoàn thành, không được cập nhật nữa
        if ($order->status === 'Completed') {
            return response()->json(['error' => 'Đơn hàng đã hoàn thành, không thể cập nhật trạng thái.'], 400);
        }

        // Kiểm tra trạng thái thanh toán, nếu chưa thanh toán thì không cập nhật trạng thái
        if ($order->payment_status === 'Waiting for Payment') {
            return response()->json(['error' => 'Đơn hàng chưa được thanh toán, trạng thái vẫn là Pending.'], 400);
        }

        // Kiểm tra phương thức thanh toán
        if ($order->payment_method === 'Cash on Delivery') {
            // Nếu là COD và trạng thái là 'Waiting for Delivery'
            if ($order->status === 'Waiting for Delivery') {
                // Xử lý logic cho trạng thái 'Waiting for Delivery'
                return response()->json(['error' => 'Đơn hàng đang chờ giao hàng, không thể cập nhật trạng thái.'], 400);
            } elseif ($order->status === 'Delivered') {
                // Khi hàng đã được giao và cần chuyển sang trạng thái 'Completed'
                $order->status = 'Completed';
            }
        } elseif ($order->payment_method === 'VNpay Payment') {
            // Nếu là VNPay và trạng thái là 'Waiting for Delivery'
            if ($order->status === 'Waiting for Delivery') {
                // Xử lý logic cho trạng thái 'Waiting for Delivery'
                return response()->json(['error' => 'Đơn hàng đang chờ giao hàng, không thể cập nhật trạng thái.'], 400);
            } elseif ($order->status === 'Delivered') {
                // Khi hàng đã được giao và cần chuyển sang trạng thái 'Completed'
                $order->status = 'Completed';
            }
        }


        // Lưu trạng thái đơn hàng nếu có sự thay đổi
        if ($order->isDirty('status')) {
            $order->save();
        }

        return response()->json(['message' => 'Trạng thái đơn hàng đã được cập nhật thành công.', 'order' => $order], 200);
    }

    public function confirmDelivery($order_id)
    {
        try {
            // Lấy thông tin đơn hàng
            $order = DB::table('orders')->where('order_id', $order_id)->first();

            // Kiểm tra nếu đơn hàng không tồn tại
            if (!$order) {
                return response()->json(['error' => 'Order not found'], 404);
            }

            // Lưu giá trị payment_method vào biến
            $paymentMethod = trim($order->payment_method); // Loại bỏ khoảng trắng

            // Kiểm tra phương thức thanh toán
            if ($paymentMethod === 'Cash on Delivery') {
                // Nếu là COD, cập nhật trạng thái đơn hàng và thanh toán
                if ($order->status === 'Delivered') {
                    return response()->json(['error' => 'Order has already been delivered'], 400);
                }

                // Cập nhật trạng thái đơn hàng và thanh toán
                DB::table('orders')->where('order_id', $order_id)->update([
                    'status' => 'Delivered',            // Đơn hàng đã giao
                    'payment_status' => 'Paid',         // Đã thanh toán khi nhận hàng
                ]);

                // Lấy số tiền cụ thể của đơn hàng
                $amount = number_format($order->total_amount, 0) . ' VND';

                return response()->json([
                    'message' => 'Order delivered and payment confirmed successfully',
                    'amount' => $amount,  // Số tiền của đơn hàng cụ thể
                ], 200);

            } elseif ($paymentMethod === 'VNpay Payment') {
                // Nếu là VNPay, chỉ cần cập nhật trạng thái đơn hàng
                if ($order->status === 'Delivered') {
                    return response()->json(['error' => 'Order has already been delivered'], 400);
                }

                // Giả định xử lý kết quả thanh toán từ VNPay
                $paymentStatus = $this->checkVnPayPaymentStatus($order_id); // Hàm kiểm tra trạng thái từ VNPay

                // Nếu thanh toán thất bại
                if ($paymentStatus === 'Failed') {
                    // Cập nhật trạng thái đơn hàng và trạng thái thanh toán
                    DB::table('orders')->where('order_id', $order_id)->update([
                        'status' => 'Failed',              // Đơn hàng thất bại
                        'payment_status' => 'Failed',      // Thanh toán thất bại
                    ]);

                    return response()->json([
                        'message' => 'VNPay payment failed, order status updated to failed.',
                    ], 400);
                }

                // Cập nhật trạng thái đơn hàng
                DB::table('orders')->where('order_id', $order_id)->update([
                    'status' => 'Delivered',            // Đơn hàng đã giao
                ]);

                // Lấy số tiền của đơn hàng
                $amount = number_format($order->total_amount, 0) . ' VND'; // Định dạng số tiền

                return response()->json([
                    'message' => 'Order delivered successfully',
                    'amount' => $amount,  // Trả về số tiền của đơn hàng
                ], 200);

            } else {
                // Nếu phương thức thanh toán không hợp lệ
                return response()->json(['error' => 'Invalid payment method'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to confirm delivery: ' . $e->getMessage()], 500);
        }
    }

    public function getTotalPaymentsForBothMethods()
    {
        try {
            // Tính tổng số tiền của các đơn hàng sử dụng phương thức thanh toán là VNPay và đã hoàn tất (Completed)
            $totalVNPay = DB::table('orders')
                ->where('payment_method', 'VNpay Payment')
                ->where('status', 'Completed')
                ->sum('total_amount'); // Tính tổng số tiền cho VNPay

            // Tính tổng số tiền của các đơn hàng sử dụng phương thức thanh toán là Cash on Delivery (COD) và đã hoàn tất (Completed)
            $totalCOD = DB::table('orders')
                ->where('payment_method', 'Cash on Delivery')
                ->where('status', 'Completed')
                ->sum('total_amount'); // Tính tổng số tiền cho COD

            // Tổng cộng cả hai phương thức
            $totalAmount = $totalVNPay + $totalCOD;

            // Định dạng số tiền cho từng phương thức
            $formattedVNPay = number_format($totalVNPay, 0) . ' VND';
            $formattedCOD = number_format($totalCOD, 0) . ' VND';
            $formattedTotal = number_format($totalAmount, 0) . ' VND';

            return response()->json([
                'total_VNPay' => $formattedVNPay,  // Tổng tiền VNPay
                'total_COD' => $formattedCOD,      // Tổng tiền COD
                'total_amount' => $formattedTotal  // Tổng tiền của cả hai phương thức
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to fetch total payments: ' . $e->getMessage()], 500);
        }
    }
    public function getCanceledOrders()
    {
        try {
            // Lấy danh sách các đơn hàng có status là 'Canceled'
            $canceledOrders = Order::where('status', 'Canceled')->get();

            // Kiểm tra nếu không có đơn hàng nào bị hủy
            if ($canceledOrders->isEmpty()) {
                return response()->json(['message' => 'No canceled orders found.'], 200);
            }

            // Trả về danh sách các đơn hàng bị hủy
            return response()->json([
                'message' => 'Canceled orders retrieved successfully.',
                'orders' => $canceledOrders
            ], 200);

        } catch (\Exception $e) {
            // Xử lý lỗi nếu có
            return response()->json(['error' => 'Unable to retrieve canceled orders: ' . $e->getMessage()], 500);
        }
    }

    public function getOrderItems($order_id)
    {
        // Tìm đơn hàng và lấy các item
        $order = Order::with('orderItems.product')->find($order_id);

        // Kiểm tra xem đơn hàng có tồn tại không
        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        // Kiểm tra xem có item nào không
        if ($order->orderItems->isEmpty()) {
            return response()->json(['message' => 'No order items found for this order.'], 404);
        }

        // Lấy các mục đơn hàng
        $orderItems = $order->orderItems->map(function($item) {
            return [
                'id' => $item->id,
                'order_id' => $item->order_id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'quantity' => $item->quantity,
                'price' => $item->price,
            ];
        });

        return response()->json($orderItems);
    }

    public function checkVnPayPaymentStatus($order_id)
    {
        // Giả định bạn gọi API VNPay để kiểm tra trạng thái thanh toán
        // Ở đây bạn có thể gọi API của VNPay để lấy trạng thái thực tế
        // hoặc giả định trả về kết quả cho mục đích thử nghiệm.

        // Giả sử trạng thái thanh toán từ VNPay
        // Trả về 'Success' hoặc 'Failed'
        // Bạn có thể thay đổi logic này theo nhu cầu thực tế
        $status = 'Success'; // Hoặc 'Failed'

        return $status;
    }

}
