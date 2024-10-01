<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\ProductController;

class ReviewController extends Controller
{
    // Get all reviews with user and product details
    public function index()
    {
        try {
            $reviews = Review::with(['user', 'product'])->get();
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

            // Trigger updateRating in ProductController
            $productController = new ProductController();
            $productController->updateRating($product_id);

            return response()->json($review->load(['user', 'product']), 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error occurred while creating review', 'error' => $e->getMessage()], 500);
        }
    }

    // Show a single review
    public function show($id)
    {
        try {
            $review = Review::with(['user', 'product'])->find($id);

            if ($review) {
                return response()->json($review, 200);
            } else {
                return response()->json(['message' => 'Review not found'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve review', 'error' => $e->getMessage()], 500);
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

                // Trigger updateRating in ProductController
                $productController = new ProductController();
                $productController->updateRating($review->product_id);

                return response()->json($review, 200);
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

                // Trigger updateRating in ProductController
                $productController = new ProductController();
                $productController->updateRating($product_id);

                return response()->json(['message' => 'Review deleted'], 200);
            } else {
                return response()->json(['message' => 'Review not found'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete review', 'error' => $e->getMessage()], 500);
        }
    }
}
