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
        try {
            $request->validate([
                'name' => 'required|string|unique:hashtags,name',
                'description' => 'nullable|string',
            ]);

            // Create the hashtag
            $hashtag = Hashtag::create($request->all());

            // Prepare the response with reordered fields
            $response = [
                'hashtag_id' => $hashtag->hashtag_id,
                'name' => $hashtag->name,
                'description' => $hashtag->description,
                'created_at' => $hashtag->created_at->toISOString(),
                'updated_at' => $hashtag->updated_at->toISOString(),
            ];

            return response()->json($response, 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred'], 500);
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
            $request->validate([
                'name' => 'required|string|unique:hashtags,name,' . $id . ',hashtag_id',
                'description' => 'nullable|string',
            ]);

            $hashtag = Hashtag::findOrFail($id);
            $hashtag->update($request->all());

            return response()->json($hashtag);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Hashtag not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred'], 500);
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
}
