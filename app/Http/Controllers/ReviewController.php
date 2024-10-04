<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
    public function store(Request $request, $product_id)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'content' => 'required|string',
                'rate' => 'required|integer|between:1,5',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            // Check if the product exists
            $product = Product::find($product_id);
            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }

            // Create the review
            $review = Review::create(array_merge($request->only(['content', 'rate']), [
                'user_id' => auth()->id(),
                'product_id' => $product_id,
            ]));

            // Update product rating
            $this->updateProductRating($product_id);

            return response()->json($review->load(['user', 'product']), 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error occurred while creating review', 'error' => $e->getMessage()], 500);
        }
    }

    // Update an existing review and recalculate product rating
    public function update(Request $request, $id)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'content' => 'sometimes|required|string',
            'rate' => 'sometimes|required|integer|between:1,5',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        try {
            // Find and update the review
            $review = Review::find($id);

            if ($review) {
                $review->update($request->all());

                // Update product rating
                $this->updateProductRating($review->product_id);

                return response()->json($review->load(['user', 'product']), 200);
            } else {
                return response()->json(['message' => 'Review not found'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update review', 'error' => $e->getMessage()], 500);
        }
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
