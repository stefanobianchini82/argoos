<?php

namespace App\Livewire;

use App\Models\Setting;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Settings — Argoos')]
class Settings extends Component
{
    public string $alertEmail              = '';
    public string $telegramChatId          = '';
    public bool   $hostOfflineEmailEnabled    = true;
    public bool   $hostOfflineTelegramEnabled = false;

    public bool $saved = false;

    protected array $rules = [
        'alertEmail'                 => ['nullable', 'email', 'max:255'],
        'telegramChatId'             => ['nullable', 'string', 'max:100'],
        'hostOfflineEmailEnabled'    => ['boolean'],
        'hostOfflineTelegramEnabled' => ['boolean'],
    ];

    protected array $messages = [
        'alertEmail.email' => 'The alert email must be a valid email address.',
    ];

    public function mount(): void
    {
        $this->alertEmail              = Setting::get(Setting::ALERT_EMAIL, '');
        $this->telegramChatId          = Setting::get(Setting::TELEGRAM_CHAT_ID, '');
        $this->hostOfflineEmailEnabled    = filter_var(
            Setting::get(Setting::HOST_OFFLINE_EMAIL_ENABLED, true),
            FILTER_VALIDATE_BOOLEAN
        );
        $this->hostOfflineTelegramEnabled = filter_var(
            Setting::get(Setting::HOST_OFFLINE_TELEGRAM_ENABLED, false),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    public function save(): void
    {
        $this->validate();

        Setting::set(Setting::ALERT_EMAIL,                   $this->alertEmail);
        Setting::set(Setting::TELEGRAM_CHAT_ID,              $this->telegramChatId);
        Setting::set(Setting::HOST_OFFLINE_EMAIL_ENABLED,    $this->hostOfflineEmailEnabled ? '1' : '0');
        Setting::set(Setting::HOST_OFFLINE_TELEGRAM_ENABLED, $this->hostOfflineTelegramEnabled ? '1' : '0');

        $this->saved = true;
    }

    public function updatedAlertEmail(): void
    {
        $this->saved = false;
    }

    public function updatedTelegramChatId(): void
    {
        $this->saved = false;
    }

    public function render()
    {
        return view('livewire.settings')->layout('layouts.app');
    }
}
