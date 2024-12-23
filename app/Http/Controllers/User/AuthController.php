<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmail;
use App\Models\EmailVerification;
use App\Models\UserEmail;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['login', 'refresh', 'register', 'checkUsername']]);
        $this->middleware('modify.response');
    }

    public function checkUsername(Request $request)
    {
        $request->validate(['username' => 'required|string|max:255',]); 
        $username = $request->input('username'); 
        $userExists = User::where('username', $username)->exists(); 

        return response()->json(['is_available' => !$userExists]);
    }

    public function register(Request $request)
    {
        $rules = [
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:4',
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|digits:10'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $user = User::create([
                'username' => $request['username'],
                'password' => bcrypt($request['password']),
                'phone_number' => $request['phone_number'],
            ]);
    
            $user->profile()->create([
                'name' => $request['name'],
            ]);

            $emailVerification = $user->emailVerifications()->create([
                'email' => $request['email'],
            ]);

            Mail::to($request['email'])->send(new VerifyEmail([
                'username' => $user->username,
                'name' => $user->profile->name,
                'code' => $emailVerification->code,
            ]));

            $emailVerificationToken = $this->createToken($emailVerification->id, 15 * 60);
    
            $token = auth('api')->login($user);

            DB::commit();

            return response()->json([
                'refresh_token' => $this->createToken($user->id, config('jwt.refresh_ttl')),
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
                'email_verification_token' => $emailVerificationToken
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([ 
                'error' => 'Registration failed', 
                'message' => $e->getMessage() 
            ], 500);
        }
    }

    public function sendEmailVerification(Request $request)
    {
        $rules = [
            'email' => 'required|email|unique:users,email',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()
            ], 422);
        }

        $user = User::find(Auth::id());

        DB::beginTransaction();

        try {
            $newEmailVerification = $user->emailVerifications()->create([
                'email' => $request['email']
            ]);

            $emailVerificationToken = $this->createToken($newEmailVerification->id, 15 * 60);

            Mail::to($request['email'])->send(new VerifyEmail([
                'username' => $user->username,
                'name' => $user->profile->name,
                'code' => $newEmailVerification->code,
            ]));

            DB::commit();

            return response()->json([
                'message' => 'Verification code sent successfully',
                'email_verification_token' => $emailVerificationToken
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([ 
                'error' => 'Registration failed', 
                'message' => $e->getMessage() 
            ], 500);
        }        
    }

    public function verifyEmail(Request $request)
    {
        $user = User::find(Auth::id());

        $rules = [
            'code' => 'required|string',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()
            ], 422);
        }

        $token = $request->header('X-Email-Verification-Token');

        if (!$token) {
            return response()->json([
                'error' => 'Missing email verification tokens'
            ], 401); 
        }

        try {
            $decoded = JWTAuth::getJWTProvider()->decode($token);

            $emailVerification = EmailVerification::where('id', $decoded['sub'])
                ->where('user_id', $user->id)
                ->first();

            if (!$emailVerification) {
                return response()->json([
                    'error' => 'Invalid or expired verification token'
                ], 401);
            }

            if ($emailVerification->is_verified &&
                $emailVerification->expires_at > Carbon::now()
            ) {
                return response()->json([
                    'message' => 'Email has been verified',
                    'status' => 'already_verified'
                ], 200); 
            }

            if (hash_equals($emailVerification->code, $request['code'])) {
                DB::beginTransaction();
    
                try {
                    $emailVerification->update([
                        'is_verified' => true,
                        'expires_at' => Carbon::now()->addDays(1),
                    ]);
                    
                    if (!$user->email && !$user->email_verified_at) {
                        $user->update([
                            'email' => $emailVerification->email,
                            'email_verified_at' => Carbon::now(),
                        ]);
                    }
    
                    DB::commit();
    
                    $newToken = $this->createToken($emailVerification->id, 1440);
    
                    return response()->json([
                        'message' => 'Email verified successfully',
                        'status' => 'verified',
                        'email_verification_token' => $newToken,
                    ], 200);
                } catch (\Exception $e) {
                    DB::rollback();
    
                    return response()->json([ 
                        'error' => 'Verification failed', 
                        'message' => $e->getMessage() 
                    ], 500);
                }
            } else {
                return response()->json([
                    'error' => 'Invalid verification code'
                ], 422);
            }
        } catch (JWTException $e) {
            return response()->json([
                'Token invalid',
            ], 401); 
        }
    }

    public function updateEmail(Request $request) 
    {
        $user = User::find(Auth::id());

        $rules = [
            'code' => 'required|string',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()
            ], 422);
        }   

        $emailVerificationToken = $request->header('X-Email-Verification-Token');
        $newEmailVerificationToken = $request->header('X-New-Email-Verification-Token');

        if (!$emailVerificationToken || !$newEmailVerificationToken) {
            return response()->json([
                'error' => 'Missing email verification tokens'
            ], 401); 
        }

        try {
            $emailVerificationDecoded = JWTAuth::getJWTProvider()->decode($emailVerificationToken);
            $newEmailVerificationDecoded = JWTAuth::getJWTProvider()->decode($newEmailVerificationToken);
        } catch (JWTException $e) {
            return response()->json([
                'Token invalid'
            ], 401); 
        }

        $emailVerification = EmailVerification::where('id', $emailVerificationDecoded['sub'])
            ->where('user_id', $user->id)
            ->first();

        $newEmailVerification = EmailVerification::where('id', $newEmailVerificationDecoded['sub'])
            ->where('user_id', $user->id)
            ->first();

        if (!$emailVerification) {
            return response()->json([
                'error' => 'Current email verification record not found.'
            ], 404);
        }

        if (!$newEmailVerification) {
            return response()->json([
                'error' => 'New email verification record not found.'
            ], 404);
        }

        if (
            $emailVerification->email !== $user->email ||
            !$emailVerification->is_verified ||
            $emailVerification->expires_at <= Carbon::now()
        ) {
            return response()->json([
                'error' => 'Current email verification token is invalid or expired.'
            ], 422);
        }

        if (
            $newEmailVerification->email !== $user->email &&
            $newEmailVerification->expires_at > Carbon::now() &&
            hash_equals($newEmailVerification->code, $request['code'])
        ) {
            DB::beginTransaction();
    
            try {
                $newEmailVerification->update([
                    'is_verified' => true,
                    'expires_at' => Carbon::now()->addDays(1),
                ]);

                $emailVerification->delete();
                
                $user->update([
                    'email' => $newEmailVerification->email,
                    'email_verified_at' => Carbon::now(),
                ]);

                DB::commit();

                $newToken = $this->createToken($newEmailVerification->id, 1440);

                return response()->json([
                    'message' => 'Email verified successfully',
                    'email_verification_token' => $newToken,
                ], 200);
            } catch (\Exception $e) {
                DB::rollback();

                return response()->json([ 
                    'error' => 'Verification failed', 
                    'message' => $e->getMessage() 
                ], 500);
            } 
        } else {
            return response()->json([
                'error' => 'Invalid verification code',
                'message' => 'The verification code provided is incorrect or expired.'
            ], 422);
        }
    }

    public function checkAuth(Request $request)
    {
        return response()->json([
            'authenticated' => Auth::check(),
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'invalid_credentials'], 401);
        }

        $user = auth('api')->user();

        return response()->json([
            'refresh_token' => $this->createToken($user->id, config('jwt.refresh_ttl')),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ]);
    }

    public function createToken($id, $exp) {
        $data = [
            'sub' => $id,
            'random' => rand() . time(),
            'exp' => time() + $exp,
        ];

        $refreshToken = JWTAuth::getJWTProvider()->encode($data);

        return $refreshToken;
    }

    public function refresh(Request $request)
    {
        $refreshToken = $request->input('refresh_token'); 

        if (!$refreshToken) {
            return response()->json([
                'error' => 'Token invalid'
            ], 401);
        }
        
        try {
            $decoded = JWTAuth::getJWTProvider()->decode($refreshToken);

            $user = User::find($decoded['sub']);

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            $token = auth('api')->login($user);

            return response()->json([
                'access_token' => $token,
                'expires_in' => config('jwt.ttl') * 60,
            ]);
        } catch (JWTException $e) {
            return response()->json(['Refresh token invalid'], 401);   
        }
    }

    public function logout(Request $request) {
        Auth::logout();
    }

    public function testEmail() {
        $verify = [
            'username' => 'trieunv',
            'name' => 'Nguyễn Triều',
            'token' => '342178',
        ];

        Mail::to('trieudien112@gmail.com')->send(new VerifyEmail($verify));
        
        return response()->json([
            'message' => 'Send message success',
        ], 200);
    }
}
