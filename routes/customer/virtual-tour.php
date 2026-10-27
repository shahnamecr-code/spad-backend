<?php

use App\Http\Controllers\VirtualTourController;

Route::prefix('virtual-tours')->group(function () {
    Route::get('/assets/scenes/{scene}/{variant}', [VirtualTourController::class, 'sceneAsset'])->where('variant', 'ultra|high|medium|preview|thumbnail');
    Route::get('/assets/tours/{virtualTour}/floorplan', [VirtualTourController::class, 'floorplanAsset']);
    Route::get('/assets/hotspots/{hotspot}', [VirtualTourController::class, 'hotspotAsset']);
    Route::get('/property/{advertisement}', [VirtualTourController::class, 'byAdvertisement']);
    Route::get('/slug/{slug}', [VirtualTourController::class, 'bySlug']);
    Route::post('/{virtualTour}/session', [VirtualTourController::class, 'startSession'])->middleware('throttle:60,1');
    Route::post('/{virtualTour}/event', [VirtualTourController::class, 'event'])->middleware('throttle:120,1');
    Route::post('/{virtualTour}/ping', [VirtualTourController::class, 'ping'])->middleware('throttle:60,1');
});
