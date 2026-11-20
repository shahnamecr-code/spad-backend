<?php

use App\Http\Controllers\SpadScanController;
use Illuminate\Support\Facades\Route;

Route::prefix('online-renovation/scans')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [SpadScanController::class, 'index']);
    Route::get('materials', [SpadScanController::class, 'materials']);
    Route::post('estimate', [SpadScanController::class, 'estimate'])->middleware('throttle:25,1');
    Route::post('/', [SpadScanController::class, 'store'])->middleware('throttle:12,1');
    Route::get('{id}', [SpadScanController::class, 'show'])->whereNumber('id');
    Route::put('{id}', [SpadScanController::class, 'update'])->whereNumber('id')->middleware('throttle:20,1');
    Route::delete('{id}', [SpadScanController::class, 'destroy'])->whereNumber('id');
    Route::post('{id}/renovation', [SpadScanController::class, 'startRenovation'])->whereNumber('id')->middleware('throttle:5,1');
    Route::post('{id}/photos', [SpadScanController::class, 'upload'])->whereNumber('id')->middleware('throttle:15,1');
    Route::get('{id}/photos/{photoId}', [SpadScanController::class, 'photo'])->whereNumber('id')->whereNumber('photoId');
    Route::delete('{id}/photos/{photoId}', [SpadScanController::class, 'destroyPhoto'])->whereNumber('id')->whereNumber('photoId');
});
