<?php

Route::prefix('location')->group(function (){
    Route::get('allCities',[\App\Http\Controllers\Admin\LocationController::class,'allCities']);
    Route::get('allRegions',[\App\Http\Controllers\Admin\LocationController::class,'allRegions']);
    Route::get('cityAllRegions/{city}',[\App\Http\Controllers\Admin\LocationController::class,'cityAllRegions']);
//    add from store
    Route::prefix('region')->group(function () {
        Route::get('', [\App\Http\Controllers\Admin\LocationController::class, 'region_index']);
        Route::post('', [\App\Http\Controllers\Admin\LocationController::class, 'region_store']);
        Route::get('{region}', [\App\Http\Controllers\Admin\LocationController::class, 'region_show']);
        Route::put('{region}', [\App\Http\Controllers\Admin\LocationController::class, 'region_update']);
        Route::delete('{region}', [\App\Http\Controllers\Admin\LocationController::class, 'region_delete']);
    });

    Route::prefix('city')->group(function () {
        Route::get('', [\App\Http\Controllers\Admin\LocationController::class, 'city_index']);
        Route::post('', [\App\Http\Controllers\Admin\LocationController::class, 'city_store']);
        Route::get('{city}', [\App\Http\Controllers\Admin\LocationController::class, 'city_show']);
        Route::put('{city}', [\App\Http\Controllers\Admin\LocationController::class, 'city_update']);
        Route::delete('{city}', [\App\Http\Controllers\Admin\LocationController::class, 'city_delete']);
        Route::get('{city}/regions', [\App\Http\Controllers\Admin\LocationController::class, 'cities_regions']);
    });

    Route::prefix('province')->group(function () {
        Route::get('', [\App\Http\Controllers\Admin\LocationController::class, 'province_index']);
        Route::post('', [\App\Http\Controllers\Admin\LocationController::class, 'province_store']);
        Route::get('{province}', [\App\Http\Controllers\Admin\LocationController::class, 'province_show']);
        Route::put('{province}', [\App\Http\Controllers\Admin\LocationController::class, 'province_update']);
        Route::delete('{province}', [\App\Http\Controllers\Admin\LocationController::class, 'province_delete']);
        Route::get('{province}/cities', [\App\Http\Controllers\Admin\LocationController::class, 'province_cities']);
    });
});
