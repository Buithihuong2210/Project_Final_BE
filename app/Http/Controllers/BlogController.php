<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Hashtag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BlogController extends Controller
{
    // Retrieve all blog entries
    public function showAll()
    {
        try {
            $blogs = Blog::all();

            return response()->json($blogs, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while retrieving blogs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Create a new blog
    public function store(Request $request)
    {
        try {
            $isAdmin = auth()->user()->admin;

            $rules = [
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'thumbnail' => 'url',
                'hashtags' => 'nullable|array',
                'hashtags.*' => 'string|max:255',
            ];

            if ($isAdmin) {
                $rules['status'] = 'required|in:draft,published';
            }

            $validatedData = Validator::make($request->all(), $rules)->validate();
            $hashtags = $validatedData['hashtags'] ?? [];

            // Create a new blog
            $blog = Blog::create([
                'title' => $validatedData['title'],
                'user_id' => auth()->id(),
                'content' => $validatedData['content'],
                'status' => $isAdmin ? $validatedData['status'] : 'draft',
                'thumbnail' => $validatedData['thumbnail'] ?? '',
                'like' => 0,  // Default value for like
            ]);

            // Handle hashtags
            $hashtagIds = [];
            foreach ($hashtags as $hashtagName) {
                $hashtag = Hashtag::firstOrCreate(['name' => $hashtagName]);
                $hashtag->increment('usage_count');
                $hashtagIds[] = $hashtag->id;
            }

            $blog->hashtags()->attach($hashtagIds);

            return response()->json($blog, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while creating the blog',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Show a specific blog by ID
    public function show($blog_id)
    {
        try {
            $blog = Blog::findOrFail($blog_id);
            return response()->json($blog);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Blog not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    // Update a blog as a regular user (only if the blog is in draft status)
    public function updateUser(Request $request, $blog_id)
    {
        try {
            $blog = Blog::findOrFail($blog_id);

            if (auth()->user()->id !== $blog->user_id || $blog->status !== 'draft') {
                return response()->json([
                    'message' => 'Unauthorized or blog is not in draft status',
                ], 403);
            }

            $validatedData = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'thumbnail' => 'url',
            ])->validate();

            // Update the blog
            $blog->update([
                'title' => $validatedData['title'],
                'content' => $validatedData['content'],
                'thumbnail' => $validatedData['thumbnail'],
            ]);

            return response()->json($blog);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the blog',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Update a blog as an admin
    public function updateAdmin(Request $request, $blog_id)
    {
        try {
            if (!auth()->user()->admin) {
                return response()->json([
                    'message' => 'Unauthorized. Only admins can perform this action.',
                ], 403);
            }

            $blog = Blog::findOrFail($blog_id);

            $validatedData = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'status' => 'required|in:draft,published',
                'thumbnail' => 'url',
            ])->validate();

            // Update the blog
            $blog->update([
                'title' => $validatedData['title'],
                'content' => $validatedData['content'],
                'status' => $validatedData['status'],
                'thumbnail' => $validatedData['thumbnail'] ?? '',
            ]);

            return response()->json($blog);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the blog',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Change the blog status
    public function changeStatus(Request $request, $blog_id)
    {
        try {
            $blog = Blog::findOrFail($blog_id);

            $validatedData = $request->validate([
                'status' => 'required|in:draft,published',
            ]);

            $blog->update([
                'status' => $validatedData['status'],
            ]);

            return response()->json($blog);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while changing the blog status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Increment the like count for a blog
    public function updateLike($blog_id)
    {
        try {
            $blog = Blog::findOrFail($blog_id);

            // Check if the blog status is 'published'
            if ($blog->status !== 'published') {
                return response()->json([
                    'message' => 'Likes can only be updated for published blogs',
                ], 403);
            }

            // Increment the like count
            $blog->increment('like');

            return response()->json([
                'message' => 'Blog like updated successfully',
                'blog' => $blog,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the like count',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Delete a blog
    public function destroy($blog_id)
    {
        try {
            $blog = Blog::findOrFail($blog_id);
            $blog->delete();

            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Blog not found or could not be deleted',
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
