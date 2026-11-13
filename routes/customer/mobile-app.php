<?php

use App\Http\Controllers\SpadMobileAppController;

// Used by the Android/iPhone app. Open to guests; a signed-in user is recognised by their token.
Route::prefix('app')->group(function () {
    Route::get('config', [SpadMobileAppController::class, 'config'])->middleware('throttle:60,1');
    Route::post('devices', [SpadMobileAppController::class, 'registerDevice'])->middleware('throttle:20,1');
    Route::get('notifications', [SpadMobileAppController::class, 'notifications'])->middleware('throttle:30,1');
});
