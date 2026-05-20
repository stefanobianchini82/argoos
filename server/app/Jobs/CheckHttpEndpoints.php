<?php

namespace App\Jobs;

use App\Models\HttpCheck;
use App\Services\HttpChecker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckHttpEndpoints implements ShouldQueue
{
    use Queueable;

    public function handle(HttpChecker $checker): void
    {
        HttpCheck::active()->with('host')->each(function (HttpCheck $check) use ($checker) {
            $checker->check($check);
        });
    }
}
