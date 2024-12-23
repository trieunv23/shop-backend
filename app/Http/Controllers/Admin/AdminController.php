<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function create(Request $request) {
        $rules = [
            'username' => 'required|string|unique:admins,username',
            'password' => 'required|string|min:8',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors(),
            ], 422);
        }

        $admin = Admin::create([
            'username' => $request['username'],
            'password' => bcrypt($request['password']),
        ]);

        $token = auth('admin')->login($admin);

        return response()->json([
            'ms' => 'successful',
            'access_token' => $token 
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');

        if (!$token = Auth::guard('admin')->attempt($credentials)) {
            return response()->json([
                'error' => 'Invalid credentials',
            ], 401);
        }

        return response()->json([
            'token' => $token,
            'admin' => Auth::guard('admin')->user(),
        ]);
    }
}
