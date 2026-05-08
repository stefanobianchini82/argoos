<?php

namespace App\Notifications;

use App\Models\AlertEvent;
use App\Models\AlertRule;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class AlertTriggered extends Notification
{
    public function __construct(
        public readonly AlertRule  $rule,
        public readonly AlertEvent $event,
    ) {}

    public function via(object $notifiable): array
    {
        return match ($this->rule->channel) {
            'email'    => ['mail'],
            'telegram' => ['telegram'],
            'webhook'  => ['webhook'],
            default    => ['mail'],
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $host     = $this->rule->host;
        $metric   = $this->rule->metricLabel();
        $operator = $this->rule->operator;
        $threshold = $this->rule->threshold;
        $peak     = $this->event->peak_value;

        return (new MailMessage)
            ->subject("Alert: {$metric} on {$host->label}")
            ->greeting("Alert triggered on {$host->label}")
            ->line("**{$metric}** {$operator} {$threshold} for {$this->rule->duration_minutes} minute(s).")
            ->line("Current average value: **{$peak}**")
            ->line("Triggered at: {$this->event->triggered_at->format('Y-m-d H:i:s')} UTC")
            ->line('Log in to the Argoos dashboard to view details.');
    }

    public function toTelegram(object $notifiable): void
    {
        // Telegram notifications are not yet implemented.
        Log::warning('AlertTriggered: Telegram channel is not yet implemented.', [
            'rule_id' => $this->rule->id,
            'host'    => $this->rule->host?->label,
        ]);
    }

    public function toWebhook(object $notifiable): void
    {
        // Webhook notifications are not yet implemented.
        Log::warning('AlertTriggered: Webhook channel is not yet implemented.', [
            'rule_id' => $this->rule->id,
            'host'    => $this->rule->host?->label,
        ]);
    }
}
