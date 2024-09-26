<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

class VoucherController extends Controller
{
    // Fetch all vouchers
    public function index()
    {
        try {
            $vouchers = Voucher::all();
            return response()->json($vouchers, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Unable to fetch vouchers'], 500);
        }
    }

    // Create a new voucher
    public function store(Request $request)
    {
        try {
            $request->validate([
                'code' => 'required|unique:vouchers,code',
                'discount_amount' => 'required|numeric',
                'status' => 'required|in:active,inactive',
                'start_date' => 'required|date',
                'expiry_date' => 'required|date|after_or_equal:start_date',
            ]);

            $voucher = Voucher::create($request->all());

            return response()->json($voucher, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to create voucher'], 500);
        }
    }
    //show detail
    public function show($voucher_id)
    {
        try {
            $voucher = Voucher::findOrFail($voucher_id); // Find voucher by id or fail
            return response()->json($voucher, 200); // Return voucher details as JSON
        } catch (Exception $e) {
            return response()->json(['error' => 'Voucher not found'], 404); // Return error if not found
        }
    }

    // Update a voucher
    public function update(Request $request, $voucher_id)
    {
        try {
            $voucher = Voucher::findOrFail($voucher_id);

            $request->validate([
                'code' => 'required|unique:vouchers,code,' . $voucher_id . ',voucher_id',
                'discount_amount' => 'required|numeric',
                'status' => 'required|in:active,inactive',
                'start_date' => 'required|date',
                'expiry_date' => 'required|date|after_or_equal:start_date',
            ]);

            $voucher->update($request->all());

            return response()->json($voucher, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to update voucher'], 500);
        }
    }

    // Change voucher status
    public function changeStatus(Request $request, $voucher_id)
    {
        try {
            $voucher = Voucher::findOrFail($voucher_id);

            // Validate that the status is either 'active' or 'inactive'
            $request->validate([
                'status' => 'required|in:active,inactive',
            ]);

            // Update the status
            $voucher->status = $request->status;
            $voucher->save();

            return response()->json(['message' => 'Voucher status updated successfully', 'voucher' => $voucher], 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to change voucher status'], 500);
        }
    }

    // Delete a voucher
    public function destroy($voucher_id)
    {
        try {
            $voucher = Voucher::findOrFail($voucher_id);
            $voucher->delete();

            return response()->json(['message' => 'Voucher deleted successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to delete voucher'], 500);
        }
    }
}
