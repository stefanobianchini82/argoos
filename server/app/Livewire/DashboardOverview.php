<?php

namespace App\Livewire;

use App\Models\Host;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class DashboardOverview extends Component
{
    public function render()
    {
        $hosts   = Host::with('latestMetric')->orderBy('label')->get();
        $hostIds = $hosts->pluck('id')->all();

        $diskUsagePct = [];
        if (! empty($hostIds)) {
            $rows = DB::table('disk_partitions')
                ->select('host_id', DB::raw('SUM(used) as total_used, SUM(total) as grand_total'))
                ->whereIn('host_id', $hostIds)
                ->where('collected_at', DB::raw(
                    '(SELECT MAX(d2.collected_at) FROM disk_partitions d2 WHERE d2.host_id = disk_partitions.host_id)'
                ))
                ->groupBy('host_id')
                ->get();

            foreach ($rows as $row) {
                $diskUsagePct[$row->host_id] = $row->grand_total > 0
                    ? round($row->total_used / $row->grand_total * 100, 1)
                    : 0;
            }
        }

        return view('livewire.dashboard-overview', compact('hosts', 'diskUsagePct'))
            ->layout('layouts.app');
    }
}
