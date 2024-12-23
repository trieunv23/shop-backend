<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['login', 'refresh']]);
        $this->middleware('modify.response');
    }

    public function showProfile() 
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['error' => 'User not found or token is invalid'], 404);
        }

        $userProfile = User::with('profile')->find($user->id);

        $customizedProfile = [
            'id' => $userProfile->profile->id,
            'user_id' => $userProfile->id,
            'code' => $userProfile->code,
            'status' => $userProfile->status,
            'email' => $userProfile->email,
            'email_verified_at' => $userProfile->email_verified_at,
            'is_verified' => $userProfile->is_verified,
            'phone_number' => $userProfile->phone_number,
            'name' => $userProfile->profile->name,
            'day_of_birth' => $userProfile->profile->day_of_birth,
            'gender' => $userProfile->profile->gender,
        ];

        return response()->json([
            'profile' => $customizedProfile
        ], 201);
    }

    public function updateProfile(Request $request)
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['error' => 'User not found or token is invalid'], 404);
        }

        $rules = [
            'name' => 'string|max:255',
            'day_of_birth' => 'date|before:today',
            'gender' => 'string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->profile->update($request->only(['name', 'email', 'day_of_birth', 'gender']));

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully'
        ], 200);
    }
}
