<?php

use App\Http\Controllers\Admin\SpadAiAssistantSettingsController;

Route::prefix('ai-assistant')->middleware('throttle:20,1')->group(function () {
    Route::get('settings', [SpadAiAssistantSettingsController::class, 'show']);
    Route::post('settings', [SpadAiAssistantSettingsController::class, 'save']);
});
