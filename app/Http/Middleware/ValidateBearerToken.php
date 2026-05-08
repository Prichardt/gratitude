<?php

namespace App\Http\Middleware;

use App\Models\ApplicationKey;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class ValidateBearerToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if (! $bearer || ! str_contains($bearer, '|')) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $token = PersonalAccessToken::findToken($bearer);

        $tokenable = $token?->tokenable;

        if (! $token || ! ($tokenable instanceof ApplicationKey) || $tokenable->status !== 'active') {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $token->forceFill(['last_used_at' => now()])->save();

        return $next($request);
    }
}
