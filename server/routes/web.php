<?php

use App\Livewire\DashboardOverview;
use App\Livewire\HostCreate;
use App\Livewire\HostDetail;
use App\Livewire\HostEdit;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.basic.argoos')->group(function () {
    Route::get('/', DashboardOverview::class);
    Route::get('/hosts/create',      HostCreate::class)->name('hosts.create');
    Route::get('/hosts/{host}',      HostDetail::class)->name('hosts.show');
    Route::get('/hosts/{host}/edit', HostEdit::class)->name('hosts.edit');
});
