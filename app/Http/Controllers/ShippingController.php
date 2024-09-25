<?php

namespace App\Http\Controllers;

use App\Models\Shipping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShippingController extends Controller
{
    // Fetch all shipping records
    public function index()
    {
        try {
            $shippings = Shipping::all();
            return response()->json($shippings, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching shippings', 'error' => $e->getMessage()], 500);
        }
    }

    // Fetch a single shipping record
    public function show($shipping_id)
    {
        try {
            $shipping = Shipping::find($shipping_id);

            if (!$shipping) {
                return response()->json(['message' => 'Shipping not found'], 404);
            }

            return response()->json($shipping, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching shipping', 'error' => $e->getMessage()], 500);
        }
    }

    // Create a new shipping record
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:shippings,name', // Ensure the name is unique
                'shipping_amount' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $shipping = Shipping::create($request->all());

            return response()->json($shipping, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error creating shipping', 'error' => $e->getMessage()], 500);
        }
    }

    // Update a shipping record
    public function update(Request $request, $shipping_id)
    {
        try {
            $shipping = Shipping::find($shipping_id);

            if (!$shipping) {
                return response()->json(['message' => 'Shipping not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255|unique:shippings,name,' . $shipping_id . ',shipping_id', // Ensure unique name but exclude current record
                'shipping_amount' => 'sometimes|required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $shipping->update($request->all());

            return response()->json($shipping, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error updating shipping', 'error' => $e->getMessage()], 500);
        }
    }

    // Delete a shipping record
    public function destroy($shipping_id)
    {
        try {
            // Find the shipping record by its ID
            $shipping = Shipping::findOrFail($shipping_id);

            // Delete the shipping record
            $shipping->delete();

            // Return a success message
            return response()->json(['message' => "Shipping {$shipping_id} deleted successfully"], 200);
        } catch (ModelNotFoundException $e) {
            // Handle case where the shipping record is not found
            return response()->json(['message' => 'Shipping not found'], 404);
        } catch (Exception $e) {
            // Handle any other unexpected exceptions
            return response()->json(['message' => 'An error occurred while deleting the shipping.', 'error' => $e->getMessage()], 500);
        }
    }

}
