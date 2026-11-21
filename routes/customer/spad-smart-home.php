<?php

use App\Http\Controllers\SpadSmartHomeController;
use Illuminate\Support\Facades\Route;

Route::prefix('smart-home')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [SpadSmartHomeController::class, 'show'])->middleware('throttle:12,1,spad-smart-home-state');
    Route::post('connection', [SpadSmartHomeController::class, 'connect'])->middleware('throttle:5,10,spad-smart-home-connect');
    Route::delete('connection', [SpadSmartHomeController::class, 'disconnect'])->middleware('throttle:12,1,spad-smart-home-disconnect');
    Route::post('actions', [SpadSmartHomeController::class, 'action'])->middleware('throttle:12,1,spad-smart-home-action');
    Route::get('audit', [SpadSmartHomeController::class, 'audit'])->middleware('throttle:20,1,spad-smart-home-audit');
});
