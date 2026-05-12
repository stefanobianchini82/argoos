<?php

use App\Http\Middleware\AuthenticateAgent;
use App\Http\Middleware\BasicAuth;
use App\Jobs\CheckAlertRules;
use App\Jobs\CheckHostsOffline;
use App\Jobs\PruneOldMetrics;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.agent'         => AuthenticateAgent::class,
            'auth.basic.argoos'  => BasicAuth::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->job(CheckAlertRules::class)->everyMinute();
        $schedule->job(CheckHostsOffline::class)->everyMinute();
        $schedule->job(PruneOldMetrics::class)->monthly();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
