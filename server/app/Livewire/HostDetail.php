<?php

namespace App\Livewire;

use App\Models\ContainerMetric;
use App\Models\DiskPartition;
use App\Models\Host;
use App\Models\Metric;
use App\Models\ProcessMemory;
use App\Services\HostSnapshot;
use App\Services\MetricAggregator;
use Illuminate\Support\Collection;
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

        $snapshot         = app(HostSnapshot::class);
        $latestPartitions = $latestMetric !== null ? $snapshot->latestPartitions($this->host) : new Collection();
        $latestProcesses  = $latestMetric !== null ? $snapshot->latestProcesses($this->host, $this->procSort, $this->procDir) : new Collection();
        $latestContainers = $latestMetric !== null ? $snapshot->latestContainers($this->host) : new Collection();

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
