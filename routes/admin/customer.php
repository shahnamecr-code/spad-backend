<?php
Route::prefix('customer')->group(function () {
    Route::get('', [\App\Http\Controllers\Admin\CustomerController::class, 'index']);
    Route::post('', [\App\Http\Controllers\Admin\CustomerController::class, 'store']);
    Route::get('{user}', [\App\Http\Controllers\Admin\CustomerController::class, 'show']);
    Route::put('{user}', [\App\Http\Controllers\Admin\CustomerController::class, 'update']);
    Route::delete('{user}', [\App\Http\Controllers\Admin\CustomerController::class, 'destroy']);
});
