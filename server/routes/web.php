<?php

use App\Livewire\DashboardOverview;
use App\Livewire\HostDetail;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.basic.argoos')->group(function () {
    Route::get('/', DashboardOverview::class);
    Route::get('/hosts/{host}', HostDetail::class);
});
