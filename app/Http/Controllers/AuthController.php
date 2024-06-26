<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\VerificationMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use App\Mail\ForgotPasswordMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Notifications\VerifyEmailNotification;

class AuthController extends Controller
{
    public function register()
    {
        //Validate Request
        $validator = Validator::make(request()->all(), [
            'name' => 'required|string',
            'password' => 'required|string',
            'email' => 'required|email|unique:users',
            'address' => 'required|string',
            'number' => 'required|unique:users'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->messages()->all());
        }

        //Create User
        $user = User::create([
            'name' => request('name'),
            'email' => request('email'),
            'password' => Hash::make(request('password')),
            'address' => request('address'),
            'number' => request('number'),
            'role_id' => 0
        ]);

        if ($user) {
            // Assuming $user is the user model instance
            $verificationUrl = URL::temporarySignedRoute(
                "verifyMail", // Name of the verification route
                now()->addMinutes(10), // Expiry time for the URL (e.g., 60 minutes)
                ['id' => $user->id] // Route parameters
            );
            Mail::to($user->email)->send(new VerificationMail($verificationUrl));
            return redirect()->route('Verification Registrasion')->with('user_id', $user->id);
        } else {
            return redirect()->back()->withErrors(["User not Found"]);
        }
    }

    public function mailVerification($id)
    {
        $user = User::find($id);
        if (!$user) {
            Log::error("User with ID $id not found.");
            // Handle the case where the user is not found
            // For example, return a response indicating that the user doesn't exist
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->email_verified_at = now();
        $user->save();
        return redirect()->route('login');
    }

    public function resendVerification($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response('Failed', 200);
        }

        // Assuming $user is the user model instance
        $verificationUrl = URL::temporarySignedRoute(
            "verifyMail", // Name of the verification route
            now()->addMinutes(10), // Expiry time for the URL (e.g., 60 minutes)
            ['id' => $user->id] // Route parameters
        );
        Mail::to($user->email)->send(new VerificationMail($verificationUrl));
        return response('Success', 200);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        //Validate Request
        $validator = Validator::make(request()->all(), [
            'password' => 'required',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->messages()->all());
        }

        $credentials = request(['email', 'password']);

        if (!$token = auth()->attempt($credentials)) {

            // Return the clothes with total quantities
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json(["User not Found"]);
            }
            return redirect()->back()->withErrors(["User not Found"]);
        }

        // //Check if user has already verified
        if (auth()->user()->email_verified_at == null) {
            auth()->logout();
            return redirect()->back()->withErrors(["User not Found"]);
        }

        // Return the clothes with total quantities
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json(["User Found"]);
        }

        return redirect()->route('dashboard');
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return redirect()->route('home');
    }

    public function changePassword()
    {
        $validator = Validator::make(request()->all(), [
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->messages()->all());
        }

        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            return redirect()->back()->withErrors(['Error Authentication']);
        }

        // Hash the new password
        $newPassword = Hash::make(request()->input('password'));

        // Update the user's password
        $user->update([
            'password' => $newPassword
        ]);
        return redirect()->route('Profile')->with('success', 'User updated successfully');
    }

    public function forgotPassword()
    {
        $validator = Validator::make(request()->all(), [
            'email' => 'required|email|exists:users,email'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->messages()->all());
        }

        //Generate Random Password
        $newPassword = Str::random(8);

        //Change user password
        $user = User::where('email', request('email'))->first();

        if (!$user) {
            return redirect()->back()->withErrors(["User Not Found"]);
        }

        // Hash the new password
        $newHashed = Hash::make($newPassword);

        // Update the user's password
        $user->update([
            'password' => $newHashed
        ]);

        // Assuming $user is the user model instance
        $url = URL::temporarySignedRoute(
            "login", // Name of the verification route
            now()->addMinutes(10), // Expiry time for the URL (e.g., 60 minutes)
        );
        Mail::to($user->email)->send(new ForgotPasswordMail($url, $newPassword));

        return redirect()->back();
    }
}
