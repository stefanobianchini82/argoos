<?php

namespace App\Services;

use App\Models\ContainerMetric;
use App\Models\Host;
use App\Models\Metric;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MetricAggregator
{
    /** Largest number of container series rendered on a chart, by peak memory. */
    private const MAX_CONTAINERS = 12;

    public function getForRange(Host $host, string $range): array
    {
        $ttl = match ($range) {
            '1h'    => 30,
            '6h'    => 60,
            '24h'   => 300,
            '7d'    => 900,
            default => 60,
        };

        return Cache::remember("metrics.{$host->id}.{$range}", $ttl, fn () => match ($range) {
            '1h'    => $this->raw($host, 60),
            '6h'    => $this->aggregated($host, 360, 300),
            '24h'   => $this->aggregated($host, 1440, 900),
            '7d'    => $this->aggregated($host, 10080, 3600),
            default => $this->raw($host, 60),
        });
    }

    /**
     * Per-container CPU% and memory time series for a range, pivoted so each
     * container is one chart line aligned to a shared set of time buckets.
     */
    public function getContainersForRange(Host $host, string $range): array
    {
        $ttl = match ($range) {
            '1h'    => 30,
            '6h'    => 60,
            '24h'   => 300,
            '7d'    => 900,
            default => 30,
        };

        return Cache::remember("containers.{$host->id}.{$range}", $ttl, fn () => match ($range) {
            '6h'    => $this->containerAggregated($host, 360, 300),
            '24h'   => $this->containerAggregated($host, 1440, 900),
            '7d'    => $this->containerAggregated($host, 10080, 3600),
            default => $this->containerRaw($host, 60),
        });
    }

    private function raw(Host $host, int $minutes): array
    {
        $rows = Metric::where('host_id', $host->id)
            ->where('collected_at', '>=', now()->subMinutes($minutes))
            ->orderBy('collected_at')
            ->get([
                'collected_at', 'cpu_usage', 'ram_used', 'ram_total',
                'disk_read_bytes', 'disk_write_bytes',
                'net_rx_bytes', 'net_tx_bytes', 'load_avg_1',
            ]);

        return $this->format($rows);
    }

    private function aggregated(Host $host, int $minutes, int $bucketSecs): array
    {
        $rows = Metric::where('host_id', $host->id)
            ->where('collected_at', '>=', now()->subMinutes($minutes))
            ->selectRaw(
                'ANY_VALUE(FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(collected_at) / ?) * ?)) AS bucket,
                 AVG(cpu_usage)        AS cpu_usage,
                 AVG(ram_used)         AS ram_used,
                 AVG(ram_total)        AS ram_total,
                 AVG(disk_read_bytes)  AS disk_read_bytes,
                 AVG(disk_write_bytes) AS disk_write_bytes,
                 AVG(net_rx_bytes)     AS net_rx_bytes,
                 AVG(net_tx_bytes)     AS net_tx_bytes,
                 AVG(load_avg_1)       AS load_avg_1',
                [$bucketSecs, $bucketSecs]
            )
            ->groupByRaw('FLOOR(UNIX_TIMESTAMP(collected_at) / ?)', [$bucketSecs])
            ->orderByRaw('bucket')
            ->get();

        return $this->format($rows, 'bucket');
    }

    /**
     * Raw per-container series (no time bucketing). Used for the 1h range and
     * SQLite tests, since it avoids MySQL-only date functions.
     */
    private function containerRaw(Host $host, int $minutes): array
    {
        $rows = ContainerMetric::where('host_id', $host->id)
            ->where('collected_at', '>=', now()->subMinutes($minutes))
            ->orderBy('collected_at')
            ->get(['container_name', 'collected_at', 'cpu_percent', 'memory_usage']);

        // Use the exact timestamp as both the bucket key and label.
        $rows->each(function ($r) {
            $r->bucket       = (string) $r->collected_at;
            $r->bucket_label = (string) $r->collected_at;
        });

        return $this->pivotContainers($rows);
    }

    private function containerAggregated(Host $host, int $minutes, int $bucketSecs): array
    {
        $rows = ContainerMetric::where('host_id', $host->id)
            ->where('collected_at', '>=', now()->subMinutes($minutes))
            ->selectRaw(
                'container_name,
                 ANY_VALUE(FLOOR(UNIX_TIMESTAMP(collected_at) / ?)) AS bucket,
                 ANY_VALUE(FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(collected_at) / ?) * ?)) AS bucket_label,
                 AVG(cpu_percent)  AS cpu_percent,
                 AVG(memory_usage) AS memory_usage',
                [$bucketSecs, $bucketSecs, $bucketSecs]
            )
            ->groupByRaw('container_name, FLOOR(UNIX_TIMESTAMP(collected_at) / ?)', [$bucketSecs])
            ->orderByRaw('bucket')
            ->get();

        return $this->pivotContainers($rows);
    }

    /**
     * Pivots flat (container_name, bucket, bucket_label, cpu_percent, memory_usage)
     * rows into one aligned series per container for multi-line charts.
     */
    private function pivotContainers(Collection $rows): array
    {
        if ($rows->isEmpty()) {
            return ['labels' => [], 'containers' => [], 'cpu' => [], 'memory_mb' => []];
        }

        // Shared, ordered list of time buckets across all containers.
        $labels = $rows->unique('bucket')->sortBy('bucket')
            ->mapWithKeys(fn ($r) => [(string) $r->bucket => (string) $r->bucket_label]);
        $bucketIndex = array_flip($labels->keys()->all());

        // Keep the busiest containers (by peak memory) to bound the chart legend.
        $names = $rows->groupBy('container_name')
            ->map(fn ($g) => $g->max('memory_usage'))
            ->sortDesc()
            ->take(self::MAX_CONTAINERS)
            ->keys();

        $cpu   = [];
        $memMb = [];
        foreach ($names as $name) {
            $cpu[$name]   = array_fill(0, $labels->count(), null);
            $memMb[$name] = array_fill(0, $labels->count(), null);
        }

        foreach ($rows as $r) {
            if (! isset($cpu[$r->container_name])) {
                continue;
            }
            $i = $bucketIndex[(string) $r->bucket];
            $cpu[$r->container_name][$i]   = round((float) $r->cpu_percent, 1);
            $memMb[$r->container_name][$i] = round((float) $r->memory_usage / 1024 / 1024, 1);
        }

        return [
            'labels'     => $labels->values()->all(),
            'containers' => $names->values()->all(),
            'cpu'        => $cpu,
            'memory_mb'  => $memMb,
        ];
    }

    private function format(Collection $rows, string $timeKey = 'collected_at'): array
    {
        return [
            'labels'        => $rows->pluck($timeKey)->map(fn ($t) => (string) $t)->values()->all(),
            'cpu_usage'     => $rows->pluck('cpu_usage')->map(fn ($v) => round((float) $v, 1))->values()->all(),
            'ram_pct'       => $rows->map(fn ($r) => $r->ram_total > 0 ? round($r->ram_used / $r->ram_total * 100, 1) : 0.0)->values()->all(),
            'disk_read_kb'  => $rows->pluck('disk_read_bytes')->map(fn ($v) => round((float) $v / 1024, 1))->values()->all(),
            'disk_write_kb' => $rows->pluck('disk_write_bytes')->map(fn ($v) => round((float) $v / 1024, 1))->values()->all(),
            'net_rx_kb'     => $rows->pluck('net_rx_bytes')->map(fn ($v) => round((float) $v / 1024, 1))->values()->all(),
            'net_tx_kb'     => $rows->pluck('net_tx_bytes')->map(fn ($v) => round((float) $v / 1024, 1))->values()->all(),
            'load_avg_1'    => $rows->pluck('load_avg_1')->map(fn ($v) => round((float) $v, 2))->values()->all(),
        ];
    }
}
