<?php

use App\Http\Controllers\Admin\SpadWhatsAppMarketController as WA;

Route::prefix('whatsapp-market')->middleware('throttle:30,1')->group(function () {
    Route::get('/', [WA::class, 'index']);
    Route::post('/', [WA::class, 'save']);
    Route::get('/preview', [WA::class, 'preview'])->middleware('throttle:5,1');
    Route::post('/run', [WA::class, 'runNow'])->middleware('throttle:2,1');
});
