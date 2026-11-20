<?php

namespace App\Services;

use App\Models\ContainerMetric;
use App\Models\DiskPartition;
use App\Models\Host;
use App\Models\ProcessMemory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * "Latest collection" snapshots for a host: the rows sharing the most
 * recent collected_at for partitions, processes and containers.
 */
class HostSnapshot
{
    public function latestPartitions(Host $host): Collection
    {
        $cutoff = now()->subMinutes(10);
        $maxAt  = Cache::remember("disk_max_at.{$host->id}", 15, fn () =>
            DiskPartition::where('host_id', $host->id)
                ->where('collected_at', '>=', $cutoff)
                ->max('collected_at')
        );

        if ($maxAt === null) {
            return new Collection();
        }

        return DiskPartition::where('host_id', $host->id)
            ->where('collected_at', $maxAt)
            ->orderBy('mount_point')
            ->get();
    }

    public function latestProcesses(Host $host, string $sort = 'mem_rss', string $dir = 'desc', ?int $limit = null): Collection
    {
        $maxAt = ProcessMemory::where('host_id', $host->id)->max('collected_at');

        if ($maxAt === null) {
            return new Collection();
        }

        return ProcessMemory::where('host_id', $host->id)
            ->where('collected_at', $maxAt)
            ->orderBy($sort, $dir)
            ->when($limit !== null, fn ($q) => $q->limit($limit))
            ->get();
    }

    public function latestContainers(Host $host): Collection
    {
        $cutoff = now()->subMinutes(10);
        $maxAt  = Cache::remember("container_max_at.{$host->id}", 15, fn () =>
            ContainerMetric::where('host_id', $host->id)
                ->where('collected_at', '>=', $cutoff)
                ->max('collected_at')
        );

        if ($maxAt === null) {
            return new Collection();
        }

        return ContainerMetric::where('host_id', $host->id)
            ->where('collected_at', $maxAt)
            ->orderByDesc('cpu_percent')
            ->get();
    }
}
