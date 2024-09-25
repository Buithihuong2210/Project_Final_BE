<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShoppingCart;
use App\Models\CartItem;
use App\Models\Product;
use Exception;

class CartController extends Controller
{
    /**
     * Add a product to the cart.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Validate the request data
            $request->validate([
                'product_id' => 'required|exists:products,product_id',
                'quantity' => 'required|integer|not_in:0', // Quantity can be positive or negative but not zero
            ]);

            // Fetch the product to check its available quantity (stock)
            $product = Product::findOrFail($request->product_id);

            // Find or create a shopping cart for the authenticated user
            $cart = ShoppingCart::firstOrCreate(['user_id' => auth()->id()]);

            // Check if the item already exists in the cart
            $cartItem = $cart->items()->where('product_id', $request->product_id)->first();

            if ($cartItem) {
                // Calculate the new quantity (existing + new request quantity)
                $newQuantity = $cartItem->quantity + $request->quantity;

                // Check if the new quantity exceeds the available stock
                if ($newQuantity > $product->quantity) {
                    return response()->json([
                        'message' => 'Requested quantity exceeds available stock.',
                        'available_stock' => $product->quantity
                    ], 400);
                }

                if ($newQuantity < 1) {
                    // If the new quantity is less than 1, remove the item from the cart
                    $cartItem->delete();
                    return response()->json(['message' => 'Item removed from cart because quantity was reduced to zero or below.'], 200);
                } else {
                    // Update the cart item with the new quantity and price
                    $cartItem->update([
                        'quantity' => $newQuantity,
                        'price' => $cartItem->product->price * $newQuantity // Update the price based on the new quantity
                    ]);
                }
            } else {
                // If the item doesn't exist in the cart, add it
                if ($request->quantity > $product->quantity) {
                    return response()->json([
                        'message' => 'Requested quantity exceeds available stock.',
                        'available_stock' => $product->quantity
                    ], 400);
                }

                if ($request->quantity > 0) {
                    // Add a new item to the cart
                    $cart->items()->create([
                        'product_id' => $request->product_id,
                        'quantity' => $request->quantity,
                        'price' => $product->price * $request->quantity, // Set price based on product price and quantity
                    ]);
                } else {
                    // Prevent adding a new item with a negative quantity
                    return response()->json(['message' => 'Cannot add an item with a negative quantity.'], 400);
                }
            }

            // Reload the cart with updated items and products
            return response()->json($cart->load('items.product'), 201);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error updating cart: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update the quantity of a cart item.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\CartItem $item
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, CartItem $item)
    {
        try {
            // Validate the request data
            $request->validate([
                'quantity' => 'required|integer|min:1',
            ]);

            // Fetch the related product to get its price
            $product = Product::findOrFail($item->product_id);

            // Calculate the new total price based on the updated quantity
            $totalPrice = $product->price * $request->quantity;

            // Update the cart item with the new quantity and price
            $item->update([
                'quantity' => $request->quantity,
                'price' => $totalPrice, // Update total price
            ]);

            // Fetch the updated cart item with the related product
            $updatedItem = CartItem::where('id', $item->id)
                ->with('product')
                ->first();

            // Return the updated cart item
            return response()->json($updatedItem, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error updating cart item: ' . $e->getMessage()], 500);
        }
    }
}
