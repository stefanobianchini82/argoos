<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Get the latest collected system metrics of a host: CPU, RAM, load average, disk I/O, network I/O and uptime. Byte counters for disk/network are deltas since the previous collection.')]
class GetHostMetrics extends HostTool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $host = $this->resolveHost($request->get('host'));
        if (is_string($host)) {
            return Response::error($host);
        }

        $m = $host->latestMetric;
        if ($m === null) {
            return Response::error("No metrics collected yet for host \"{$host->label}\".");
        }

        return Response::structured([
            'host'    => $this->hostSummary($host),
            'metrics' => [
                'collected_at'     => $m->collected_at->toIso8601String(),
                'cpu_percent'      => $m->cpu_usage,
                'ram_used_bytes'   => $m->ram_used,
                'ram_total_bytes'  => $m->ram_total,
                'ram_percent'      => $m->ram_percentage,
                'load_avg_1'       => $m->load_avg_1,
                'load_avg_5'       => $m->load_avg_5,
                'load_avg_15'      => $m->load_avg_15,
                'disk_read_bytes'  => $m->disk_read_bytes,
                'disk_write_bytes' => $m->disk_write_bytes,
                'net_rx_bytes'     => $m->net_rx_bytes,
                'net_tx_bytes'     => $m->net_tx_bytes,
                'uptime_seconds'   => $m->uptime_seconds,
                'uptime'           => $m->formatted_uptime,
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return ['host' => $this->hostSchema($schema)];
    }
}
