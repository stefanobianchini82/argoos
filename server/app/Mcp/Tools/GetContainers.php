<?php

namespace App\Mcp\Tools;

use App\Models\ContainerMetric;
use App\Services\HostSnapshot;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Get the Docker containers running on a host from the latest collection, with CPU and memory usage.')]
class GetContainers extends HostTool
{
    public function handle(Request $request, HostSnapshot $snapshot): Response|ResponseFactory
    {
        $host = $this->resolveHost($request->get('host'));
        if (is_string($host)) {
            return Response::error($host);
        }

        $containers = $snapshot->latestContainers($host);

        return Response::structured([
            'host'         => $this->hostSummary($host),
            'collected_at' => $containers->first()?->collected_at?->toIso8601String(),
            'containers'   => $containers->map(fn (ContainerMetric $c) => [
                'id'                 => $c->container_id,
                'name'               => $c->container_name,
                'image'              => $c->image,
                'cpu_percent'        => $c->cpu_percent,
                'memory_usage_bytes' => $c->memory_usage,
                'memory_limit_bytes' => $c->memory_limit,
                'memory_percent'     => self::percent($c->memory_usage, $c->memory_limit),
            ])->values()->all(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return ['host' => $this->hostSchema($schema)];
    }
}
