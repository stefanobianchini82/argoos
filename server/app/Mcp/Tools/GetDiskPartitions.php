<?php

namespace App\Mcp\Tools;

use App\Models\DiskPartition;
use App\Services\HostSnapshot;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Get the latest disk usage per mount point (partition) of a host.')]
class GetDiskPartitions extends HostTool
{
    public function handle(Request $request, HostSnapshot $snapshot): Response|ResponseFactory
    {
        $host = $this->resolveHost($request->get('host'));
        if (is_string($host)) {
            return Response::error($host);
        }

        $partitions = $snapshot->latestPartitions($host);

        return Response::structured([
            'host'         => $this->hostSummary($host),
            'collected_at' => $partitions->first()?->collected_at?->toIso8601String(),
            'partitions'   => $partitions->map(fn (DiskPartition $p) => [
                'mount_point' => $p->mount_point,
                'total_bytes' => $p->total,
                'used_bytes'  => $p->used,
                'free_bytes'  => $p->free,
                'used_percent' => self::percent($p->used, $p->total),
            ])->values()->all(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return ['host' => $this->hostSchema($schema)];
    }
}
