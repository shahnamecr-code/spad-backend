<?php
use App\Http\Controllers\Admin\SpadCrmBehaviorInsightsController;

Route::prefix('crm-pro/behavior')->middleware('throttle:60,1')->group(function () {
    Route::get('/overview', [SpadCrmBehaviorInsightsController::class, 'overview']);
    Route::get('/users/{userId}', [SpadCrmBehaviorInsightsController::class, 'user'])->whereNumber('userId');
});
