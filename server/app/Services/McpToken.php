<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Bearer token for the MCP endpoint. Only its SHA-256 hash is stored
 * (in settings); the plaintext is returned once by generate().
 */
class McpToken
{
    public const PREFIX = 'argoos_mcp_';

    private const CACHE_KEY = 'mcp.token_hash';

    /** Create a new token, replacing (and invalidating) any previous one. */
    public function generate(): string
    {
        $token = self::PREFIX . bin2hex(random_bytes(32));

        DB::transaction(function () use ($token): void {
            Setting::set(Setting::MCP_TOKEN_HASH, hash('sha256', $token));
            Setting::set(Setting::MCP_TOKEN_HINT, substr($token, -4));
            Setting::set(Setting::MCP_TOKEN_CREATED_AT, now()->toIso8601String());
        });

        Cache::forget(self::CACHE_KEY);

        return $token;
    }

    /** Remove the token: the MCP endpoint is disabled until a new one is generated. */
    public function revoke(): void
    {
        Setting::whereIn('key', [
            Setting::MCP_TOKEN_HASH,
            Setting::MCP_TOKEN_HINT,
            Setting::MCP_TOKEN_CREATED_AT,
        ])->delete();

        Cache::forget(self::CACHE_KEY);
    }

    public function verify(?string $token): bool
    {
        if (! is_string($token) || $token === '') {
            return false;
        }

        $hash = Cache::remember(self::CACHE_KEY, 60, fn () => (string) Setting::get(Setting::MCP_TOKEN_HASH, ''));

        return $hash !== '' && hash_equals($hash, hash('sha256', $token));
    }

    /**
     * @return array{enabled: bool, hint: ?string, created_at: ?Carbon}
     */
    public function status(): array
    {
        $createdAt = Setting::get(Setting::MCP_TOKEN_CREATED_AT);

        return [
            'enabled'    => filled(Setting::get(Setting::MCP_TOKEN_HASH)),
            'hint'       => Setting::get(Setting::MCP_TOKEN_HINT),
            'created_at' => $createdAt ? Carbon::parse($createdAt) : null,
        ];
    }
}
