<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        // Retrieve all users
        $users = User::all();

        // Return the users directly in JSON format with a 200 status
        return response()->json($users, 200);
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

        // Return a response without wrapping in 'user' object
        return response()->json([
                'message' => "User {$id} updated successfully",
            ] + $user->toArray(), 200); // Merging user data directly into response
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

        // Return the user information without wrapping it in a 'user' object
        return response()->json($user, 200);
    }
    public function changePassword(Request $request)
    {
        try {
            // Validate input
            $request->validate([
                'current_password' => 'required',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            // Get the authenticated user
            $user = Auth::user();

            // Check if current password is correct
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['message' => 'Current password is incorrect'], 400);
            }

            // Update the user's password
            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json(['message' => 'Password changed successfully'], 200);

        } catch (\Throwable $th) {
            // Catch any errors and return a response
            return response()->json([
                'message' => 'An error occurred while changing the password.',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
