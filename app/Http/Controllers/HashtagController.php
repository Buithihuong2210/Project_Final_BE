<?php

namespace App\Http\Controllers;

use App\Models\Hashtag;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class HashtagController extends Controller
{
    public function index()
    {
        return Hashtag::all();
    }

    public function store(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            // Create a new hashtag
            $hashtag = Hashtag::create([
                'name' => $request->input('name'),
            ]);

            // Return the response
            return response()->json([
                'hashtag_id' => $hashtag->id,
                'name' => $hashtag->name,
                'created_at' => $hashtag->created_at,
                'updated_at' => $hashtag->updated_at,
            ], 201);

        } catch (\Exception $e) {
            // Log the actual error message
            \Log::error('Error creating hashtag: ' . $e->getMessage());
            return response()->json(['error' => 'Could not create hashtag. Details: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $hashtag = Hashtag::findOrFail($id);
            return response()->json($hashtag);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Hashtag not found'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // Update validation to use the correct primary key (id)
            $request->validate([
                'name' => 'required|string|unique:hashtags,name,' . $id . ',id', // Change 'hashtag_id' to 'id'
            ]);

            // Find the hashtag by id
            $hashtag = Hashtag::findOrFail($id);

            // Update the hashtag
            $hashtag->update($request->all());

            // Return the updated hashtag
            return response()->json($hashtag);

        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Hashtag not found'], 404);
        } catch (\Exception $e) {
            // Log the exact error message for easier debugging
            return response()->json(['message' => 'An error occurred', 'details' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            // Find the hashtag or fail
            $hashtag = Hashtag::findOrFail($id);

            // Delete the hashtag
            $hashtag->delete();

            // Prepare the response with a success message
            $response = [
                'message' => 'Hashtag deleted successfully',
                'hashtag_id' => $id
            ];

            // Return the response with a 200 OK status code
            return response()->json($response, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Hashtag not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred'], 500);
        }
    }

    public function getByID($id)
    {
        try {
            // Attempt to find the hashtag by its ID
            $hashtag = Hashtag::findOrFail($id);

            // Prepare the response with the hashtag data
            $response = [
                'hashtag_id' => $hashtag->id, // Make sure to use 'id' instead of 'hashtag_id'
                'name' => $hashtag->name,
                'created_at' => $hashtag->created_at->toISOString(),
                'updated_at' => $hashtag->updated_at->toISOString(),
            ];

            // Return the response with the found hashtag data
            return response()->json($response, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Hashtag not found'], 404);
        } catch (\Exception $e) {
            // Log the exact error message for easier debugging
            return response()->json(['message' => 'An error occurred', 'details' => $e->getMessage()], 500);
        }
    }

}
