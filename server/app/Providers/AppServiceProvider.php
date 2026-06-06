<?php

namespace App\Providers;

use App\Channels\SlackChannel;
use App\Channels\TelegramChannel;
use App\Channels\WebhookChannel;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        $this->app->resolving(ChannelManager::class, function (ChannelManager $manager) {
            $manager->extend('telegram', fn () => new TelegramChannel());
            $manager->extend('slack', fn () => new SlackChannel());
            $manager->extend('webhook', fn () => new WebhookChannel());
        });

        RateLimiter::for('agents', function (Request $request) {
            $perMinute = (int) config('app.agent_rate_limit', 300);
            return Limit::perMinute($perMinute)->by($request->ip());
        });
    }
}
