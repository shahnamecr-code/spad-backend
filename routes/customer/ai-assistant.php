<?php

use App\Http\Controllers\SpadAiAssistantController;

Route::get('ai-assistant/config', [SpadAiAssistantController::class, 'config'])
    ->middleware('throttle:30,1');
Route::post('ai-assistant/chat', [SpadAiAssistantController::class, 'chat'])
    ->middleware('throttle:6,1');
