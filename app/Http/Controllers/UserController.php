<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Mail\VerifyEmail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class UserController extends Controller 
{
    public function checkAuth(Request $request)
    {
        return response()->json([
            'authenticated' => Auth::check(),
        ]);
    }

    public function login(Request $request) 
    {
        try {
            $request->validate([
                'username' => 'required|string',
                'password' => 'required|string'
            ]);
    
            $user = User::with('profile')->where('username', $request->input('username'))->first();
    
            if ($user && Hash::check($request->input('password'), $user->password)) {
                Auth::login($user, true);

                return response()->json([
                    'user' => $user
                ], 201);
            } else {
                return response()->json([
                    'message' => 'username or password incorrect!', 'data' => $request->all()
                ], 401);
            }
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 401);
        }
    }

    public function checkUsername(Request $request)
    {
        $request->validate([ 'username' => 'required|string|max:255', ]); 
        $username = $request->input('username'); 
        $userExists = User::where('username', $username)->exists(); 

        return response()->json(['isAvailable' => !$userExists]);
    }

    public function register(Request $request)
    {
        try {
            $validated = $request->validate([ 
                'username' => 'required|string|unique:users,username,',
                'password' => 'required|string|min:4',
                'name' => 'required|string',
                'email' => 'required|email',
                'phone_number' => 'required|digits:10'
            ]);

            $verificationCode = rand(100000, 999999);
            $expiresAt = now()->addMinutes(30);
    
            $user = User::create([
                'username' => $validated['username'],
                'password' => bcrypt($validated['password']),
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'],
                'verification_code' => $verificationCode, 
                'verification_code_expires_at' => $expiresAt,
                'is_verified' => false,
            ]);

            $user->profile()->create([
                'name' => $validated['name'],
            ]);

            $user->sendEmailVerificationNotification();

            Mail::to($validated['email'])->send(new VerifyEmail($user));
    
            return response()->json(['message' => 'success', 'user' => $user]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function verifyEmail(Request $request)
    {
        $validated = $request->validate([ 
            'user_id' => 'required|integer',
            'code' => 'required|string'
        ]);

        $user = User::where('verification_code', $validated['code'])->where('id', $validated['user_id'])->first();

        if ($user && $user->verification_code_expires_at && now()->lessThanOrEqualTo($user->verification_code_expires_at)) {
            $user->is_verified = true;
            $user->verification_code = '';
            $user->email_verified_at = Carbon::now()->setTimezone('Asia/Ho_Chi_Minh');
            $user->verification_code_expires_at = null;
            $user->save();

            return response()->json(['message' => 'Email verified successfully']);
        } else {
            return response()->json(['error' => 'Invalid or expired verification code'], 400);
        }
    }

    public function logout(Request $request) {
        Auth::logout();
    }

    public function getUser (Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();   
            
            $user = User::with(['profile' => function ($query) {
                $query->select('id', 'user_id', 'name', 'day_of_birth', 'gender'); // Trường của bảng profile
            }])->select('id', 'user_code', 'email', 'phone_number', 'status') // Trường của bảng user 
              ->find(Auth::id()); 

            return response()->json([
                'user' => $user
            ], 201);
        } else {
            return response()->json(['message' => 'User not authenticated'], 401);
        }
    }

    public function changeProfileInfor (Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'day_of_birth' => 'required|date|before:today',
                'gender' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user->profile->update([
                'name' => $request->name,
                'email' => $request->email,
                'day_of_birth' => $request->day_of_birth,
            ]);

            return response()->json(['message' => 'Profile updated successfully'], 200);
        }
    }

    // Admin 
    public function getAllUser(Request $request)  
    {
        // Authentication Admin

        try {
            $users = User::with('profile')->get();
    
            $usersArray = StringHelper::convertListKeysToCamelCase($users->toArray());
    
            return response()->json($usersArray);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong', 'message' => $e->getMessage()], 500);
        }
    }
}
