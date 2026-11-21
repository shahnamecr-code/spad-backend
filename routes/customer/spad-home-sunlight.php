<?php

use App\Http\Controllers\SpadSunlightController;
use Illuminate\Support\Facades\Route;

Route::prefix('home-sunlight')->group(function () {
    Route::get('example', [SpadSunlightController::class, 'example'])->middleware('throttle:45,1');
    Route::post('analyze', [SpadSunlightController::class, 'analyze'])->middleware('throttle:30,1');
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('projects', [SpadSunlightController::class, 'index']);
        Route::post('projects', [SpadSunlightController::class, 'store'])->middleware('throttle:12,1');
        Route::get('projects/{id}', [SpadSunlightController::class, 'show'])->whereNumber('id');
        Route::put('projects/{id}', [SpadSunlightController::class, 'update'])->whereNumber('id')->middleware('throttle:20,1');
        Route::delete('projects/{id}', [SpadSunlightController::class, 'destroy'])->whereNumber('id');
        Route::get('scan/{id}', [SpadSunlightController::class, 'fromScan'])->whereNumber('id');
    });
});
