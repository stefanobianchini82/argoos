<?php

namespace App\Livewire;

use App\Models\Host;
use App\Models\HttpCheck;
use App\Models\Setting;
use Livewire\Component;

class HttpCheckForm extends Component
{
    public Host $host;
    public ?HttpCheck $httpCheck = null;

    public string $label               = '';
    public string $url                 = '';
    public string $method              = 'GET';
    public int    $timeoutSeconds      = 10;
    public int    $expectedStatusCode  = 200;
    public string $keywordMatch        = '';
    public string $channel             = 'email';
    public string $channelTarget       = '';
    public bool   $isActive            = true;

    public function mount(): void
    {
        if ($this->httpCheck !== null) {
            $this->label              = $this->httpCheck->label;
            $this->url                = $this->httpCheck->url;
            $this->method             = $this->httpCheck->method;
            $this->timeoutSeconds     = $this->httpCheck->timeout_seconds;
            $this->expectedStatusCode = $this->httpCheck->expected_status_code;
            $this->keywordMatch       = $this->httpCheck->keyword_match ?? '';
            $this->channel            = $this->httpCheck->channel;
            $this->channelTarget      = $this->httpCheck->channel_target;
            $this->isActive           = $this->httpCheck->is_active;
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
            'label'              => ['required', 'string', 'max:100'],
            'url'                => ['required', 'url', 'max:2048'],
            'method'             => ['required', 'in:' . implode(',', HttpCheck::METHODS)],
            'timeoutSeconds'     => ['required', 'integer', 'min:1', 'max:60'],
            'expectedStatusCode' => ['required', 'integer', 'min:100', 'max:599'],
            'keywordMatch'       => ['nullable', 'string', 'max:255'],
            'channel'            => ['required', 'in:' . implode(',', HttpCheck::CHANNELS)],
            'channelTarget'      => ['required', 'string', 'max:255'],
        ]);

        $data = [
            'label'                => $this->label,
            'url'                  => $this->url,
            'method'               => $this->method,
            'timeout_seconds'      => $this->timeoutSeconds,
            'expected_status_code' => $this->expectedStatusCode,
            'keyword_match'        => filled($this->keywordMatch) ? $this->keywordMatch : null,
            'channel'              => $this->channel,
            'channel_target'       => $this->channelTarget,
            'is_active'            => $this->isActive,
        ];

        if ($this->httpCheck !== null) {
            $this->httpCheck->update($data);
        } else {
            $this->host->httpChecks()->create($data);
        }

        $this->redirect(route('hosts.checks', $this->host), navigate: true);
    }

    public function render()
    {
        $title = $this->httpCheck ? 'Edit HTTP Check' : 'New HTTP Check';

        $settingValue = match ($this->channel) {
            'email'    => Setting::get(Setting::ALERT_EMAIL),
            'telegram' => Setting::get(Setting::TELEGRAM_CHAT_ID),
            'slack'    => Setting::get(Setting::SLACK_WEBHOOK_URL),
            default    => null,
        };

        return view('livewire.http-check-form', [
            'methods'      => HttpCheck::METHODS,
            'channels'     => HttpCheck::CHANNELS,
            'settingValue' => $settingValue,
        ])->layout('layouts.app')->title("{$title} — {$this->host->label} — Argoos");
    }
}
