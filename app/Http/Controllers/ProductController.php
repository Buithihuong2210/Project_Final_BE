<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review; // Assuming there is a Review model for handling reviews
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Exception;

class ProductController extends Controller
{
    // List all products with their associated brands and average rating
    public function index()
    {
        try {
            // Retrieve all products with their associated brands
            $products = Product::with('brand')->get();

            $products = $products->map(function ($product) {
                return [
                    'product_id' => $product->product_id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => $product->price,
                    'discount' => $product->discount,
                    'discounted_price' => $product->discounted_price,
                    'quantity' => $product->quantity,
                    'brand_id' => $product->brand_id,
                    'images' => $product->images,
                    'status' => $product->status,
                    'short_description' => $product->short_description,
                    'volume' => $product->volume,
                    'nature' => $product->nature,
                    'product_type' => $product->product_type, // New field
                    'main_ingredient' => $product->main_ingredient, // New field
                    'target_skin_type' => $product->target_skin_type, // New field
                    'rating' => $product->rating,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                    'brand' => $product->brand
                ];
            });

            return response()->json($products, 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching products.', 'error' => $e->getMessage()], 500);
        }
    }


    // Store a new product
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric',
                'discount' => 'nullable|numeric|min:0|max:100',
                'quantity' => 'nullable|numeric|min:0|max:500',
                'brand_id' => 'required|exists:brands,brand_id',
                'images' => 'required|array',
                'images.*' => 'nullable|url|ends_with:.jpg,.jpeg,.png,.gif,.svg',
                'short_description' => 'nullable|string',
                'volume' => 'nullable|numeric',
                'nature' => 'nullable|string|in:new,best seller,exclusive',
                'product_type' => 'nullable|string', // New field validation
                'main_ingredient' => 'nullable|string', // New field validation
                'target_skin_type' => 'nullable|string', // New field validation

            ]);

            $product_data = $request->all();
            $product_data['status'] = 'available';

            // Chỉ lưu mảng images mà không cần json_encode
            // Trường images sẽ được tự động xử lý thành JSON nếu bạn đã khai báo trong $casts

            $discount = $request->input('discount', 0);
            $product_data['discounted_price'] = $discount > 0 && $discount < 100
                ? round($product_data['price'] * (1 - $discount / 100), 2)
                : round($product_data['price'], 2);

            $product_data['rating'] = $request->input('rating', 0);

            $product = Product::create($product_data);

            return response()->json($product, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred while creating the product.', 'error' => $e->getMessage()], 500);
        }
    }

    // Show a specific product by ID
    public function show($product_id)
    {
        try {
            $product = Product::with('brand')->findOrFail($product_id);

            return response()->json([
                'product_id' => $product->product_id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'discount' => $product->discount,
                'discounted_price' => $product->discounted_price,
                'quantity' => $product->quantity,
                'brand_id' => $product->brand_id,
                'images' => $product->images,
                'status' => $product->status,
                'short_description' => $product->short_description,
                'volume' => $product->volume,
                'nature' => $product->nature,
                'rating' => $product->rating, // Add the rating
                'product_type' => $product->product_type, // New field
                'main_ingredient' => $product->main_ingredient, // New field
                'target_skin_type' => $product->target_skin_type, // New field
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
                'brand' => $product->brand
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Product not found'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching the product.', 'error' => $e->getMessage()], 500);
        }
    }

    // Update a product
    public function update(Request $request, $product_id)
    {
        try {
            $product = Product::findOrFail($product_id);

            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'sometimes|required|numeric',
                'discount' => 'nullable|numeric|min:0|max:100',
                'brand_id' => 'sometimes|required|exists:brands,brand_id',
                'images' => 'sometimes|nullable|array',
                'images.*' => 'nullable|url|ends_with:.jpg,.jpeg,.png,.gif,.svg',
                'short_description' => 'nullable|string',
                'volume' => 'nullable|numeric',
                'nature' => 'nullable|string|in:new,best seller,exclusive',
                'product_type' => 'sometimes|nullable|string', // New field validation
                'main_ingredient' => 'sometimes|nullable|string', // New field validation
                'target_skin_type' => 'sometimes|nullable|string', // New field validation
            ]);

            $product_data = $request->all();

            // Check if 'images' is present in the request and is an array
            if ($request->has('images')) {
                if (is_array($request->input('images'))) {
                    // Convert images to JSON format without escape characters
                    $product_data['images'] = json_encode($request->input('images'));
                }
            }

            // Calculate discounted price
            $discount = $request->input('discount', 0);
            $product_data['discounted_price'] = $discount > 0 && $discount < 100
                ? round($product_data['price'] * (1 - $discount / 100), 2)
                : round($product_data['price'], 2);

            // Set status to 'available' if not specified
            if (!$request->has('status')) {
                $product_data['status'] = 'available';
            }

            // Update the product
            $product->update($product_data);

            // Prepare the response
            return response()->json([
                'product_id' => $product->product_id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'discount' => $product->discount,
                'discounted_price' => $product->discounted_price,
                'quantity' => $product->quantity,
                'brand_id' => $product->brand_id,
                'images' => json_decode($product->images), // Decode for response
                'status' => $product->status,
                'short_description' => $product->short_description,
                'volume' => $product->volume,
                'nature' => $product->nature,
                'rating' => $product->rating,
                'product_type' => $product->product_type, // New field
                'main_ingredient' => $product->main_ingredient, // New field
                'target_skin_type' => $product->target_skin_type, // New field
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
                'brand' => $product->brand
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Product not found'], 404);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred while updating the product.', 'error' => $e->getMessage()], 500);
        }
    }

    // Delete a product
    public function destroy($product_id)
    {
        try {
            $product = Product::findOrFail($product_id);
            $product->delete();

            return response()->json(['message' => 'Product deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Product not found'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred while deleting the product.', 'error' => $e->getMessage()], 500);
        }
    }

    // Change product status
    public function changeStatus(Request $request, $product_id)
    {
        try {
            $product = Product::findOrFail($product_id);

            $request->validate([
                'status' => 'required|string|in:' . Product::STATUS_AVAILABLE . ',' . Product::STATUS_OUT_OF_STOCK,
            ]);

            $product->status = $request->input('status');
            $product->save();

            return response()->json(['message' => 'Product status updated successfully.', 'product' => $product], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Product not found'], 404);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred while updating the product status.', 'error' => $e->getMessage()], 500);
        }
    }
    public function getReviewsByProduct($product_id)
    {
        try {
            // Check if the product exists
            $product = Product::find($product_id);
            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }

            // Get all reviews for the specified product
            $reviews = Review::with(['user'])->where('product_id', $product_id)->get();

            // Calculate the average rating
            $averageRating = $reviews->avg('rate');

            return response()->json([
                'product_name' => $product->name,
                'rating' => round($averageRating, 2),
                'reviews' => $reviews
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve reviews', 'error' => $e->getMessage()], 500);
        }
    }


}
