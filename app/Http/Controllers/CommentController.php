<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    // Create a new comment for a specific blog
    public function store(Request $request, $blog_id)
    {
        try {
            // Validate input data
            $validatedData = $request->validate([
                'content' => 'required|string',
                'parent_id' => 'nullable|exists:comments,comment_id', // Check if parent_id exists in the comments table
            ]);

            // Create a new comment
            $comment = Comment::create([
                'blog_id' => $blog_id,
                'user_id' => auth()->id(),
                'content' => $validatedData['content'],
                'parent_id' => $validatedData['parent_id'] ?? null, // Assign parent_id if provided
            ]);

            // Load the associated user
            $comment->load('user:id,name,image,dob,role,phone,gender,email'); // Load only id and name from user for efficiency

            // Return the newly created comment with a 201 status code
            return response()->json($comment, 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Handle other exceptions
            return response()->json([
                'message' => 'An error occurred while creating the comment',
                'error' => $e->getMessage(),
            ], 500);
        }
        
    }

    // Get all comments for a specific blog
    public function index($blog_id)
    {
        try {
            // Get comments related to the blog and eager load relationships with user and replies
            $comments = Comment::where('blog_id', $blog_id)
                ->with(['user:id,name,image,dob,role,phone,gender,email', 'replies.user:id,name,image,dob,role,phone,gender,email']) // Removed 'user:' prefix here
                ->whereNull('parent_id') // Chỉ lấy các comment cha
                ->get();

            // Check if comments are empty
            if ($comments->isEmpty()) {
                return response()->json([], 200); // Return an empty array
            }

            return response()->json($comments, 200);

        } catch (\Exception $e) {
            // Handle errors
            return response()->json([
                'message' => 'An error occurred while retrieving comments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Update a specific comment
    public function update(Request $request, $blog_id, $comment_id)
    {
        try {
            // Validate input data
            $validatedData = $request->validate([
                'content' => 'required|string', // Ensure content is required and is a string
            ]);

            // Find the comment by ID
            $comment = Comment::findOrFail($comment_id);

            // Optional: Check if the authenticated user is the owner of the comment
            if ($comment->user_id !== auth()->id()) {
                return response()->json([
                    'message' => 'You are not authorized to update this comment.',
                ], 403);
            }

            // Update the comment content
            $comment->update([
                'content' => $validatedData['content'],
            ]);

            // Load user data after update
            $comment->load('user');

            // Return the updated comment with a 200 status code
            return response()->json($comment, 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle the case where the comment is not found
            return response()->json([
                'message' => 'Comment not found.',
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            // Handle any other exceptions
            return response()->json([
                'message' => 'An error occurred while updating the comment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Delete a specific comment
    public function destroy($blog_id, $comment_id)
    {
        try {
            // Find the comment by ID
            $comment = Comment::findOrFail($comment_id);

            // Optional: Check if the authenticated user is the owner of the comment
            if ($comment->user_id !== auth()->id()) {
                return response()->json([
                    'message' => 'You are not authorized to delete this comment.',
                ], 403);
            }

            // Delete the comment
            $comment->delete();

            // Return a success response
            return response()->json([
                'message' => "Comment {$comment_id} deleted successfully."
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle the case where the comment is not found
            return response()->json([
                'message' => 'Comment not found.',
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            // Handle any other exceptions
            return response()->json([
                'message' => 'An error occurred while deleting the comment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}