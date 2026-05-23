<?php

namespace App\Livewire;

use App\Models\AlertRule;
use App\Models\Host;
use App\Models\HttpCheck;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Settings — Argoos')]
class Settings extends Component
{
    use WithFileUploads;

    public string $alertEmail              = '';
    public string $telegramChatId          = '';
    public string $slackWebhookUrl         = '';
    public bool   $hostOfflineEmailEnabled    = true;
    public bool   $hostOfflineTelegramEnabled = false;
    public bool   $hostOfflineSlackEnabled    = false;
    public int    $hostOfflineOfflineMinutes  = 3;
    public int    $hostOfflineRenotifyMinutes = 10;

    public bool   $saved = false;
    public bool   $opcacheResetDone = false;
    public bool   $telegramBotConfigured = false;
    public string $telegramTestStatus  = '';
    public string $telegramTestMessage = '';
    public string $slackTestStatus     = '';
    public string $slackTestMessage    = '';

    public mixed  $importFile   = null;
    public string $importError  = '';
    public ?array $importResult = null;

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

    public function resetOpcache(): void
    {
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        $this->opcacheResetDone = true;
    }

    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $data = [
            'version'     => 1,
            'exported_at' => now()->toIso8601String(),
            'settings'    => Setting::all()->pluck('value', 'key')->toArray(),
            'hosts'       => Host::with(['alertRules', 'httpChecks'])->get()->map(fn (Host $h) => [
                'label'          => $h->label,
                'description'    => $h->description,
                'ip'             => $h->ip,
                'api_key'        => $h->api_key,
                'api_key_prefix' => $h->api_key_prefix,
                'alert_rules'    => $h->alertRules->map(fn (AlertRule $r) => [
                    'metric'              => $r->metric,
                    'operator'            => $r->operator,
                    'threshold'           => $r->threshold,
                    'excluded_partitions' => $r->excluded_partitions,
                    'duration_minutes'    => $r->duration_minutes,
                    'channel'             => $r->channel,
                    'channel_target'      => $r->channel_target,
                    'is_active'           => $r->is_active,
                ])->values()->all(),
                'http_checks'    => $h->httpChecks->map(fn (HttpCheck $c) => [
                    'label'                => $c->label,
                    'url'                  => $c->url,
                    'method'               => $c->method,
                    'timeout_seconds'      => $c->timeout_seconds,
                    'expected_status_code' => $c->expected_status_code,
                    'keyword_match'        => $c->keyword_match,
                    'channel'              => $c->channel,
                    'channel_target'       => $c->channel_target,
                    'is_active'            => $c->is_active,
                ])->values()->all(),
            ])->values()->all(),
        ];

        $filename = 'argoos-config-' . now()->format('Ymd-His') . '.json';

        return response()->streamDownload(
            fn () => print json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            $filename,
            ['Content-Type' => 'application/json'],
        );
    }

    public function import(): void
    {
        $this->importError  = '';
        $this->importResult = null;

        $this->validate(['importFile' => ['required', 'file', 'mimes:json,txt', 'max:2048']]);

        $content = file_get_contents($this->importFile->getRealPath());
        $data    = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! isset($data['version']) || $data['version'] !== 1) {
            $this->importError = 'Invalid file format or unsupported version.';
            $this->importFile  = null;
            return;
        }

        $newHosts       = 0;
        $updatedHosts   = 0;
        $importedRules  = 0;
        $importedChecks = 0;

        try {
        DB::transaction(function () use ($data, &$newHosts, &$updatedHosts, &$importedRules, &$importedChecks): void {
            foreach ($data['settings'] ?? [] as $key => $value) {
                Setting::set($key, $value);
            }

            foreach ($data['hosts'] ?? [] as $hostData) {
                $fields = [
                    'description'    => $hostData['description'] ?? null,
                    'ip'             => $hostData['ip'] ?? null,
                    'api_key'        => $hostData['api_key'],
                    'api_key_prefix' => $hostData['api_key_prefix'],
                ];

                $host = Host::where('label', $hostData['label'])->first();

                if ($host) {
                    $host->update($fields);
                    $updatedHosts++;
                } else {
                    $host = Host::create(array_merge(['label' => $hostData['label']], $fields));
                    $newHosts++;
                }

                foreach ($hostData['alert_rules'] ?? [] as $r) {
                    $duplicate = $host->alertRules()
                        ->where('metric', $r['metric'])
                        ->where('operator', $r['operator'])
                        ->where('threshold', $r['threshold'])
                        ->where('duration_minutes', $r['duration_minutes'])
                        ->where('channel', $r['channel'])
                        ->where('channel_target', $r['channel_target'])
                        ->exists();

                    if (! $duplicate) {
                        $host->alertRules()->create([
                            'metric'              => $r['metric'],
                            'operator'            => $r['operator'],
                            'threshold'           => $r['threshold'],
                            'excluded_partitions' => $r['excluded_partitions'] ?? null,
                            'duration_minutes'    => $r['duration_minutes'],
                            'channel'             => $r['channel'],
                            'channel_target'      => $r['channel_target'],
                            'is_active'           => $r['is_active'] ?? true,
                        ]);
                        $importedRules++;
                    }
                }

                foreach ($hostData['http_checks'] ?? [] as $c) {
                    $duplicate = $host->httpChecks()
                        ->where('url', $c['url'])
                        ->where('method', $c['method'])
                        ->where('expected_status_code', $c['expected_status_code'])
                        ->exists();

                    if (! $duplicate) {
                        $host->httpChecks()->create([
                            'label'                => $c['label'],
                            'url'                  => $c['url'],
                            'method'               => $c['method'],
                            'timeout_seconds'      => $c['timeout_seconds'] ?? 10,
                            'expected_status_code' => $c['expected_status_code'],
                            'keyword_match'        => $c['keyword_match'] ?? null,
                            'channel'              => $c['channel'],
                            'channel_target'       => $c['channel_target'],
                            'is_active'            => $c['is_active'] ?? true,
                        ]);
                        $importedChecks++;
                    }
                }
            }
        });
        } catch (\Throwable $e) {
            $this->importError = 'Import failed: ' . $e->getMessage();
            $this->importFile  = null;
            return;
        }

        $this->mount();

        $this->importFile   = null;
        $this->importResult = [
            'new_hosts'     => $newHosts,
            'updated_hosts' => $updatedHosts,
            'rules'         => $importedRules,
            'checks'        => $importedChecks,
        ];
    }

    public function render()
    {
        return view('livewire.settings')->layout('layouts.app');
    }
}
