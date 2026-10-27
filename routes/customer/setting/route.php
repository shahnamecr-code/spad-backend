<?php

Route::get('/market-prices',[\App\Http\Controllers\SettingsController::class,'marketPrices']);

Route::get('/settings/{group}',[\App\Http\Controllers\SettingsController::class,'getByGroup']);

Route::get('/global-settings',[\App\Http\Controllers\SettingsController::class,'globalSetting']);

Route::get('/server-side-settings',[\App\Http\Controllers\SettingsController::class,'serverSideSettings']);
