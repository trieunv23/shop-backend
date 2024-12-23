<?php

namespace App\Http\Middleware;

use App\Helpers\StringHelper;
use Closure;
use Illuminate\Http\Request;

class ModifyResponse
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
        $response = $next($request);

        if ($response->isSuccessful()) {
            $content = $response->getContent();

            $data = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $modifiedData = StringHelper::convertToCamelCase($data);
                
                $response->setContent(json_encode($modifiedData));
            }
        }

        return $response;
    }
}
