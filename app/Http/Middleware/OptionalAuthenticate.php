<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class OptionalAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken()) {
            try {
                JWTAuth::parseToken()->authenticate();
            } catch (\Throwable) {
                // Invalid token - continue as unauthenticated
            }
        }

        return $next($request);
    }
}
