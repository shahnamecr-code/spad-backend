<?php

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::prefix('myAddresses')->group(function (){
        Route::get('',[\App\Http\Controllers\Customer\AddressController::class,'index']);
        Route::post('',[\App\Http\Controllers\Customer\AddressController::class,'store']);
        Route::post('{address}/edit',[\App\Http\Controllers\Customer\AddressController::class,'update']);
    });
});
