<?php

namespace App\Services;

use App\Models\Host;
use App\Models\Metric;
use Illuminate\Support\Facades\Cache;

class MetricAggregator
{
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

    private function format(\Illuminate\Support\Collection $rows, string $timeKey = 'collected_at'): array
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
