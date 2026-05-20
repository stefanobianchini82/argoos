<?php

namespace App\Notifications;

use App\Models\HttpCheck;
use App\Models\HttpCheckEvent;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class HttpCheckRecovered extends Notification
{
    public function __construct(
        public readonly HttpCheck      $check,
        public readonly HttpCheckEvent $event,
    ) {}

    public function via(object $notifiable): array
    {
        return match ($this->check->channel) {
            'telegram' => ['telegram'],
            'slack'    => ['slack'],
            'webhook'  => ['webhook'],
            default    => ['mail'],
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $downAt      = $this->event->triggered_at->format('Y-m-d H:i:s');
        $recoveredAt = $this->event->resolved_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s');
        $responseMs  = $this->event->response_ms !== null ? " ({$this->event->response_ms} ms)" : '';

        return (new MailMessage)
            ->subject("RECOVERED: {$this->check->label} on {$this->check->host->label}")
            ->greeting("Endpoint recovered on {$this->check->host->label}")
            ->line("**{$this->check->label}** ({$this->check->url}) is back online{$responseMs}.")
            ->line("Was down since: {$downAt} UTC")
            ->line("Recovered at: {$recoveredAt} UTC");
    }

    public function toTelegram(object $notifiable): string
    {
        $downAt      = $this->event->triggered_at->format('Y-m-d H:i:s');
        $recoveredAt = $this->event->resolved_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s');
        $responseMs  = $this->event->response_ms !== null ? " ({$this->event->response_ms} ms)" : '';

        return "🟢 <b>RECOVERED: {$this->check->label}</b> — {$this->check->host->label}\n\n"
            . "<b>URL:</b> {$this->check->url}\n"
            . "Back online{$responseMs}. Was down since: {$downAt} UTC\n"
            . "Recovered at: {$recoveredAt} UTC";
    }

    public function toSlack(object $notifiable): array
    {
        $downAt      = $this->event->triggered_at->format('Y-m-d H:i:s');
        $recoveredAt = $this->event->resolved_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s');
        $responseMs  = $this->event->response_ms !== null ? " ({$this->event->response_ms} ms)" : '';

        return [
            'text' => ":large_green_circle: *RECOVERED: {$this->check->label}* — {$this->check->host->label}\n"
                . "*URL:* {$this->check->url}\n"
                . "Back online{$responseMs}. Was down since: {$downAt} UTC\n"
                . "Recovered at: {$recoveredAt} UTC",
        ];
    }

    public function toWebhook(object $notifiable): void
    {
        Log::warning('HttpCheckRecovered: Webhook channel is not yet implemented.', ['check_id' => $this->check->id]);
    }
}
