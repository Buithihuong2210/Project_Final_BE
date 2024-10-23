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

}
