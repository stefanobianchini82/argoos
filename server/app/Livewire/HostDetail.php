<?php

namespace App\Livewire;

use App\Models\DiskPartition;
use App\Models\Host;
use App\Models\Metric;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class HostDetail extends Component
{
    public Host $host;

    public bool $confirmingDelete = false;

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
