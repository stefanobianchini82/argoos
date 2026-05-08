<?php

namespace App\Services;

use App\Models\AlertEvent;
use App\Models\AlertRule;
use App\Notifications\AlertTriggered;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AlertEvaluator
{
    public function evaluate(AlertRule $rule): void
    {
        $avgValue = $this->queryAverage($rule);

        if ($avgValue === null) {
            return;
        }

        $exceeded = $this->compare($avgValue, $rule->operator, $rule->threshold);

        $openEvent = $rule->alertEvents()
            ->whereNull('resolved_at')
            ->latest('triggered_at')
            ->first();

        if ($exceeded && $openEvent === null) {
            $event = AlertEvent::create([
                'alert_rule_id' => $rule->id,
                'triggered_at'  => now(),
                'peak_value'    => $avgValue,
            ]);

            $rule->update(['last_notified_at' => now()]);

            $this->sendNotification($rule, $event);
        } elseif ($exceeded && $openEvent !== null) {
            if ($avgValue > ($openEvent->peak_value ?? 0)) {
                $openEvent->update(['peak_value' => $avgValue]);
            }
        } elseif (! $exceeded && $openEvent !== null) {
            $openEvent->update(['resolved_at' => now()]);
        }
    }

    private function queryAverage(AlertRule $rule): ?float
    {
        $since = now()->subMinutes($rule->duration_minutes);

        $column = DB::connection()->getQueryGrammar()->wrap($rule->metric);

        $result = DB::selectOne(
            "SELECT AVG({$column}) AS avg_value FROM metrics WHERE host_id = ? AND collected_at >= ?",
            [$rule->host_id, $since]
        );

        return $result?->avg_value;
    }

    private function compare(float $value, string $operator, float $threshold): bool
    {
        return match ($operator) {
            '>'  => $value > $threshold,
            '<'  => $value < $threshold,
            '>=' => $value >= $threshold,
            '<=' => $value <= $threshold,
            default => false,
        };
    }

    private function sendNotification(AlertRule $rule, AlertEvent $event): void
    {
        $alertEmail = config('dashboard.alert_email');

        if (blank($alertEmail)) {
            Log::warning('AlertEvaluator: no DASHBOARD_ALERT_EMAIL configured, skipping notification.');
            return;
        }

        $notifiable = (new \Illuminate\Notifications\AnonymousNotifiable)
            ->route('mail', $alertEmail)
            ->route('telegram', $alertEmail)
            ->route('webhook', $alertEmail);

        $notifiable->notify(new AlertTriggered($rule, $event));
    }
}
