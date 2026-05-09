<?php

use App\Livewire\Settings;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

describe('Settings — mount', function () {
    it('loads saved settings into component properties', function () {
        Setting::set(Setting::ALERT_EMAIL, 'ops@example.com');
        Setting::set(Setting::TELEGRAM_CHAT_ID, '12345678');
        Setting::set(Setting::SLACK_WEBHOOK_URL, 'https://hooks.slack.com/test');
        Setting::set(Setting::HOST_OFFLINE_OFFLINE_MINUTES, '5');
        Setting::set(Setting::HOST_OFFLINE_RENOTIFY_MINUTES, '20');

        Livewire::test(Settings::class)
            ->assertSet('alertEmail', 'ops@example.com')
            ->assertSet('telegramChatId', '12345678')
            ->assertSet('slackWebhookUrl', 'https://hooks.slack.com/test')
            ->assertSet('hostOfflineOfflineMinutes', 5)
            ->assertSet('hostOfflineRenotifyMinutes', 20);
    });
});

describe('Settings — save', function () {
    it('persists all settings and sets saved to true', function () {
        Livewire::test(Settings::class)
            ->set('alertEmail', 'alerts@example.com')
            ->set('telegramChatId', '99999')
            ->set('hostOfflineOfflineMinutes', 5)
            ->set('hostOfflineRenotifyMinutes', 15)
            ->call('save')
            ->assertSet('saved', true);

        expect(Setting::get(Setting::ALERT_EMAIL))->toBe('alerts@example.com');
        expect(Setting::get(Setting::TELEGRAM_CHAT_ID))->toBe('99999');
        expect(Setting::get(Setting::HOST_OFFLINE_OFFLINE_MINUTES))->toBe('5');
        expect(Setting::get(Setting::HOST_OFFLINE_RENOTIFY_MINUTES))->toBe('15');
    });

    it('returns a validation error for an invalid email', function () {
        Livewire::test(Settings::class)
            ->set('alertEmail', 'not-an-email')
            ->call('save')
            ->assertHasErrors(['alertEmail' => 'email']);
    });

    it('accepts an empty email (nullable)', function () {
        Livewire::test(Settings::class)
            ->set('alertEmail', '')
            ->call('save')
            ->assertHasNoErrors(['alertEmail']);
    });

    it('rejects offline minutes below 1', function () {
        Livewire::test(Settings::class)
            ->set('hostOfflineOfflineMinutes', 0)
            ->call('save')
            ->assertHasErrors(['hostOfflineOfflineMinutes']);
    });
});

describe('Settings — field updates reset saved flag', function () {
    it('resets saved to false when alertEmail is updated', function () {
        Livewire::test(Settings::class)
            ->set('alertEmail', 'a@b.com')
            ->call('save')
            ->set('alertEmail', 'c@d.com')
            ->assertSet('saved', false);
    });
});

describe('Settings — testTelegramNotification', function () {
    it('returns error when bot token is not configured', function () {
        config(['services.telegram.bot_token' => '']);

        Livewire::test(Settings::class)
            ->set('telegramChatId', '12345')
            ->call('testTelegramNotification')
            ->assertSet('telegramTestStatus', 'error')
            ->assertSet('telegramTestMessage', 'Bot token not configured.');
    });

    it('returns error when chat id is empty', function () {
        config(['services.telegram.bot_token' => 'test-token']);

        Livewire::test(Settings::class)
            ->set('telegramChatId', '')
            ->call('testTelegramNotification')
            ->assertSet('telegramTestStatus', 'error')
            ->assertSet('telegramTestMessage', 'Enter a Chat ID first.');
    });

    it('returns success when telegram API responds OK', function () {
        config(['services.telegram.bot_token' => 'test-token']);
        Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        Livewire::test(Settings::class)
            ->set('telegramChatId', '12345')
            ->call('testTelegramNotification')
            ->assertSet('telegramTestStatus', 'success');
    });

    it('returns error when telegram API responds with failure', function () {
        config(['services.telegram.bot_token' => 'test-token']);
        Http::fake(['https://api.telegram.org/*' => Http::response(['description' => 'Bad Request'], 400)]);

        Livewire::test(Settings::class)
            ->set('telegramChatId', '12345')
            ->call('testTelegramNotification')
            ->assertSet('telegramTestStatus', 'error')
            ->assertSet('telegramTestMessage', 'Bad Request');
    });
});

describe('Settings — testSlackNotification', function () {
    it('returns error when webhook url is empty', function () {
        Livewire::test(Settings::class)
            ->set('slackWebhookUrl', '')
            ->call('testSlackNotification')
            ->assertSet('slackTestStatus', 'error')
            ->assertSet('slackTestMessage', 'Enter a Webhook URL first.');
    });

    it('returns success when Slack webhook responds OK', function () {
        Http::fake(['https://hooks.slack.com/*' => Http::response('ok', 200)]);

        Livewire::test(Settings::class)
            ->set('slackWebhookUrl', 'https://hooks.slack.com/test')
            ->call('testSlackNotification')
            ->assertSet('slackTestStatus', 'success');
    });

    it('returns error when Slack webhook responds with failure', function () {
        Http::fake(['https://hooks.slack.com/*' => Http::response('invalid_payload', 400)]);

        Livewire::test(Settings::class)
            ->set('slackWebhookUrl', 'https://hooks.slack.com/test')
            ->call('testSlackNotification')
            ->assertSet('slackTestStatus', 'error');
    });
});
