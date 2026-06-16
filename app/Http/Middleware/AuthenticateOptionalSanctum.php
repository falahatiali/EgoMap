<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateOptionalSanctum
{
    /**
     * Resolve a Bearer token when present so public API routes can still
     * personalize responses (viewer reactions, can_delete, etc.).
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (is_string($token) && $token !== '') {
            $accessToken = PersonalAccessToken::findToken($token);

            if ($accessToken !== null && $accessToken->tokenable !== null) {
                auth()->setUser($accessToken->tokenable);
            }
        }

        return $next($request);
    }
}
