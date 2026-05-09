<?php

namespace App\Jobs;

use App\Models\Host;
use App\Models\Setting;
use App\Notifications\HostOffline;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Notifications\AnonymousNotifiable;

class CheckHostsOffline implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $emailEnabled    = filter_var(Setting::get(Setting::HOST_OFFLINE_EMAIL_ENABLED, true), FILTER_VALIDATE_BOOLEAN);
        $telegramEnabled = filter_var(Setting::get(Setting::HOST_OFFLINE_TELEGRAM_ENABLED, false), FILTER_VALIDATE_BOOLEAN);
        $slackEnabled    = filter_var(Setting::get(Setting::HOST_OFFLINE_SLACK_ENABLED, false), FILTER_VALIDATE_BOOLEAN);

        $alertEmail      = Setting::get(Setting::ALERT_EMAIL);
        $telegramChatId  = Setting::get(Setting::TELEGRAM_CHAT_ID);
        $slackWebhookUrl = Setting::get(Setting::SLACK_WEBHOOK_URL);

        $hasEmail    = $emailEnabled && filled($alertEmail);
        $hasTelegram = $telegramEnabled && filled($telegramChatId);
        $hasSlack    = $slackEnabled && filled($slackWebhookUrl);

        if (! $hasEmail && ! $hasTelegram && ! $hasSlack) {
            return;
        }

        Host::query()
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '<', now()->subMinutes(3))
            ->where(function ($q) {
                $q->whereNull('last_offline_notified_at')
                  ->orWhere('last_offline_notified_at', '<', now()->subMinutes(10));
            })
            ->each(function (Host $host) use ($alertEmail, $telegramChatId, $slackWebhookUrl, $hasEmail, $hasTelegram, $hasSlack) {
                $host->update(['last_offline_notified_at' => now()]);

                $notifiable = new AnonymousNotifiable;

                if ($hasEmail) {
                    $notifiable = $notifiable->route('mail', $alertEmail);
                }

                if ($hasTelegram) {
                    $notifiable = $notifiable->route('telegram', $telegramChatId);
                }

                if ($hasSlack) {
                    $notifiable = $notifiable->route('slack', $slackWebhookUrl);
                }

                $notifiable->notify(new HostOffline($host, $hasEmail, $hasTelegram, $hasSlack));
            });
    }
}
