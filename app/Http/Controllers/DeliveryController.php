<?php
namespace App\Http\Controllers;

use App\Models\Delivery;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException; // Import QueryException to catch database-related errors
use Exception;

class DeliveryController extends Controller
{
    public function create(Request $request)
    {
        try {
            // Validate the incoming request data
            $request->validate([
                'order_id' => 'required|exists:orders,order_id', // Ensure 'order_id' exists in 'orders' table
                'delivery_address' => 'required|string|max:255', // Validate delivery address
                'delivery_date' => 'required|date', // Validate delivery date
            ]);

            // Create a new delivery record in the database
            $delivery = Delivery::create($request->all());
            return response()->json($delivery, 201); // Return the newly created delivery with a 201 status code
        } catch (QueryException $e) {
            // Handle database errors
            return response()->json([
                'error' => 'Database error: ' . $e->getMessage() // Return a detailed error message
            ], 500); // HTTP status code 500 for internal server error
        } catch (Exception $e) {
            // Handle unexpected errors
            return response()->json([
                'error' => 'An unexpected error occurred: ' . $e->getMessage() // Return a general error message
            ], 500); // HTTP status code 500 for internal server error
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            // Find the delivery record by ID or fail
            $delivery = Delivery::findOrFail($id);
            // Update the delivery status based on the request input
            $delivery->update(['status' => $request->input('status')]);

            return response()->json($delivery); // Return the updated delivery record
        } catch (QueryException $e) {
            // Handle database errors
            return response()->json([
                'error' => 'Database error: ' . $e->getMessage() // Return a detailed error message
            ], 500); // HTTP status code 500 for internal server error
        } catch (Exception $e) {
            // Handle unexpected errors
            return response()->json([
                'error' => 'An unexpected error occurred: ' . $e->getMessage() // Return a general error message
            ], 500); // HTTP status code 500 for internal server error
        }
    }
}
