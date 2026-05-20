<?php

namespace App\Notifications;

use App\Models\HttpCheck;
use App\Models\HttpCheckEvent;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class HttpCheckDown extends Notification
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
        $reason = $this->reasonText($this->event->context ?? []);

        return (new MailMessage)
            ->subject("DOWN: {$this->check->label} on {$this->check->host->label}")
            ->greeting("Endpoint down on {$this->check->host->label}")
            ->line("**{$this->check->label}** ({$this->check->url}) is unreachable.")
            ->line($reason)
            ->line("Triggered at: {$this->event->triggered_at->format('Y-m-d H:i:s')} UTC")
            ->line('Log in to the Argoos dashboard to view details.');
    }

    public function toTelegram(object $notifiable): string
    {
        $reason = $this->reasonText($this->event->context ?? []);

        return "🔴 <b>DOWN: {$this->check->label}</b> — {$this->check->host->label}\n\n"
            . "<b>URL:</b> {$this->check->url}\n"
            . "{$reason}\n"
            . "At: {$this->event->triggered_at->format('Y-m-d H:i:s')} UTC";
    }

    public function toSlack(object $notifiable): array
    {
        $reason = $this->reasonText($this->event->context ?? []);

        return [
            'text' => ":red_circle: *DOWN: {$this->check->label}* — {$this->check->host->label}\n"
                . "*URL:* {$this->check->url}\n"
                . "{$reason}\n"
                . "At: {$this->event->triggered_at->format('Y-m-d H:i:s')} UTC",
        ];
    }

    public function toWebhook(object $notifiable): void
    {
        Log::warning('HttpCheckDown: Webhook channel is not yet implemented.', ['check_id' => $this->check->id]);
    }

    private function reasonText(array $context): string
    {
        return match ($context['reason'] ?? '') {
            'connection_error'  => 'Connection error: ' . ($context['error'] ?? 'unknown'),
            'unexpected_status' => "Got HTTP {$context['got_status']}, expected {$context['expected']}",
            'keyword_not_found' => 'Response did not contain expected keyword: ' . ($context['keyword'] ?? ''),
            'unexpected_error'  => 'Unexpected error: ' . ($context['error'] ?? 'unknown'),
            default             => 'Unknown failure',
        };
    }
}
