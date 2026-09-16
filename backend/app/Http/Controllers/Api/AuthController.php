<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;

use Illuminate\Http\Request;

class AuthController extends Controller
{
     public function register(Request $request)
    {
       
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role'=> 'required|in:job_seeker,company',
        ]);



        
        $user = User::create($validated);

        
        return response()->json($user, 201);
    }

     public function login(Request $request){

     $credentials = $request->validate([

     'email'=> 'required|email',
     'password'=> 'required|string',
     ]);


     if (!Auth::guard('web')->attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid email or password provided.',
            ], 401);
        }

        // Replace the old session ID after successful login.
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Logged in successfully.',
            'user' => $request->user(),
        ]);
    }

    public function logout(Request $request)
{
   
    Auth::guard('web')->logout();

    
    $request->session()->invalidate();

    
    $request->session()->regenerateToken();

    return response()->json([
        'message' => 'Logged out successfully.',
    ]);
}

}
