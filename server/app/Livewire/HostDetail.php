<?php

namespace App\Livewire;

use App\Models\DiskPartition;
use App\Models\Host;
use Illuminate\Support\Collection;
use Livewire\Component;

class HostDetail extends Component
{
    public Host $host;

    public function render()
    {
        $latestMetric = $this->host->latestMetric;

        $latestPartitions = new Collection();
        if ($latestMetric !== null) {
            $maxAt = DiskPartition::where('host_id', $this->host->id)->max('collected_at');
            if ($maxAt !== null) {
                $latestPartitions = DiskPartition::where('host_id', $this->host->id)
                    ->where('collected_at', $maxAt)
                    ->orderBy('mount_point')
                    ->get();
            }
        }

        return view('livewire.host-detail', [
            'latestMetric'     => $latestMetric,
            'latestPartitions' => $latestPartitions,
        ])->layout('layouts.app', ['title' => $this->host->label . ' — Argoos']);
    }
}
