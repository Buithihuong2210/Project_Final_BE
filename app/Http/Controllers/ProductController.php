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
                    'images' => json_decode($product->images),
                    'status' => $product->status,
                    'short_description' => $product->short_description,
                    'volume' => $product->volume,
                    'nature' => $product->nature,
                    'rating' => $product->rating, // Add the rating
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
            ]);

            $product_data = $request->all();
            $product_data['images'] = json_encode($request->input('images'));
            $product_data['status'] = 'available';

            $discount = $request->input('discount', 0);
            $product_data['discounted_price'] = $discount > 0 && $discount < 100
                ? round($product_data['price'] * (1 - $discount / 100), 2)
                : round($product_data['price'], 2);

            // Initialize rating as null or 0 when creating a product
            $product_data['rating'] = null;

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
                'images' => json_decode($product->images),
                'status' => $product->status,
                'short_description' => $product->short_description,
                'volume' => $product->volume,
                'nature' => $product->nature,
                'rating' => $product->rating, // Add the rating
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
                'images' => 'sometimes|required|array',
                'images.*' => 'nullable|url|ends_with:.jpg,.jpeg,.png,.gif,.svg',
                'short_description' => 'nullable|string',
                'volume' => 'nullable|numeric',
                'nature' => 'nullable|string|in:new,best seller,exclusive',
            ]);

            $product_data = $request->all();

            if ($request->has('images')) {
                $product_data['images'] = json_encode($request->input('images'));
            }

            $discount = $request->input('discount', 0);
            $product_data['discounted_price'] = $discount > 0 && $discount < 100
                ? round($product_data['price'] * (1 - $discount / 100), 2)
                : round($product_data['price'], 2);

            if (!$request->has('status')) {
                $product_data['status'] = 'available';
            }

            $product->update($product_data);

            return response()->json($product, 200);
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

    // Calculate and update the product rating based on user reviews
    public function updateRating($product_id)
    {
        try {
            // Attempt to find the product
            $product = Product::findOrFail($product_id);

            // Calculate the average rating from the reviews
            $averageRating = Review::where('product_id', $product_id)->avg('rate');

            // If there are no reviews yet, set the rating to null or 0
            $product->rating = $averageRating ? round($averageRating, 2) : null;

            // Save the updated rating in the product
            $product->save();

            return response()->json(['message' => 'Product rating updated successfully.', 'product' => $product], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // If product is not found, return a 404 error
            return response()->json(['message' => 'Product not found'], 404);
        } catch (\Exception $e) {
            // Handle any other errors
            return response()->json(['message' => 'An error occurred while updating the product rating.', 'error' => $e->getMessage()], 500);
        }
    }
}
