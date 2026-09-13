<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TokenAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $user = $token ? User::where('api_token', $token)->first() : null;

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->attributes->set('auth_user', $user);

        return $next($request);
    }
}