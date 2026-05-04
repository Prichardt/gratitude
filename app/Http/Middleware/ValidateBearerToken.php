<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ValidateBearerToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if (! $bearer || ! str_contains($bearer, '|')) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        [$id, $secret] = explode('|', $bearer, 2);

        $token = DB::connection('auth_db')
            ->table('personal_access_tokens')
            ->where('id', (int) $id)
            ->first();

        if (! $token || ! hash_equals($token->token, hash('sha256', $secret))) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
