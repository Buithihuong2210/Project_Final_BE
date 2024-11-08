<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SocialAuthController extends Controller
{
    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')->redirect();
    }

    // Handle the callback from Facebook
    public function handleFacebookCallback()
    {
        try {
            // Get the Facebook user details
            $facebookUser = Socialite::driver('facebook')->stateless()->user();

            // Find or create a user in the database
            $user = User::firstOrCreate(
                ['email' => $facebookUser->getEmail()],
                [
                    'name' => $facebookUser->getName(),
                    'facebook_id' => $facebookUser->getId(),
                    'image' => $facebookUser->getAvatar(),
                    'password' => Hash::make('12345678')
                    // Add other user fields as necessary
                ]
            );

            // Log the user in
            Auth::login($user);

            // Create an auth token
            $tk = $user->createToken('authToken')->plainTextToken;

            // Redirect to the frontend with the token in the URL
            return redirect()->to('http://localhost:3000/home?tk=' . $tk);

        } catch (\Exception $e) {
            dd($e);
            Log::error('Error occurred: ' . $e->getMessage());
            // Handle the error and redirect back with a failure message
            return redirect('http://localhost:3000/login')->withErrors(['error' => 'Failed to login using Facebook.']);
        }
    }
}
