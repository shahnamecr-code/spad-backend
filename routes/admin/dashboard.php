<?php

Route::prefix('dashboard')
    ->group(function (){
        Route::get('/AdsStatistics/{type}',[\App\Http\Controllers\Admin\DashboardController::class,'getLastAds']);
        Route::get('/CrmRequestsStatistics/{type}',[\App\Http\Controllers\Admin\DashboardController::class,'getLastCrmRequests']);
        Route::get('/server-health',[\App\Http\Controllers\Admin\SpadServerHealthController::class,'index'])->middleware('throttle:30,1');
        Route::get('/server-resources',[\App\Http\Controllers\Admin\SpadServerHealthController::class,'resources'])->middleware('throttle:30,1');
        Route::get('/',[\App\Http\Controllers\Admin\DashboardController::class,'index']);
    });
