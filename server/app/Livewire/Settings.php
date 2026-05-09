<?php

namespace App\Livewire;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Settings — Argoos')]
class Settings extends Component
{
    public string $alertEmail              = '';
    public string $telegramChatId          = '';
    public string $slackWebhookUrl         = '';
    public bool   $hostOfflineEmailEnabled    = true;
    public bool   $hostOfflineTelegramEnabled = false;
    public bool   $hostOfflineSlackEnabled    = false;
    public int    $hostOfflineOfflineMinutes  = 3;
    public int    $hostOfflineRenotifyMinutes = 10;

    public bool   $saved = false;
    public bool   $telegramBotConfigured = false;
    public string $telegramTestStatus  = '';
    public string $telegramTestMessage = '';
    public string $slackTestStatus     = '';
    public string $slackTestMessage    = '';

    protected array $rules = [
        'alertEmail'                 => ['nullable', 'email', 'max:255'],
        'telegramChatId'             => ['nullable', 'string', 'max:100'],
        'slackWebhookUrl'            => ['nullable', 'url', 'max:500'],
        'hostOfflineEmailEnabled'    => ['boolean'],
        'hostOfflineTelegramEnabled' => ['boolean'],
        'hostOfflineSlackEnabled'    => ['boolean'],
        'hostOfflineOfflineMinutes'  => ['required', 'integer', 'min:1', 'max:1440'],
        'hostOfflineRenotifyMinutes' => ['required', 'integer', 'min:1', 'max:1440'],
    ];

    protected array $messages = [
        'alertEmail.email' => 'The alert email must be a valid email address.',
    ];

    public function mount(): void
    {
        $this->alertEmail              = (string) Setting::get(Setting::ALERT_EMAIL, '');
        $this->telegramChatId          = (string) Setting::get(Setting::TELEGRAM_CHAT_ID, '');
        $this->slackWebhookUrl         = (string) Setting::get(Setting::SLACK_WEBHOOK_URL, '');
        $this->hostOfflineEmailEnabled    = filter_var(
            Setting::get(Setting::HOST_OFFLINE_EMAIL_ENABLED, true),
            FILTER_VALIDATE_BOOLEAN
        );
        $this->hostOfflineTelegramEnabled = filter_var(
            Setting::get(Setting::HOST_OFFLINE_TELEGRAM_ENABLED, false),
            FILTER_VALIDATE_BOOLEAN
        );
        $this->hostOfflineSlackEnabled = filter_var(
            Setting::get(Setting::HOST_OFFLINE_SLACK_ENABLED, false),
            FILTER_VALIDATE_BOOLEAN
        );
        $this->hostOfflineOfflineMinutes  = max(1, (int) Setting::get(Setting::HOST_OFFLINE_OFFLINE_MINUTES, 3));
        $this->hostOfflineRenotifyMinutes = max(1, (int) Setting::get(Setting::HOST_OFFLINE_RENOTIFY_MINUTES, 10));

        $this->telegramBotConfigured = (bool) config('services.telegram.bot_token');
    }

    public function save(): void
    {
        $this->validate();

        Setting::set(Setting::ALERT_EMAIL,                   $this->alertEmail);
        Setting::set(Setting::TELEGRAM_CHAT_ID,              $this->telegramChatId);
        Setting::set(Setting::SLACK_WEBHOOK_URL,             $this->slackWebhookUrl);
        Setting::set(Setting::HOST_OFFLINE_EMAIL_ENABLED,    $this->hostOfflineEmailEnabled ? '1' : '0');
        Setting::set(Setting::HOST_OFFLINE_TELEGRAM_ENABLED, $this->hostOfflineTelegramEnabled ? '1' : '0');
        Setting::set(Setting::HOST_OFFLINE_SLACK_ENABLED,    $this->hostOfflineSlackEnabled ? '1' : '0');
        Setting::set(Setting::HOST_OFFLINE_OFFLINE_MINUTES,  (string) $this->hostOfflineOfflineMinutes);
        Setting::set(Setting::HOST_OFFLINE_RENOTIFY_MINUTES, (string) $this->hostOfflineRenotifyMinutes);

        $this->saved = true;
    }

    public function updatedAlertEmail(): void
    {
        $this->saved = false;
    }

    public function updatedSlackWebhookUrl(): void
    {
        $this->saved = false;
        $this->slackTestStatus  = '';
        $this->slackTestMessage = '';
    }

    public function updatedTelegramChatId(): void
    {
        $this->saved = false;
        $this->telegramTestStatus  = '';
        $this->telegramTestMessage = '';
    }

    public function testTelegramNotification(): void
    {
        $this->telegramTestStatus  = '';
        $this->telegramTestMessage = '';

        $token = config('services.telegram.bot_token');
        if (empty($token)) {
            $this->telegramTestStatus  = 'error';
            $this->telegramTestMessage = 'Bot token not configured.';
            return;
        }
        if (empty($this->telegramChatId)) {
            $this->telegramTestStatus  = 'error';
            $this->telegramTestMessage = 'Enter a Chat ID first.';
            return;
        }

        $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id'    => $this->telegramChatId,
            'text'       => "✅ <b>Argoos test notification</b>\n\nTelegram notifications are working correctly.",
            'parse_mode' => 'HTML',
        ]);

        if ($response->successful()) {
            $this->telegramTestStatus  = 'success';
            $this->telegramTestMessage = 'Test message sent!';
        } else {
            $this->telegramTestStatus  = 'error';
            $this->telegramTestMessage = $response->json('description') ?? 'Request failed.';
        }
    }

    public function testSlackNotification(): void
    {
        $this->slackTestStatus  = '';
        $this->slackTestMessage = '';

        if (empty($this->slackWebhookUrl)) {
            $this->slackTestStatus  = 'error';
            $this->slackTestMessage = 'Enter a Webhook URL first.';
            return;
        }

        $response = Http::post($this->slackWebhookUrl, [
            'text' => "✅ *Argoos test notification*\n\nSlack notifications are working correctly.",
        ]);

        if ($response->successful()) {
            $this->slackTestStatus  = 'success';
            $this->slackTestMessage = 'Test message sent!';
        } else {
            $this->slackTestStatus  = 'error';
            $this->slackTestMessage = $response->body() ?: 'Request failed.';
        }
    }

    public function render()
    {
        return view('livewire.settings')->layout('layouts.app');
    }
}
