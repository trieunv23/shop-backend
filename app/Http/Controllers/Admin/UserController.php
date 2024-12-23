<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
        $this->middleware('modify.response');
    }

    public function index()
    {
        $users = User::with('profile')->get();

        return response()->json([
            'users' => $users
        ]);
    }

    public function show($id)
    {
        $users = User::with('profile')->where('id', '$id');

        return response()->json($users);
    }

    public function updateUser($id, Request $request)
    {
        $rules = [
            'status' => 'string',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()
            ], 422);
        }

        $user = User::findOrFail($id);

        $user->update($request->all());

        return response()->json([
            'message' => 'User updated successfull',
        ], 200);
    }
}
