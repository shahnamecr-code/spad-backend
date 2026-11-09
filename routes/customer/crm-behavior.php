<?php
use App\Http\Controllers\SpadCrmBehaviorController;

Route::middleware(['auth:sanctum', 'throttle:120,1'])->prefix('crm-behavior')->group(function () {
    Route::get('/consent', [SpadCrmBehaviorController::class, 'consent']);
    Route::post('/consent', [SpadCrmBehaviorController::class, 'setConsent'])->middleware('throttle:10,1');
    Route::delete('/consent', [SpadCrmBehaviorController::class, 'erase'])->middleware('throttle:10,1');
    Route::post('/events', [SpadCrmBehaviorController::class, 'event']);
});
