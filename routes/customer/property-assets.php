<?php

// Private property portfolio: every operation is restricted to the account owner.
Route::prefix('my-property-assets')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [\App\Http\Controllers\SpadPropertyAssetController::class, 'index'])->middleware('throttle:20,1');
    Route::post('/', [\App\Http\Controllers\SpadPropertyAssetController::class, 'store'])->middleware('throttle:10,60');
    Route::post('{id}/refresh', [\App\Http\Controllers\SpadPropertyAssetController::class, 'refresh'])
        ->whereNumber('id')->middleware('throttle:8,60');
    Route::patch('{id}', [\App\Http\Controllers\SpadPropertyAssetController::class, 'rename'])
        ->whereNumber('id')->middleware('throttle:15,1');
    Route::delete('{id}', [\App\Http\Controllers\SpadPropertyAssetController::class, 'destroy'])
        ->whereNumber('id')->middleware('throttle:10,1');
});
