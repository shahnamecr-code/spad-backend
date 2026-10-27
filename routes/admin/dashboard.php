<?php

Route::prefix('dashboard')
    ->group(function (){
        Route::get('/AdsStatistics/{type}',[\App\Http\Controllers\Admin\DashboardController::class,'getLastAds']);
        Route::get('/CrmRequestsStatistics/{type}',[\App\Http\Controllers\Admin\DashboardController::class,'getLastCrmRequests']);
        Route::get('/',[\App\Http\Controllers\Admin\DashboardController::class,'index']);
    });
