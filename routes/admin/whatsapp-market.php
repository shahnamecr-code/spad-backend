<?php

use App\Http\Controllers\Admin\SpadWhatsAppMarketController as WA;

// These routes are already protected by auth:sanctum,admin in routes/api.php.
// Keep the write/send quotas strict, but do not stack low shared-IP counters
// on the read-only, privately cached poster preview.
Route::prefix('whatsapp-market')->group(function () {
    Route::get('/', [WA::class, 'index'])->middleware('throttle:30,1');
    Route::post('/', [WA::class, 'save'])->middleware('throttle:10,1');
    Route::get('/preview', [WA::class, 'preview'])->middleware('throttle:spad-wa-preview');
    Route::post('/run', [WA::class, 'runNow'])->middleware('throttle:2,1');
});
