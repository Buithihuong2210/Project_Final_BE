<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        // Retrieve all users
        $users = User::all();

        // Return the users in JSON format with a 200 status
        return response()->json([
            'users' => $users
        ], 200);
    }

    public function update(Request $request, $id)
    {
        try {
            // Validate the request
            $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'string|max:255',
                'dob' => 'date',
                'gender' => 'string|in:male,female,other|max:255',
                'image' => 'string|url|max:255',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }

        // Find the user by ID
        $user = User::find($id);

        if (is_null($user)) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Update the user's details
        $user->name = $request->input('name');
        $user->phone = $request->input('phone');
        $user->dob = $request->input('dob');
        $user->gender = $request->input('gender');
        $user->image = $request->input('image');

        $user->save();

        // Return a response
        return response()->json([
            'message' => "User {$id} updated successfully",
            'user' => $user
        ], 200);
    }

    public function destroy($id)
    {
        try{
            // Find the user by ID
            $user = User::findOrFail($id);
            // Delete the user
            $user->delete();
        }
        catch (\Exception $e) {
            return response()->json([
                'message' =>"Can`t not found user with ID is {$id} to delete"
            ],400);
        }

        // Optionally, return a response or redirect
        return response()->json(['message' => 'User deleted successfully']);
    }
    public function getUserById($id)
    {
        // Find the user by ID
        $user = User::find($id);

        // Check if the user exists
        if (is_null($user)) {
            return response()->json([
                'message' => "User with ID {$id} not found"
            ], 404);
        }

        // Return the user information
        return response()->json([
            'user' => $user
        ], 200);
    }

}
