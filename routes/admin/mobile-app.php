<?php

use App\Http\Controllers\Admin\SpadMobileAppController;

Route::prefix('mobile-app')->group(function () {
    Route::get('settings', [SpadMobileAppController::class, 'settings']);
    Route::post('settings', [SpadMobileAppController::class, 'saveSettings']);
    Route::get('notifications', [SpadMobileAppController::class, 'notifications']);
    Route::post('notifications', [SpadMobileAppController::class, 'sendNotification'])->middleware('throttle:10,1');
    Route::delete('notifications/{id}', [SpadMobileAppController::class, 'deleteNotification']);
});
