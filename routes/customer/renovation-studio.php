<?php

use App\Http\Controllers\RenovationStudioController as S;
use Illuminate\Support\Facades\Route;

// Renovation studio v2: tours, catalog and room layouts are public; designs and quotes need a login.
Route::prefix('online-renovation/studio')->group(function () {
    Route::get('tours', [S::class, 'tours']);
    Route::get('tours/{tourId}', [S::class, 'tour'])->whereNumber('tourId');
    Route::get('products', [S::class, 'products']);
    Route::get('layout/{tourId}/{sceneKey}', [S::class, 'layout'])->whereNumber('tourId')->where('sceneKey', '[a-z0-9:_-]{1,60}');
    Route::post('layout/{tourId}/{sceneKey}/analyze', [S::class, 'analyze'])->whereNumber('tourId')
        ->where('sceneKey', '[a-z0-9:_-]{1,60}')->middleware('throttle:8,10');
    Route::post('quote', [S::class, 'quote'])->middleware('throttle:40,1');
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('designs', [S::class, 'mine']);
        Route::get('designs/{id}', [S::class, 'show'])->whereNumber('id');
        Route::post('designs', [S::class, 'save'])->middleware('throttle:30,1');
        Route::put('designs/{id}', [S::class, 'save'])->whereNumber('id')->middleware('throttle:30,1');
        Route::post('designs/{id}/submit', [S::class, 'submit'])->whereNumber('id')->middleware('throttle:4,1');
    });
});
