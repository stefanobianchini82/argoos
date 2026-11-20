<?php

namespace App\Mcp\Tools;

use App\Models\Host;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Server\Tool;

/**
 * Base class for tools that target a single host, identified by id or label.
 */
abstract class HostTool extends Tool
{
    protected function hostSchema(JsonSchema $schema): Type
    {
        return $schema->string()
            ->description('Host numeric id or exact label (as returned by list-hosts).')
            ->required();
    }

    /**
     * @return Host|string Host on success, error message otherwise.
     */
    protected function resolveHost(mixed $identifier): Host|string
    {
        $identifier = trim((string) $identifier);

        if ($identifier === '') {
            return 'The "host" argument is required.';
        }

        if (ctype_digit($identifier) && ($host = Host::find((int) $identifier)) !== null) {
            return $host;
        }

        $matches = Host::where('label', $identifier)->limit(2)->get();

        return match ($matches->count()) {
            1       => $matches->first(),
            0       => "Host \"{$identifier}\" not found. Use list-hosts to see available hosts.",
            default => "Label \"{$identifier}\" matches multiple hosts; use the numeric id instead.",
        };
    }

    protected function hostSummary(Host $host): array
    {
        return [
            'id'           => $host->id,
            'label'        => $host->label,
            'online'       => $host->isOnline(),
            'last_seen_at' => $host->last_seen_at?->toIso8601String(),
        ];
    }

    protected static function percent(int|float|null $part, int|float|null $total): ?float
    {
        return $total > 0 ? round($part / $total * 100, 1) : null;
    }
}
