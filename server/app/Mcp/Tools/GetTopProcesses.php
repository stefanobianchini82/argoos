<?php

namespace App\Mcp\Tools;

use App\Models\ProcessMemory;
use App\Services\HostSnapshot;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Get the top processes of a host from the latest collection, sorted by memory (RSS) or CPU usage.')]
class GetTopProcesses extends HostTool
{
    public function handle(Request $request, HostSnapshot $snapshot): Response|ResponseFactory
    {
        $validated = $request->validate([
            'sort_by' => ['nullable', 'in:mem_rss,cpu_percent'],
            'limit'   => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $host = $this->resolveHost($request->get('host'));
        if (is_string($host)) {
            return Response::error($host);
        }

        $processes = $snapshot->latestProcesses(
            $host,
            $validated['sort_by'] ?? 'mem_rss',
            'desc',
            (int) ($validated['limit'] ?? 10),
        );

        return Response::structured([
            'host'         => $this->hostSummary($host),
            'collected_at' => $processes->first()?->collected_at?->toIso8601String(),
            'processes'    => $processes->map(fn (ProcessMemory $p) => [
                'pid'           => $p->pid,
                'name'          => $p->name,
                'mem_rss_bytes' => $p->mem_rss,
                'cpu_percent'   => $p->cpu_percent,
            ])->values()->all(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'host'    => $this->hostSchema($schema),
            'sort_by' => $schema->string()->enum(['mem_rss', 'cpu_percent'])->description('Sort column (default mem_rss).'),
            'limit'   => $schema->integer()->min(1)->max(50)->description('Max processes to return (default 10).'),
        ];
    }
}
