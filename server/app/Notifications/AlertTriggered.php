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
            'slack'    => ['slack'],
            default    => ['mail'],
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $host      = $this->rule->host;
        $metric    = $this->rule->metricLabel();
        $operator  = $this->rule->operator;
        $threshold = $this->rule->threshold;
        $peak      = $this->event->peak_value;
        $context   = $this->event->trigger_context;

        $mail = (new MailMessage)
            ->subject("Alert: {$metric} on {$host->label}")
            ->greeting("Alert triggered on {$host->label}")
            ->line("**{$metric}** {$operator} {$threshold} for {$this->rule->duration_minutes} minute(s).");

        if (isset($context['mount_point'])) {
            $mail->line("Partition: **{$context['mount_point']}** at {$context['usage_pct']}%");
        } else {
            $mail->line("Current average value: **{$peak}**");
        }

        return $mail
            ->line("Triggered at: {$this->event->triggered_at->format('Y-m-d H:i:s')} UTC")
            ->line('Log in to the Argoos dashboard to view details.');
    }

    public function toTelegram(object $notifiable): string
    {
        $host      = $this->rule->host;
        $metric    = $this->rule->metricLabel();
        $operator  = $this->rule->operator;
        $threshold = $this->rule->threshold;
        $peak      = $this->event->peak_value;
        $context   = $this->event->trigger_context;
        $at        = $this->event->triggered_at->format('Y-m-d H:i:s');

        $valueLine = isset($context['mount_point'])
            ? "Partition <b>{$context['mount_point']}</b> at {$context['usage_pct']}%"
            : "Current value: <b>{$peak}</b>";

        return "🚨 <b>Alert triggered on {$host->label}</b>\n\n"
            . "<b>{$metric}</b> {$operator} {$threshold} for {$this->rule->duration_minutes} minute(s).\n"
            . "{$valueLine}\n"
            . "Triggered at: {$at} UTC";
    }

    public function toWebhook(object $notifiable): void
    {
        Log::warning('AlertTriggered: Webhook channel is not yet implemented.', [
            'rule_id' => $this->rule->id,
            'host'    => $this->rule->host?->label,
        ]);
    }

    public function toSlack(object $notifiable): array
    {
        $host      = $this->rule->host;
        $metric    = $this->rule->metricLabel();
        $operator  = $this->rule->operator;
        $threshold = $this->rule->threshold;
        $peak      = $this->event->peak_value;
        $context   = $this->event->trigger_context;
        $at        = $this->event->triggered_at->format('Y-m-d H:i:s');

        $valueLine = isset($context['mount_point'])
            ? "Partition *{$context['mount_point']}* at {$context['usage_pct']}%"
            : "Current value: *{$peak}*";

        return [
            'text' => ":rotating_light: *Alert triggered on {$host->label}*\n"
                . "*{$metric}* {$operator} {$threshold} for {$this->rule->duration_minutes} minute(s).\n"
                . "{$valueLine}\n"
                . "Triggered at: {$at} UTC",
        ];
    }
}
