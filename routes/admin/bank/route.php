<?php

Route::prefix('banks')->group(function (){
    Route::get('/',[\App\Http\Controllers\BanksController::class,'index']);
    Route::post('/create',[\App\Http\Controllers\BanksController::class,'store']);
    Route::post('/{bank}/edit',[\App\Http\Controllers\BanksController::class,'update']);
    Route::get('/{bank}',[\App\Http\Controllers\BanksController::class,'show']);
});
