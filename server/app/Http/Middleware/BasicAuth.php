<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedUser     = config('dashboard.user');
        $expectedPassword = config('dashboard.password');

        $user     = $request->getUser();
        $password = $request->getPassword();

        if (
            !is_string($user) ||
            !is_string($password) ||
            !hash_equals($expectedUser, $user) ||
            !hash_equals($expectedPassword, $password)
        ) {
            return response('Unauthorized', 401, [
                'WWW-Authenticate' => 'Basic realm="Argoos"',
            ]);
        }

        return $next($request);
    }
}
