<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function uploadImage(Request $request)
    {
        // Validate the uploaded file
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Allow only specific image types
        ]);

        // Check if the file is valid and uploaded
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            // Store the image in the 'public/images' folder
            $path = $request->file('image')->store('images', 'public');

            // Generate full URL for the stored image
            $url = Storage::url($path);

            return response()->json([
                'message' => 'Image uploaded successfully!',
                'url' => url($url), // Return the full URL of the uploaded image
            ], 200);
        }

        return response()->json([
            'error' => 'Failed to upload image.',
        ], 400);
    }
}
