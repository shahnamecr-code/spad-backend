<?php

Route::group(['prefix' => 'cart'], function () {
    Route::get('', [\App\Http\Controllers\Customer\CartController::class, 'index']);
    Route::post('', [\App\Http\Controllers\Customer\CartController::class, 'add']);
    Route::post('increase', [\App\Http\Controllers\Customer\CartController::class, 'increase']);
    Route::put('', [\App\Http\Controllers\Customer\CartController::class, 'decrease']);
    Route::delete('', [\App\Http\Controllers\Customer\CartController::class, 'destroy']);
    Route::post('address', [\App\Http\Controllers\Customer\CartController::class, 'add_address']);
    Route::post('payment_method', [\App\Http\Controllers\Customer\CartController::class, 'add_payment_method']);
    Route::get('checkout', [\App\Http\Controllers\Customer\CartController::class, 'checkout']);
});


Route::group(['prefix' => 'order'], function () {
    Route::get('', [\App\Http\Controllers\Customer\OrderController::class, 'index']);
    Route::get('{order}', [\App\Http\Controllers\Customer\OrderController::class, 'show']);
});
