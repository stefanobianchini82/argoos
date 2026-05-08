<?php

use App\Livewire\AlertRuleForm;
use App\Livewire\AlertRuleList;
use App\Livewire\DashboardOverview;
use App\Livewire\HostCreate;
use App\Livewire\HostDetail;
use App\Livewire\HostEdit;
use App\Livewire\Settings;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.basic.argoos')->group(function () {
    Route::get('/', DashboardOverview::class);
    Route::get('/hosts/create',                           HostCreate::class)->name('hosts.create');
    Route::get('/hosts/{host}',                           HostDetail::class)->name('hosts.show');
    Route::get('/hosts/{host}/edit',                      HostEdit::class)->name('hosts.edit');
    Route::get('/hosts/{host}/alerts',                    AlertRuleList::class)->name('hosts.alerts');
    Route::get('/hosts/{host}/alerts/create',             AlertRuleForm::class)->name('hosts.alerts.create');
    Route::get('/hosts/{host}/alerts/{alertRule}/edit',   AlertRuleForm::class)->name('hosts.alerts.edit');
    Route::get('/settings',                               Settings::class)->name('settings');
});
