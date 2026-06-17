<?php

namespace App\Jobs;

use App\Models\DiskPartition;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PruneDiskPartitions implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        DiskPartition::where('collected_at', '<', now()->subHour())->delete();
    }
}
