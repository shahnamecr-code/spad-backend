<?php

Route::prefix('property-acceptance')->group(function () {
    Route::get('', [\App\Http\Controllers\PropertyAcceptanceController::class, 'index']);
    Route::get('operators', [\App\Http\Controllers\PropertyAcceptanceController::class, 'operators']);
    Route::get('{id}', [\App\Http\Controllers\PropertyAcceptanceController::class, 'show'])->whereNumber('id');
    Route::post('{id}', [\App\Http\Controllers\PropertyAcceptanceController::class, 'update'])->whereNumber('id')->middleware('throttle:30,1');
});
