<?php

namespace App\Jobs;

use App\Models\ProcessMemory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PruneProcessMemory implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        ProcessMemory::where('collected_at', '<', now()->subHours(24))->delete();
    }
}
