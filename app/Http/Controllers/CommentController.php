<?php
namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{

    // Create a new comment for a specific blog
    public function store(Request $request, $blog_id)
    {
        try {
            // Validate the content, blog_id comes from the route
            $validatedData = $request->validate([
                'content' => 'required|string',
            ]);

            // Create the comment, using the blog_id from the route
            $comment = Comment::create([
                'blog_id' => $blog_id, // Use the blog_id from the route
                'user_id' => auth()->id(), // Sets the user ID to the current authenticated user
                'content' => $validatedData['content'],
            ]);

            // Return the newly created comment with a 201 status code indicating success
            return response()->json($comment, 201);

        } catch (\Exception $e) {
            // Handle any errors gracefully and return a meaningful message
            return response()->json([
                'message' => 'An error occurred while creating the comment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Fetch all comments for a specific blog
    public function index($blog_id)
    {
        try {
            // Fetch comments related to the given blog and eager load the user relationship
            $comments = Comment::where('blog_id', $blog_id)->with('user')->get();
            return response()->json($comments);

        } catch (\Exception $e) {
            // Return an error message in case the fetching process fails
            return response()->json([
                'message' => 'An error occurred while fetching comments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Update a specific comment by the comment ID
    public function update(Request $request, $comment_id)
    {
        try {
            // Find the comment by its ID or fail if it does not exist
            $comment = Comment::findOrFail($comment_id);

            // Ensure the authenticated user is the owner of the comment
            if ($comment->user_id !== auth()->id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Validate the new content to be updated
            $validatedData = $request->validate([
                'content' => 'required|string',
            ]);

            // Update the comment content
            $comment->update([
                'content' => $validatedData['content'],
            ]);

            // Return the updated comment
            return response()->json($comment);

        } catch (\Exception $e) {
            // In case of failure, return an error message
            return response()->json([
                'message' => 'An error occurred while updating the comment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Delete a specific comment by its ID
    public function destroy($comment_id)
    {
        try {
            // Find the comment by its ID
            $comment = Comment::findOrFail($comment_id);

            // Ensure that the user attempting to delete the comment is the owner
            if ($comment->user_id !== auth()->id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Delete the comment
            $comment->delete();

            // Return a successful response with no content
            return response()->json(null, 204);

        } catch (\Exception $e) {
            // Handle any exceptions and return an appropriate error response
            return response()->json([
                'message' => 'An error occurred while deleting the comment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
