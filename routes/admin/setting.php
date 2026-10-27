<?php

Route::prefix('settings')->group(function (){
    Route::delete('default-city/unset',[\App\Http\Controllers\Admin\SettingsController::class,'unsetDefaultCity']);
    Route::post('default-city/{city}',[\App\Http\Controllers\Admin\SettingsController::class,'setDefaultCity']);
    Route::get('default-city/get',[\App\Http\Controllers\Admin\SettingsController::class,'getDefaultCity']);
    Route::get('/all-setting-tabs',[\App\Http\Controllers\Admin\SettingsController::class,'getTabs']);
    Route::get('sidebar/',[\App\Http\Controllers\Admin\SettingsController::class,'showAllSidebars']);
    Route::post('sidebar/create',[\App\Http\Controllers\Admin\SettingsController::class,'createSidebar']);
    Route::get('{group}',[\App\Http\Controllers\Admin\SettingsController::class,'getByGroup']);
    Route::post('{group}',[\App\Http\Controllers\Admin\SettingsController::class,'saveByGroup']);
});

