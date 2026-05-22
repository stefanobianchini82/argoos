<?php

namespace App\Livewire;

use App\Models\DiskPartition;
use App\Models\Host;
use App\Models\Metric;
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
            $this->host->delete();
        });

        $this->redirect('/');
    }

    public function setRange(string $range): void
    {
        if (! in_array($range, self::$validRanges, true)) {
            return;
        }

        $this->range = $range;
        $data = app(MetricAggregator::class)->getForRange($this->host, $this->range);
        $this->dispatch('charts-updated', data: $data);
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

        $chartData = app(MetricAggregator::class)->getForRange($this->host, $this->range);

        return view('livewire.host-detail', [
            'latestMetric'     => $latestMetric,
            'latestPartitions' => $latestPartitions,
            'chartData'        => $chartData,
            'range'            => $this->range,
            'validRanges'      => self::$validRanges,
        ])->layout('layouts.app', ['title' => $this->host->label . ' — Argoos']);
    }
}
