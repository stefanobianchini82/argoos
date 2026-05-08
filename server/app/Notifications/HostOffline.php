<?php

namespace App\Notifications;

use App\Models\Host;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class HostOffline extends Notification
{
    public function __construct(
        public readonly Host $host,
    ) {}

    public function via(object $notifiable): array
    {
        // Default to email; extend with per-host channel config when needed.
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lastSeen = $this->host->last_seen_at?->format('Y-m-d H:i:s') . ' UTC' ?? 'never';

        return (new MailMessage)
            ->subject("Host offline: {$this->host->label}")
            ->greeting("Host {$this->host->label} appears to be offline")
            ->line("The agent on **{$this->host->label}** ({$this->host->ip}) has not sent metrics for more than 3 minutes.")
            ->line("Last seen: {$lastSeen}")
            ->line('Log in to the Argoos dashboard to investigate.');
    }

    public function toTelegram(object $notifiable): void
    {
        // Telegram notifications are not yet implemented.
        Log::warning('HostOffline: Telegram channel is not yet implemented.', [
            'host' => $this->host->label,
        ]);
    }

    public function toWebhook(object $notifiable): void
    {
        // Webhook notifications are not yet implemented.
        Log::warning('HostOffline: Webhook channel is not yet implemented.', [
            'host' => $this->host->label,
        ]);
    }
}
