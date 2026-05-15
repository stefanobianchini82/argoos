<?php

namespace App\Services;

use App\Models\AlertEvent;
use App\Models\AlertRule;
use App\Notifications\AlertTriggered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AlertEvaluator
{
    public function evaluate(AlertRule $rule): void
    {
        [$value, $context] = $this->getValueAndContext($rule);

        if ($value === null) {
            return;
        }

        $exceeded = $this->compare($value, $rule->operator, $rule->threshold);

        $openEvent = $rule->alertEvents()
            ->whereNull('resolved_at')
            ->latest('triggered_at')
            ->first();

        if ($exceeded && $openEvent === null) {
            $event = AlertEvent::create([
                'alert_rule_id'   => $rule->id,
                'triggered_at'    => now(),
                'peak_value'      => $value,
                'trigger_context' => $context ?: null,
            ]);

            $rule->update(['last_notified_at' => now()]);

            $this->sendNotification($rule, $event);
        } elseif ($exceeded && $openEvent !== null) {
            if ($value > ($openEvent->peak_value ?? 0)) {
                $openEvent->update(['peak_value' => $value]);
            }
        } elseif (! $exceeded && $openEvent !== null) {
            $openEvent->update(['resolved_at' => now()]);
        }
    }

    private function getValueAndContext(AlertRule $rule): array
    {
        if ($rule->metric === 'disk_usage_percent') {
            return $this->queryDiskUsage($rule);
        }

        if ($rule->metric === 'ram_percent') {
            return [$this->queryRamPercent($rule), []];
        }

        return [$this->queryAverage($rule), []];
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

    private function queryDiskUsage(AlertRule $rule): array
    {
        $since    = now()->subMinutes($rule->duration_minutes);
        $excluded = $rule->excluded_partitions ?? [];

        $query = DB::table('disk_partitions')
            ->select('mount_point', DB::raw('AVG(used * 100.0 / total) as avg_pct'))
            ->where('host_id', $rule->host_id)
            ->where('collected_at', '>=', $since)
            ->where('total', '>', 0)
            ->groupBy('mount_point')
            ->orderByDesc('avg_pct');

        if (! empty($excluded)) {
            $query->whereNotIn('mount_point', $excluded);
        }

        $worst = $query->first();

        if (! $worst) {
            return [null, []];
        }

        $pct = round((float) $worst->avg_pct, 2);

        return [
            $pct,
            ['mount_point' => $worst->mount_point, 'usage_pct' => $pct],
        ];
    }

    private function queryRamPercent(AlertRule $rule): ?float
    {
        $since = now()->subMinutes($rule->duration_minutes);

        $result = DB::selectOne(
            'SELECT AVG(ram_used * 100.0 / ram_total) AS avg_pct
             FROM metrics
             WHERE host_id = ? AND collected_at >= ? AND ram_total > 0',
            [$rule->host_id, $since]
        );

        return $result?->avg_pct !== null ? round((float) $result->avg_pct, 2) : null;
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
        $notifiable = new \Illuminate\Notifications\AnonymousNotifiable;

        if ($rule->channel === 'telegram') {
            if (blank($rule->channel_target)) {
                Log::warning('AlertEvaluator: no channel_target (chat_id) configured for telegram rule.', [
                    'rule_id' => $rule->id,
                ]);
                return;
            }
            $notifiable = $notifiable->route('telegram', $rule->channel_target);
        } else {
            $alertEmail = \App\Models\Setting::get(\App\Models\Setting::ALERT_EMAIL);
            if (blank($alertEmail)) {
                Log::warning('AlertEvaluator: no alert_email setting configured, skipping notification.');
                return;
            }
            $notifiable = $notifiable->route('mail', $alertEmail);
        }

        $notifiable->notify(new AlertTriggered($rule, $event));
    }
}
