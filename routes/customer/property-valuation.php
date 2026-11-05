<?php

// National aggregates are public, like the existing market board; personal
// postal lookup and valuation are strictly authenticated, and rate-limited.
Route::prefix('property-market')->group(function () {
    Route::get('summary', [\App\Http\Controllers\PropertyValuationController::class, 'summary'])
        ->middleware('throttle:40,1');
    Route::get('places/{placeId}/children', [\App\Http\Controllers\PropertyValuationController::class, 'children'])
        ->whereNumber('placeId')->middleware('throttle:40,1');
});

Route::prefix('property-valuation')->middleware('auth:sanctum')->group(function () {
    Route::post('postal-lookup', [\App\Http\Controllers\PropertyValuationController::class, 'postalLookup'])
        ->middleware('throttle:5,60');
    Route::post('estimate', [\App\Http\Controllers\PropertyValuationController::class, 'estimate'])
        ->middleware('throttle:15,1');
});
