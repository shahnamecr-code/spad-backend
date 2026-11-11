<?php

use App\Http\Controllers\Admin\SpadWhatsAppMarketController as WA;
use App\Http\Controllers\Admin\SpadWhatsAppPersonalController as PersonalWA;

// These routes are already protected by auth:sanctum,admin in routes/api.php.
// Keep the write/send quotas strict, but do not stack low shared-IP counters
// on the read-only, privately cached poster preview.
Route::prefix('whatsapp-market')->group(function () {
    Route::get('/', [WA::class, 'index'])->middleware('throttle:30,1');
    Route::post('/', [WA::class, 'save'])->middleware('throttle:10,1');
    Route::get('/preview', [WA::class, 'preview'])->middleware('throttle:spad-wa-preview');
    Route::post('/run', [WA::class, 'runNow'])->middleware('throttle:2,1');
    // All routes inherit auth:sanctum,admin; PersonalWA additionally checks
    // the Super Admin role. The QR and self-send never expose a group target.
    // Own counters (3rd arg = key prefix): the QR screen polls status and a
    // rotating code every few seconds while the owner scans with the phone.
    Route::get('/personal/status', [PersonalWA::class, 'status'])->middleware('throttle:40,1,spad-wa-personal-status');
    Route::get('/personal/qr', [PersonalWA::class, 'pairingQr'])->middleware('throttle:30,1,spad-wa-personal-qr');
    Route::post('/personal/settings', [PersonalWA::class, 'preferences'])->middleware('throttle:5,1');
    Route::post('/personal/send', [PersonalWA::class, 'sendNow'])->middleware('throttle:2,1');
    // "Send now" batch (gold/FX + every saved city): at most 3 per 10 minutes.
    Route::post('/personal/send-now', [PersonalWA::class, 'sendAllNow'])->middleware('throttle:3,10,spad-wa-personal-send-now');
});
