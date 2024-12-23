<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $admin = JWTAuth::parseToken()->authenticate(); 
            
            if (!$admin || !$admin instanceof \App\Models\Admin) {
                return response()->json([
                    'error' => 'Unauthorized'
                ], 401);
            }
            
            
        } catch (JWTException $e) {
            return response()->json([
                'error' => 'Token is invalid or expired'
            ], 401);
        }

        return $next($request);
    }
}
