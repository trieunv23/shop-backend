<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ValidateRulesRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $rules)
    {
        $rules = urldecode($rules); 
        $rules = json_decode($rules, true);

        if ($rules === null) {
            return response()->json([
                'error' => 'Invalid JSON format for validation rules'
            ], 400);
        }

        if (!is_array($rules)) {
            return response()->json([
                'error' => 'Invalid validation rules'
            ], 400);
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        return $next($request);
    }
}
