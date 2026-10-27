<?php

Route::prefix('settings')->group(function (){
    Route::get('/all-setting-tabs',[\App\Http\Controllers\Admin\SettingsController::class,'getTabs']);
    Route::get('{group}',[\App\Http\Controllers\Admin\SettingsController::class,'getByGroup']);
    Route::post('{group}',[\App\Http\Controllers\Admin\SettingsController::class,'saveByGroup']);
});

