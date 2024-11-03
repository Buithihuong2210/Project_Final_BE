<?php
namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    public function getOrderItems($orderId)
    {
        // Lấy đơn hàng dựa trên order_id
        $order = Order::with('items')->find($orderId);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Trả về thông tin đơn hàng cùng với các mục đơn hàng
        return response()->json($order->items, 200);
    }
}
