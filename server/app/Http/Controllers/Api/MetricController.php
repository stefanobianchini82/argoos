<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContainerMetric;
use App\Models\DiskPartition;
use App\Models\Host;
use App\Models\Metric;
use App\Models\ProcessMemory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MetricController extends Controller
{
    /**
     * POST /api/v1/metrics
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'collected_at'              => ['required', 'date'],
            'cpu_usage'                 => ['required', 'numeric', 'min:0', 'max:100'],
            'ram_used'                  => ['required', 'integer', 'min:0'],
            'ram_total'                 => ['required', 'integer', 'min:0'],
            'disk_read_bytes'           => ['required', 'integer', 'min:0'],
            'disk_write_bytes'          => ['required', 'integer', 'min:0'],
            'net_rx_bytes'              => ['required', 'integer', 'min:0'],
            'net_tx_bytes'              => ['required', 'integer', 'min:0'],
            'load_avg_1'                => ['required', 'numeric', 'min:0'],
            'load_avg_5'                => ['required', 'numeric', 'min:0'],
            'load_avg_15'               => ['required', 'numeric', 'min:0'],
            'uptime_seconds'            => ['nullable', 'integer', 'min:0'],
            'disk_partitions'           => ['required', 'array', 'min:1'],
            'disk_partitions.*.mount'   => ['required', 'string', 'max:255'],
            'disk_partitions.*.total'   => ['required', 'integer', 'min:0'],
            'disk_partitions.*.used'    => ['required', 'integer', 'min:0'],
            'disk_partitions.*.free'    => ['required', 'integer', 'min:0'],
            'processes'                 => ['sometimes', 'array'],
            'processes.*.pid'           => ['required', 'integer', 'min:1'],
            'processes.*.name'          => ['required', 'string', 'max:255'],
            'processes.*.mem_rss'       => ['required', 'integer', 'min:0'],
            'processes.*.cpu_percent'   => ['sometimes', 'numeric', 'min:0'],
            'containers'                => ['sometimes', 'array'],
            'containers.*.id'           => ['required', 'string', 'max:64'],
            'containers.*.name'         => ['required', 'string', 'max:255'],
            'containers.*.image'        => ['nullable', 'string', 'max:255'],
            'containers.*.cpu_percent'  => ['sometimes', 'numeric', 'min:0'],
            'containers.*.memory_usage' => ['required', 'integer', 'min:0'],
            'containers.*.memory_limit' => ['required', 'integer', 'min:0'],
        ]);

        /** @var Host $host */
        $host = $request->attributes->get('host');

        $collectedAt = Carbon::parse($validated['collected_at'])->format('Y-m-d H:i:s');

        if (!empty($validated['processes'])) {
            ProcessMemory::where('host_id', $host->id)->delete();
        }

        DB::transaction(function () use ($host, $validated, $collectedAt) {
            Metric::create([
                'host_id'          => $host->id,
                'collected_at'     => $collectedAt,
                'cpu_usage'        => $validated['cpu_usage'],
                'ram_used'         => $validated['ram_used'],
                'ram_total'        => $validated['ram_total'],
                'disk_read_bytes'  => $validated['disk_read_bytes'],
                'disk_write_bytes' => $validated['disk_write_bytes'],
                'net_rx_bytes'     => $validated['net_rx_bytes'],
                'net_tx_bytes'     => $validated['net_tx_bytes'],
                'load_avg_1'       => $validated['load_avg_1'],
                'load_avg_5'       => $validated['load_avg_5'],
                'load_avg_15'      => $validated['load_avg_15'],
                'uptime_seconds'   => $validated['uptime_seconds'] ?? null,
            ]);

            $partitions = array_map(fn(array $p) => [
                'host_id'      => $host->id,
                'mount_point'  => $p['mount'],
                'total'        => $p['total'],
                'used'         => $p['used'],
                'free'         => $p['free'],
                'collected_at' => $collectedAt,
            ], $validated['disk_partitions']);

            DiskPartition::insert($partitions);

            if (!empty($validated['containers'])) {
                $containers = array_map(fn(array $c) => [
                    'host_id'        => $host->id,
                    'container_id'   => $c['id'],
                    'container_name' => $c['name'],
                    'image'          => $c['image'] ?? null,
                    'cpu_percent'    => $c['cpu_percent'] ?? 0.0,
                    'memory_usage'   => $c['memory_usage'],
                    'memory_limit'   => $c['memory_limit'],
                    'collected_at'   => $collectedAt,
                ], $validated['containers']);

                ContainerMetric::insert($containers);
            }
        });

        if (!empty($validated['processes'])) {
            $processes = array_map(fn(array $p) => [
                'host_id'      => $host->id,
                'pid'          => $p['pid'],
                'name'         => $p['name'],
                'mem_rss'      => $p['mem_rss'],
                'cpu_percent'  => $p['cpu_percent'] ?? 0.0,
                'collected_at' => $collectedAt,
            ], $validated['processes']);

            ProcessMemory::insert($processes);
        }

        return response()->json(['status' => 'created'], 201);
    }
}
