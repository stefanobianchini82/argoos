<?php

namespace App\Livewire;

use App\Models\Host;
use Illuminate\Support\Facades\Cache;
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
            $cacheKey     = 'disk_usage_overview.' . md5(implode(',', $hostIds));
            $diskUsagePct = Cache::remember($cacheKey, 30, function () use ($hostIds) {
                $cutoff = now()->subMinutes(10);

                $latest = DB::table('disk_partitions')
                    ->select('host_id', DB::raw('MAX(collected_at) as max_at'))
                    ->whereIn('host_id', $hostIds)
                    ->where('collected_at', '>=', $cutoff)
                    ->groupBy('host_id');

                $rows = DB::table('disk_partitions as dp')
                    ->joinSub($latest, 'latest', fn ($join) => $join
                        ->on('dp.host_id', '=', 'latest.host_id')
                        ->on('dp.collected_at', '=', 'latest.max_at')
                    )
                    ->select('dp.host_id', DB::raw('SUM(dp.used) as total_used, SUM(dp.total) as grand_total'))
                    ->groupBy('dp.host_id')
                    ->get();

                $result = [];
                foreach ($rows as $row) {
                    $result[$row->host_id] = $row->grand_total > 0
                        ? round($row->total_used / $row->grand_total * 100, 1)
                        : 0;
                }
                return $result;
            });
        }

        return view('livewire.dashboard-overview', compact('hosts', 'diskUsagePct'))
            ->layout('layouts.app');
    }
}
