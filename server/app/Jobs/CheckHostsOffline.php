<?php

namespace App\Jobs;

use App\Models\Host;
use App\Notifications\HostOffline;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Log;

class CheckHostsOffline implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $alertEmail = config('dashboard.alert_email');

        if (blank($alertEmail)) {
            return;
        }

        // Find hosts that have gone offline (last_seen_at > 3 min ago)
        // and haven't been notified in the last 10 minutes to avoid spam.
        Host::query()
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '<', now()->subMinutes(3))
            ->where(function ($q) {
                $q->whereNull('last_offline_notified_at')
                  ->orWhere('last_offline_notified_at', '<', now()->subMinutes(10));
            })
            ->each(function (Host $host) use ($alertEmail) {
                $host->update(['last_offline_notified_at' => now()]);

                $notifiable = (new AnonymousNotifiable)
                    ->route('mail', $alertEmail)
                    ->route('telegram', $alertEmail)
                    ->route('webhook', $alertEmail);

                $notifiable->notify(new HostOffline($host));
            });
    }
}
