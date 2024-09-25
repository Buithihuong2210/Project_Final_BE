<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function uploadImage(Request $request): \Illuminate\Http\JsonResponse
    {
        // Validate the uploaded image file
        $request->validate([
            'image' => 'required|image|mimes:jpg,png,jpeg,gif,svg|max:2048', // Specify allowed image formats
        ]);

        // Store the image file
        if ($request->file('image')) {
            $path = $request->file('image')->store('images', 'public'); // Save the image in the "images" folder under "storage/app/public"

            // Return the image's storage URL
            // Get the full URL of the uploaded file
            $fullUrl = url(Storage::url($path));

            // Return the image's full URL
            return response()->json(['url' => $fullUrl], 200);
        }

        return response()->json(['error' => 'No image uploaded'], 400);
    }
}
