<?php

use App\Http\Controllers\SpadHomeGardenController as Garden;
use Illuminate\Support\Facades\Route;

Route::prefix('home-garden')->middleware(['auth:sanctum', 'throttle:75,1'])->group(function () {
    Route::get('plants', [Garden::class, 'index']);
    Route::post('plants', [Garden::class, 'store'])->middleware('throttle:12,1,spad-garden-create');
    Route::prefix('plants/{plant}')->where(['plant' => '[a-f0-9-]{36}'])->group(function () {
        Route::get('/', [Garden::class, 'show']);
        Route::patch('/', [Garden::class, 'update']);
        Route::put('species', [Garden::class, 'confirmSpecies']);
        Route::post('water', [Garden::class, 'water']);
        Route::post('activity', [Garden::class, 'addActivity']);
        Route::post('photos', [Garden::class, 'upload'])->middleware('throttle:12,1,spad-garden-media');
        Route::get('photos/{photo}', [Garden::class, 'photo'])->where('photo', '[a-f0-9-]{36}');
        Route::delete('photos/{photo}', [Garden::class, 'removePhoto'])->where('photo', '[a-f0-9-]{36}');
        Route::post('photos/{photo}/identify', [Garden::class, 'identify'])
            ->where('photo', '[a-f0-9-]{36}')->middleware('throttle:3,60,spad-garden-identify');
    });
});
