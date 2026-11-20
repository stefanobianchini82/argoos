<?php

namespace App\Http\Middleware;

use App\Services\McpToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMcp
{
    public function __construct(private McpToken $tokens) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->tokens->verify($request->bearerToken())) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
