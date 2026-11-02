<?php

use App\Http\Controllers\PropertyDepositController;

Route::get('property-deposits/{advertisement}', [PropertyDepositController::class, 'show']);
Route::match(['get', 'post'], 'property-deposits/callback/{order}/{token}', [PropertyDepositController::class, 'callback'])
    ->where('token', '[A-Za-z0-9]{64}')->middleware('throttle:30,1');
Route::middleware('auth:sanctum')->group(function () {
    Route::post('property-deposits/{advertisement}/pay', [PropertyDepositController::class, 'pay'])
        ->middleware('throttle:5,1');
    Route::get('property-deposits/orders/{order}', [PropertyDepositController::class, 'status']);
});
