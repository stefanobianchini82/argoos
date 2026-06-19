<?php

namespace App\Livewire;

use App\Models\ContainerMetric;
use App\Models\DiskPartition;
use App\Models\Host;
use App\Models\Metric;
use App\Models\ProcessMemory;
use App\Services\MetricAggregator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class HostDetail extends Component
{
    public Host $host;

    public bool $confirmingDelete = false;

    public string $range = '1h';

    public string $procSort = 'mem_rss';

    public string $procDir = 'desc';

    private static array $validRanges = ['1h', '6h', '24h', '7d'];

    public function confirmDelete(): void
    {
        $this->confirmingDelete = true;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = false;
    }

    public function deleteHost(): void
    {
        DB::transaction(function () {
            Metric::where('host_id', $this->host->id)->delete();
            DiskPartition::where('host_id', $this->host->id)->delete();
            ProcessMemory::where('host_id', $this->host->id)->delete();
            ContainerMetric::where('host_id', $this->host->id)->delete();
            $this->host->delete();
        });

        $this->redirect('/');
    }

    public function sortProcesses(string $column): void
    {
        if (! in_array($column, ['mem_rss', 'cpu_percent'], true)) {
            return;
        }
        if ($this->procSort === $column) {
            $this->procDir = $this->procDir === 'desc' ? 'asc' : 'desc';
        } else {
            $this->procSort = $column;
            $this->procDir  = 'desc';
        }
    }

    public function setRange(string $range): void
    {
        if (! in_array($range, self::$validRanges, true)) {
            return;
        }

        $this->range = $range;
        $aggregator = app(MetricAggregator::class);
        $this->dispatch('charts-updated', data: $aggregator->getForRange($this->host, $this->range));
        $this->dispatch('container-charts-updated', data: $aggregator->getContainersForRange($this->host, $this->range));
    }

    public function render()
    {
        $latestMetric = $this->host->latestMetric;

        $latestPartitions = new Collection();
        if ($latestMetric !== null) {
            $cutoff = now()->subMinutes(10);
            $maxAt  = Cache::remember("disk_max_at.{$this->host->id}", 15, fn () =>
                DiskPartition::where('host_id', $this->host->id)
                    ->where('collected_at', '>=', $cutoff)
                    ->max('collected_at')
            );
            if ($maxAt !== null) {
                $latestPartitions = DiskPartition::where('host_id', $this->host->id)
                    ->where('collected_at', $maxAt)
                    ->orderBy('mount_point')
                    ->get();
            }
        }

        $latestProcesses = new Collection();
        if ($latestMetric !== null) {
            $maxProcAt = ProcessMemory::where('host_id', $this->host->id)->max('collected_at');
            if ($maxProcAt !== null) {
                $latestProcesses = ProcessMemory::where('host_id', $this->host->id)
                    ->where('collected_at', $maxProcAt)
                    ->orderBy($this->procSort, $this->procDir)
                    ->get();
            }
        }

        $latestContainers = new Collection();
        if ($latestMetric !== null) {
            $cutoff         = now()->subMinutes(10);
            $maxContainerAt = Cache::remember("container_max_at.{$this->host->id}", 15, fn () =>
                ContainerMetric::where('host_id', $this->host->id)
                    ->where('collected_at', '>=', $cutoff)
                    ->max('collected_at')
            );
            if ($maxContainerAt !== null) {
                $latestContainers = ContainerMetric::where('host_id', $this->host->id)
                    ->where('collected_at', $maxContainerAt)
                    ->orderByDesc('cpu_percent')
                    ->get();
            }
        }

        $aggregator         = app(MetricAggregator::class);
        $chartData          = $aggregator->getForRange($this->host, $this->range);
        $containerChartData = $aggregator->getContainersForRange($this->host, $this->range);

        return view('livewire.host-detail', [
            'latestMetric'       => $latestMetric,
            'latestPartitions'   => $latestPartitions,
            'latestProcesses'    => $latestProcesses,
            'latestContainers'   => $latestContainers,
            'chartData'          => $chartData,
            'containerChartData' => $containerChartData,
            'range'              => $this->range,
            'validRanges'        => self::$validRanges,
            'procSort'           => $this->procSort,
            'procDir'            => $this->procDir,
        ])->layout('layouts.app', ['title' => $this->host->label . ' — Argoos']);
    }
}
