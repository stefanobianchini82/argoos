<?php

namespace App\Mcp\Tools;

use App\Models\Host;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('List all hosts monitored by Argoos with online status and a summary of their latest CPU, RAM and disk usage.')]
class ListHosts extends HostTool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'tag'    => ['nullable', 'string'],
            'status' => ['nullable', 'in:online,offline'],
        ]);

        $tag    = $validated['tag'] ?? null;
        $status = $validated['status'] ?? null;

        $hosts = Host::with(['latestMetric', 'tags'])
            ->when($tag, fn ($q) => $q->withAnyTags([$tag]))
            ->orderBy('label')
            ->get();

        if ($status !== null) {
            $hosts = $hosts->filter(fn (Host $h) => $h->isOnline() === ($status === 'online'))->values();
        }

        $diskUsage = $this->diskUsageByHost($hosts->pluck('id')->all());

        $items = $hosts->map(fn (Host $host) => [
            ...$this->hostSummary($host),
            'ip'             => $host->ip,
            'description'    => $host->description,
            'tags'           => $host->tags->pluck('name')->values()->all(),
            'cpu_percent'    => $host->latestMetric?->cpu_usage,
            'ram_percent'    => $host->latestMetric?->ram_percentage,
            'disk_percent'   => $diskUsage[$host->id] ?? null,
            'metrics_at'     => $host->latestMetric?->collected_at?->toIso8601String(),
        ])->all();

        return Response::structured([
            'count' => count($items),
            'hosts' => $items,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'tag'    => $schema->string()->description('Only hosts with this tag.'),
            'status' => $schema->string()->enum(['online', 'offline'])->description('Filter by online status.'),
        ];
    }

    /**
     * Aggregate disk usage % (sum of all partitions) from each host's latest collection.
     *
     * @param  array<int>  $hostIds
     * @return array<int, float>
     */
    private function diskUsageByHost(array $hostIds): array
    {
        if ($hostIds === []) {
            return [];
        }

        $latest = DB::table('disk_partitions')
            ->select('host_id', DB::raw('MAX(collected_at) as max_at'))
            ->whereIn('host_id', $hostIds)
            ->where('collected_at', '>=', now()->subMinutes(10))
            ->groupBy('host_id');

        return DB::table('disk_partitions as dp')
            ->joinSub($latest, 'latest', fn ($join) => $join
                ->on('dp.host_id', '=', 'latest.host_id')
                ->on('dp.collected_at', '=', 'latest.max_at')
            )
            ->select('dp.host_id', DB::raw('SUM(dp.used) as total_used, SUM(dp.total) as grand_total'))
            ->groupBy('dp.host_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->host_id => self::percent($row->total_used, $row->grand_total)])
            ->all();
    }
}
