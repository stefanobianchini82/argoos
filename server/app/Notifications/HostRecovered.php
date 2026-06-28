<?php

namespace App\Notifications;

use App\Models\Host;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class HostRecovered extends Notification
{
    public function __construct(
        public readonly Host $host,
        private readonly bool $emailEnabled = true,
        private readonly bool $telegramEnabled = false,
        private readonly bool $slackEnabled = false,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = [];

        if ($this->emailEnabled) {
            $channels[] = 'mail';
        }

        if ($this->telegramEnabled) {
            $channels[] = 'telegram';
        }

        if ($this->slackEnabled) {
            $channels[] = 'slack';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lastSeen = $this->host->last_seen_at?->format('Y-m-d H:i:s').' UTC' ?? 'unknown';

        return (new MailMessage)
            ->subject("Host tornato online: {$this->host->label}")
            ->greeting("Host {$this->host->label} è di nuovo online")
            ->line("The agent on **{$this->host->label}** ({$this->host->ip}) is sending metrics again.")
            ->line("Last seen: {$lastSeen}");
    }

    public function toTelegram(object $notifiable): string
    {
        $lastSeen = $this->host->last_seen_at
            ? $this->host->last_seen_at->format('Y-m-d H:i:s').' UTC'
            : 'unknown';

        return "🟢 <b>Host tornato online: {$this->host->label}</b>\n\n"
            ."The agent on <b>{$this->host->label}</b> ({$this->host->ip}) is sending metrics again.\n"
            ."Last seen: {$lastSeen}";
    }

    public function toWebhook(object $notifiable): void
    {
        Log::warning('HostRecovered: Webhook channel is not yet implemented.', [
            'host' => $this->host->label,
        ]);
    }

    public function toSlack(object $notifiable): array
    {
        $lastSeen = $this->host->last_seen_at
            ? $this->host->last_seen_at->format('Y-m-d H:i:s').' UTC'
            : 'unknown';

        return [
            'text' => ":large_green_circle: *Host tornato online: {$this->host->label}*\n"
                ."The agent on *{$this->host->label}* ({$this->host->ip}) is sending metrics again.\n"
                ."Last seen: {$lastSeen}",
        ];
    }
}
