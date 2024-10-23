<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    // Get all reviews for a specific product
    public function getReviewsByProduct($product_id)
    {
        try {
            // Check if the product exists
            $product = Product::find($product_id);
            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }

            // Get all reviews for the specified product
            $reviews = Review::with(['user', 'product'])->where('product_id', $product_id)->get();

            return response()->json($reviews, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve reviews', 'error' => $e->getMessage()], 500);
        }
    }

    // Store a new review and update product rating
    public function store(Request $request, $order_id)
    {
        // Xác thực yêu cầu
        $request->validate([
            'product_reviews' => 'required|array',
            'product_reviews.*.content' => 'required|string',
            'product_reviews.*.rate' => 'required|integer|between:1,5',
        ]);

        // Lấy user_id từ thông tin người dùng đã xác thực
        $user_id = Auth::id();

        // Tìm đơn hàng dựa trên order_id từ request
        $order = Order::find($order_id);
        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        // Kiểm tra xem trạng thái đơn hàng có phải là "Completed" không
        if ($order->status !== 'Completed') {
            return response()->json(['message' => 'You can only review products for Completed orders.'], 403);
        }

        // Lưu đánh giá cho từng sản phẩm trong order
        foreach ($request->product_reviews as $reviewData) {
            // Tìm sản phẩm trong order_items
            $orderItem = $order->orderItems()->first(); // Lấy sản phẩm đầu tiên trong order

            if (!$orderItem) {
                return response()->json(['message' => 'No products found in this order.'], 404);
            }

            // Lưu đánh giá
            try {
                Review::create([
                    'content' => $reviewData['content'],
                    'rate' => $reviewData['rate'],
                    'user_id' => $user_id,
                    'product_id' => $orderItem->product_id, // Sử dụng product_id từ order_items
                    'order_id' => $order_id, // Lưu order_id cho mỗi review
                ]);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to create review: ' . $e->getMessage()], 500);
            }
        }

        return response()->json(['message' => 'Reviews created successfully.'], 201);
    }

    public function update(Request $request, $order_id, $review_id)
    {
        // Xác thực yêu cầu
        $request->validate([
            'content' => 'required|string',
            'rate' => 'required|integer|between:1,5',
        ]);

        // Lấy user_id từ thông tin người dùng đã xác thực
        $user_id = Auth::id();

        // Tìm đánh giá dựa trên review_id và kiểm tra quyền sở hữu
        $review = Review::where('review_id', $review_id)
            ->where('user_id', $user_id)
            ->where('order_id', $order_id)
            ->first();

        if (!$review) {
            return response()->json(['message' => 'Review not found for this user and order.'], 404);
        }

        // Cập nhật nội dung và đánh giá
        $review->content = $request->content; // Chỉ lấy content để lưu vào cột content
        $review->rate = $request->rate;       // Lưu rating riêng biệt vào cột rate
        // Lưu thay đổi
        $review->save();

        return response()->json(['message' => 'Review updated successfully.'], 200);
    }

    // Delete a review and recalculate product rating
    public function destroy($id)
    {
        try {
            // Find and delete the review
            $review = Review::find($id);

            if ($review) {
                $product_id = $review->product_id;

                // Delete the review
                $review->delete();

                // Update product rating
                $this->updateProductRating($product_id);

                return response()->json(['message' => 'Review deleted'], 200);
            } else {
                return response()->json(['message' => 'Review not found'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete review', 'error' => $e->getMessage()], 500);
        }
    }

    // Update the average product rating
    private function updateProductRating($product_id)
    {
        // Get the product and its reviews
        $product = Product::find($product_id);
        if (!$product) {
            return;
        }

        // Calculate the average rating
        $averageRating = Review::where('product_id', $product_id)->avg('rate');

        // Update the product's rating field
        $product->rating = round($averageRating, 2);
        $product->save();
    }

    public function countReviewsByProduct($product_id)
    {
        try {
            // Kiểm tra xem sản phẩm có tồn tại không
            $product = Product::find($product_id);
            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }

            // Tính tổng số lượng review của sản phẩm
            $totalReviews = Review::where('product_id', $product_id)->count();

            // Trả về tổng số lượng review
            return response()->json([
                'product_id' => $product_id,
                'total_reviews' => $totalReviews
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to count reviews', 'error' => $e->getMessage()], 500);
        }
    }

}
