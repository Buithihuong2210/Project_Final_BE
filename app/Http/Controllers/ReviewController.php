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
        // Xác thực yêu cầu, không cần product_id nữa
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

        // Lấy tất cả các sản phẩm từ order_items của đơn hàng
        $orderItems = $order->orderItems;

        // Kiểm tra nếu số lượng order_items và reviews không khớp
        if (count($orderItems) !== count($request->product_reviews)) {
            return response()->json(['message' => 'Number of reviews does not match the number of products in the order.'], 400);
        }

        // Kiểm tra nếu người dùng đã review order này rồi
        $existingReviews = Review::where('order_id', $order_id)->where('user_id', $user_id)->count();

        if ($existingReviews > 0) {
            return response()->json(['message' => 'You have already reviewed this order.'], 403);
        }

        // Lưu đánh giá cho từng sản phẩm
        foreach ($request->product_reviews as $index => $reviewData) {
            // Tìm sản phẩm tương ứng từ order_items dựa trên thứ tự
            $orderItem = $orderItems[$index];

            // Lưu đánh giá
            try {
                Review::create([
                    'content' => $reviewData['content'],
                    'rate' => $reviewData['rate'],
                    'user_id' => $user_id,
                    'product_id' => $orderItem->product_id, // Lấy product_id từ order_items theo thứ tự
                    'order_id' => $order_id, // Lưu order_id cho mỗi review
                ]);
                // Cập nhật rating cho sản phẩm sau khi tạo review
                $this->updateProductRating($orderItem->product_id);

            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to create review: ' . $e->getMessage()], 500);
            }
        }

        return response()->json(['message' => 'Reviews created successfully.'], 201);
    }
    public function updateProductRating($product_id)
    {
        // Retrieve the ratings for the product
        $ratings = Review::where('product_id', $product_id)->pluck('rate');
        // Calculate the average rating
        $averageRating = $ratings->avg();
        // Update the product rating
        Product::where('product_id', $product_id)->update(['rating' => round($averageRating, 2)]);
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
