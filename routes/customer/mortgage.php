<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MortgageController;

Route::get('mortgage/settings', [MortgageController::class, 'publicSettings']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('mortgage/requests', [MortgageController::class, 'store'])->middleware('throttle:5,60');
    Route::get('mortgage/my-requests', [MortgageController::class, 'mine']);
});
