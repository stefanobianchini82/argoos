<?php

namespace App\Providers;

use App\Channels\TelegramChannel;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->app->resolving(ChannelManager::class, function (ChannelManager $manager) {
            $manager->extend('telegram', fn () => new TelegramChannel());
        });
    }
}
