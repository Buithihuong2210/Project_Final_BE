<?php

namespace App\Http\Controllers;

use App\Models\Hashtag;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;


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

// Retrieve all blogs related to a specific hashtag

    public function getByID($id)
    {
        try {
            // Find all blogs related to the hashtag through the hashtag_blog table
            $blogs = DB::table('hashtag_blog')
                ->join('blogs', 'hashtag_blog.blog_id', '=', 'blogs.blog_id')
                ->where('hashtag_blog.hashtag_id', $id)
                ->select('blogs.*')
                ->get();

            // Check if no blogs were found
            if ($blogs->isEmpty()) {
                return response()->json([
                    'message' => 'No blogs found for this hashtag.',
                ], 404);
            }

            // Return the list of blogs
            return response()->json($blogs, 200);

        } catch (\Exception $e) {
            // Handle the error and return a failed response
            return response()->json([
                'message' => 'Error retrieving blogs.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
