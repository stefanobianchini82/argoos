<?php

namespace App\Livewire;

use App\Models\AlertRule;
use App\Models\Host;
use App\Models\Setting;
use Livewire\Component;

class AlertRuleForm extends Component
{
    public Host $host;
    public ?AlertRule $alertRule = null;

    public string $metric               = '';
    public string $operator             = '>';
    public string $threshold            = '';
    public string $excludedPartitions   = '';
    public int    $durationMinutes      = 5;
    public string $channel              = 'email';
    public string $channelTarget        = '';
    public bool   $isActive             = true;

    public function mount(): void
    {
        if ($this->alertRule !== null) {
            $this->metric               = $this->alertRule->metric;
            $this->operator             = $this->alertRule->operator;
            $this->threshold            = (string) $this->alertRule->threshold;
            $this->excludedPartitions   = implode(', ', $this->alertRule->excluded_partitions ?? []);
            $this->durationMinutes      = $this->alertRule->duration_minutes;
            $this->channel              = $this->alertRule->channel;
            $this->channelTarget        = $this->alertRule->channel_target;
            $this->isActive             = $this->alertRule->is_active;
        }
    }

    public function fillFromSettings(): void
    {
        $value = match ($this->channel) {
            'email'    => Setting::get(Setting::ALERT_EMAIL),
            'telegram' => Setting::get(Setting::TELEGRAM_CHAT_ID),
            'slack'    => Setting::get(Setting::SLACK_WEBHOOK_URL),
            default    => null,
        };

        if ($value) {
            $this->channelTarget = $value;
        }
    }

    public function save(): void
    {
        $this->validate([
            'metric'          => ['required', 'in:' . implode(',', array_keys(AlertRule::METRICS))],
            'operator'        => ['required', 'in:' . implode(',', AlertRule::OPERATORS)],
            'threshold'       => ['required', 'numeric'],
            'durationMinutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'channel'         => ['required', 'in:' . implode(',', AlertRule::CHANNELS)],
            'channelTarget'   => ['required', 'string', 'max:255'],
        ]);

        $data = [
            'metric'               => $this->metric,
            'operator'             => $this->operator,
            'threshold'            => (float) $this->threshold,
            'excluded_partitions'  => $this->metric === 'disk_usage_percent'
                ? array_values(array_filter(array_map('trim', explode(',', $this->excludedPartitions))))
                : null,
            'duration_minutes'     => $this->durationMinutes,
            'channel'              => $this->channel,
            'channel_target'       => $this->channelTarget,
            'is_active'            => $this->isActive,
        ];

        if ($this->alertRule !== null) {
            $this->alertRule->update($data);
        } else {
            $this->host->alertRules()->create($data);
        }

        $this->redirect(route('hosts.alerts', $this->host), navigate: true);
    }

    public function render()
    {
        $title = $this->alertRule ? 'Edit Alert Rule' : 'New Alert Rule';

        $settingValue = match ($this->channel) {
            'email'    => Setting::get(Setting::ALERT_EMAIL),
            'telegram' => Setting::get(Setting::TELEGRAM_CHAT_ID),
            'slack'    => Setting::get(Setting::SLACK_WEBHOOK_URL),
            default    => null,
        };

        return view('livewire.alert-rule-form', [
            'metrics'      => AlertRule::METRICS,
            'operators'    => AlertRule::OPERATORS,
            'channels'     => AlertRule::CHANNELS,
            'settingValue' => $settingValue,
        ])->layout('layouts.app')->title("{$title} — {$this->host->label} — Argoos");
    }
}
