<?php

namespace App\Http\Middleware;

use App\Models\Host;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAgent
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-API-Key');

        if (empty($key)) {
            return response()->json(['error' => 'Missing X-API-Key header'], 401);
        }

        $host = Host::findByApiKey($key);

        if ($host === null) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        // Avoid bumping updated_at on every 30-second heartbeat.
        $host->updateQuietly(['last_seen_at' => now()]);

        // Inject the resolved Host via Symfony ParameterBag to keep the request
        // input bag clean and avoid collisions with validated payload fields.
        $request->attributes->set('host', $host);

        return $next($request);
    }
}
