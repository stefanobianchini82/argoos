<?php

use App\Http\Controllers\Api\MetricController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['throttle:agents', 'auth.agent'])->group(function () {
    Route::post('/metrics', [MetricController::class, 'store']);
});
