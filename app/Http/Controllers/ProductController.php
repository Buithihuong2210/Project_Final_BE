<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Exception;

class ProductController extends Controller
{
    // List all products with their associated brands
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
                    'discount' => $product->discount, // Add discount
                    'discounted_price' => $product->discounted_price, // Add discounted price
                    'quantity' => $product->quantity,
                    'brand_id' => $product->brand_id,
                    'images' => json_decode($product->images), // Decode JSON back to array
                    'status' => $product->status,
                    'short_description' => $product->short_description,
                    'volume' => $product->volume,
                    'nature' => $product->nature,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                    'brand' => $product->brand
                ];
            });

            // Return the formatted products
            return response()->json($products, 200);
        } catch (Exception $e) {
            // Handle any unexpected exceptions
            return response()->json(['message' => 'An error occurred while fetching products.', 'error' => $e->getMessage()], 500);
        }
    }

    // Store a new product
    public function store(Request $request)
    {
        try {
            // Validate the request data
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric',
                'discount' => 'nullable|numeric|min:0|max:100', // Validate discount between 0 and 100
                'quantity' => 'nullable|numeric|min:0|max:500',
                'brand_id' => 'required|exists:brands,brand_id',
                'images' => 'required|array',
                'images.*' => 'nullable|url|ends_with:.jpg,.jpeg,.png,.gif,.svg',
                'short_description' => 'nullable|string',
                'volume' => 'nullable|numeric',
                'nature' => 'nullable|string|in:new,best seller,exclusive',
            ]);

            // Prepare product data for storage
            $product_data = $request->all();
            $product_data['images'] = json_encode($request->input('images'));

            // Set status to 'available' by default
            $product_data['status'] = 'available';

            // Calculate the discounted price if discount is provided
            $discount = $request->input('discount', 0);
            if ($discount > 0 && $discount < 100) {
                $product_data['discounted_price'] = round($product_data['price'] * (1 - $discount / 100), 2);
            } else {
                $product_data['discounted_price'] = round($product_data['price'], 2);
            }

            // Create a new product
            $product = Product::create($product_data);

            // Return the newly created product
            return response()->json([
                'product_id' => $product->product_id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'discount' => $product->discount,
                'discounted_price' => number_format($product->discounted_price, 2, '.', ''),
                'quantity' => $product->quantity,
                'brand_id' => $product->brand_id,
                'images' => json_decode($product->images),
                'status' => $product->status,
                'short_description' => $product->short_description,
                'volume' => $product->volume,
                'nature' => $product->nature,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at
            ], 201);
        } catch (ValidationException $e) {
            // Handle validation errors
            return response()->json(['errors' => $e->errors()], 422);
        } catch (Exception $e) {
            // Handle any other unexpected exceptions
            return response()->json(['message' => 'An error occurred while creating the product.', 'error' => $e->getMessage()], 500);
        }
    }


    // Show a specific product by ID
    public function show($product_id)
    {
        try {
            // Retrieve the product by its ID with the associated brand
            $product = Product::with('brand')->findOrFail($product_id);

            // Return the product
            return response()->json([
                'product_id' => $product->product_id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'discount' => $product->discount, // Return discount
                'discounted_price' => $product->discounted_price, // Return discounted price
                'quantity' => $product->quantity,
                'brand_id' => $product->brand_id,
                'images' => json_decode($product->images),
                'status' => $product->status,
                'short_description' => $product->short_description,
                'volume' => $product->volume,
                'nature' => $product->nature,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
                'brand' => $product->brand
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Handle case where the product is not found
            return response()->json(['message' => 'Product not found'], 404);
        } catch (Exception $e) {
            // Handle any other unexpected exceptions
            return response()->json(['message' => 'An error occurred while fetching the product.', 'error' => $e->getMessage()], 500);
        }
    }

    // Update a specific product by ID
    public function update(Request $request, $product_id)
    {
        try {
            // Find the product by its ID
            $product = Product::findOrFail($product_id);

            // Validate the request data
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

            // Prepare product data for updating
            $product_data = $request->all();

            // Check if images were provided and encode them
            if ($request->has('images')) {
                $product_data['images'] = json_encode($request->input('images'));
            }

            // Calculate the discounted price if discount is provided
            $discount = $request->input('discount', 0);
            if ($discount > 0 && $discount < 100) {
                $product_data['discounted_price'] = round($product_data['price'] * (1 - $discount / 100), 2);
            } else {
                $product_data['discounted_price'] = round($product_data['price'], 2);
            }

            // Set status to 'available' if not provided
            if (!$request->has('status')) {
                $product_data['status'] = 'available';
            }

            // Update the product with the validated data
            $product->update($product_data);

            // Return the updated product with product_id at the beginning
            return response()->json([
                'product_id' => $product->product_id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'discount' => $product->discount,
                'discounted_price' => number_format($product->discounted_price, 2, '.', ''),
                'quantity' => $product->quantity,
                'brand_id' => $product->brand_id,
                'images' => json_decode($product->images),
                'status' => $product->status,
                'short_description' => $product->short_description,
                'volume' => $product->volume,
                'nature' => $product->nature,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Product not found'], 404);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred while updating the product.', 'error' => $e->getMessage()], 500);
        }
    }


    // Delete a specific product by ID
    public function destroy($product_id)
    {
        try {
            // Find the product by its ID
            $product = Product::findOrFail($product_id);

            // Delete the product
            $product->delete();

            // Return success message
            return response()->json(['message' => 'Product deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            // Handle case where the product is not found
            return response()->json(['message' => 'Product not found'], 404);
        } catch (Exception $e) {
            // Handle any other unexpected exceptions
            return response()->json(['message' => 'An error occurred while deleting the product.', 'error' => $e->getMessage()], 500);
        }
    }
    public function changeStatus(Request $request, $product_id)
    {
        try {
            // Find the product by its ID
            $product = Product::findOrFail($product_id);

            // Validate the request data
            $request->validate([
                'status' => 'required|string|in:' . Product::STATUS_AVAILABLE . ',' . Product::STATUS_OUT_OF_STOCK,
            ]);

            // Update the product status
            $product->status = $request->input('status');
            $product->save();

            // Return the updated product
            return response()->json([
                'message' => 'Product status updated successfully.',
                'product' => $product
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Product not found'], 404);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred while updating the product status.', 'error' => $e->getMessage()], 500);
        }
    }

}
