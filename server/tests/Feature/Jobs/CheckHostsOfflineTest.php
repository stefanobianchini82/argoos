<?php

use App\Jobs\CheckHostsOffline;
use App\Models\Host;
use App\Models\Setting;
use App\Notifications\HostOffline;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

describe('CheckHostsOffline — no channels configured', function () {
    it('sends no notifications when all channels are disabled', function () {
        Setting::set(Setting::HOST_OFFLINE_EMAIL_ENABLED, '0');
        Setting::set(Setting::HOST_OFFLINE_TELEGRAM_ENABLED, '0');
        Setting::set(Setting::HOST_OFFLINE_SLACK_ENABLED, '0');

        Host::factory()->offline(10)->create();

        (new CheckHostsOffline)->handle();

        Notification::assertNothingSent();
    });

    it('sends no notifications when email is enabled but no address is set', function () {
        Setting::set(Setting::HOST_OFFLINE_EMAIL_ENABLED, '1');
        Setting::set(Setting::ALERT_EMAIL, '');
        Setting::set(Setting::HOST_OFFLINE_TELEGRAM_ENABLED, '0');
        Setting::set(Setting::HOST_OFFLINE_SLACK_ENABLED, '0');

        Host::factory()->offline(10)->create();

        (new CheckHostsOffline)->handle();

        Notification::assertNothingSent();
    });
});

describe('CheckHostsOffline — online hosts', function () {
    it('does not notify hosts that are still online', function () {
        Setting::set(Setting::HOST_OFFLINE_EMAIL_ENABLED, '1');
        Setting::set(Setting::ALERT_EMAIL, 'ops@example.com');
        Setting::set(Setting::HOST_OFFLINE_OFFLINE_MINUTES, '3');

        Host::factory()->online()->create();

        (new CheckHostsOffline)->handle();

        Notification::assertNothingSent();
    });
});

describe('CheckHostsOffline — offline detection', function () {
    it('notifies and updates last_offline_notified_at for a newly offline host', function () {
        Setting::set(Setting::HOST_OFFLINE_EMAIL_ENABLED, '1');
        Setting::set(Setting::ALERT_EMAIL, 'ops@example.com');
        Setting::set(Setting::HOST_OFFLINE_OFFLINE_MINUTES, '3');
        Setting::set(Setting::HOST_OFFLINE_RENOTIFY_MINUTES, '10');
        Setting::set(Setting::HOST_OFFLINE_TELEGRAM_ENABLED, '0');
        Setting::set(Setting::HOST_OFFLINE_SLACK_ENABLED, '0');

        $host = Host::factory()->offline(10)->create(['last_offline_notified_at' => null]);

        (new CheckHostsOffline)->handle();

        Notification::assertSentOnDemand(HostOffline::class);
        expect($host->fresh()->last_offline_notified_at)->not->toBeNull();
    });

    it('does not re-notify within the renotify window', function () {
        Setting::set(Setting::HOST_OFFLINE_EMAIL_ENABLED, '1');
        Setting::set(Setting::ALERT_EMAIL, 'ops@example.com');
        Setting::set(Setting::HOST_OFFLINE_OFFLINE_MINUTES, '3');
        Setting::set(Setting::HOST_OFFLINE_RENOTIFY_MINUTES, '10');
        Setting::set(Setting::HOST_OFFLINE_TELEGRAM_ENABLED, '0');
        Setting::set(Setting::HOST_OFFLINE_SLACK_ENABLED, '0');

        Host::factory()->offline(10)->create([
            'last_offline_notified_at' => now()->subMinutes(5),
        ]);

        (new CheckHostsOffline)->handle();

        Notification::assertNothingSent();
    });

    it('re-notifies after the renotify window has elapsed', function () {
        Setting::set(Setting::HOST_OFFLINE_EMAIL_ENABLED, '1');
        Setting::set(Setting::ALERT_EMAIL, 'ops@example.com');
        Setting::set(Setting::HOST_OFFLINE_OFFLINE_MINUTES, '3');
        Setting::set(Setting::HOST_OFFLINE_RENOTIFY_MINUTES, '10');
        Setting::set(Setting::HOST_OFFLINE_TELEGRAM_ENABLED, '0');
        Setting::set(Setting::HOST_OFFLINE_SLACK_ENABLED, '0');

        Host::factory()->offline(30)->create([
            'last_offline_notified_at' => now()->subMinutes(15),
        ]);

        (new CheckHostsOffline)->handle();

        Notification::assertSentOnDemand(HostOffline::class);
    });

    it('does not notify hosts with null last_seen_at', function () {
        Setting::set(Setting::HOST_OFFLINE_EMAIL_ENABLED, '1');
        Setting::set(Setting::ALERT_EMAIL, 'ops@example.com');
        Setting::set(Setting::HOST_OFFLINE_OFFLINE_MINUTES, '3');
        Setting::set(Setting::HOST_OFFLINE_TELEGRAM_ENABLED, '0');
        Setting::set(Setting::HOST_OFFLINE_SLACK_ENABLED, '0');

        Host::factory()->create(['last_seen_at' => null]);

        (new CheckHostsOffline)->handle();

        Notification::assertNothingSent();
    });
});
