<?php

Route::prefix('experts')
    ->group(function (){
        Route::get('/',[\App\Http\Controllers\ExpertsController::class,'index']);
        Route::get('/info/{admin}',[\App\Http\Controllers\ExpertsController::class,'showExpertInfo']);
        Route::get('/advertisements/{admin}',[\App\Http\Controllers\ExpertsController::class,'showExpertAds']);
        Route::middleware('auth:sanctum')->group(function (){
            Route::post('/point/{admin}',[\App\Http\Controllers\ExpertsController::class,'pointToExpert']);
        });
    });
