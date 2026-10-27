<?php

use App\Http\Controllers\Admin\VirtualTourController;

Route::prefix('virtual-tours')->group(function () {
    Route::get('/assets/scenes/{scene}/{variant}', [VirtualTourController::class, 'sceneAsset'])->where('variant', 'ultra|high|medium|preview|thumbnail');
    Route::get('/assets/tours/{virtualTour}/floorplan', [VirtualTourController::class, 'floorplanAsset']);
    Route::get('/assets/hotspots/{hotspot}', [VirtualTourController::class, 'hotspotAsset']);
    Route::get('/', [VirtualTourController::class, 'index']);
    Route::get('/property/{advertisement}', [VirtualTourController::class, 'forAdvertisement']);
    Route::get('/properties', [VirtualTourController::class, 'properties']);
    Route::post('/property/{advertisement}', [VirtualTourController::class, 'ensureForAdvertisement']);
    Route::get('/{virtualTour}', [VirtualTourController::class, 'show']);
    Route::put('/{virtualTour}', [VirtualTourController::class, 'update']);
    Route::post('/{virtualTour}/scenes', [VirtualTourController::class, 'uploadScenes']);
    Route::post('/{virtualTour}/reorder-scenes', [VirtualTourController::class, 'reorderScenes']);
    Route::post('/{virtualTour}/floorplan', [VirtualTourController::class, 'uploadFloorplan']);
    Route::delete('/{virtualTour}/floorplan', [VirtualTourController::class, 'deleteFloorplan']);
    Route::post('/{virtualTour}/floorplan-points', [VirtualTourController::class, 'saveFloorplanPoint']);
    Route::get('/{virtualTour}/validate-publish', [VirtualTourController::class, 'validatePublish']);
    Route::post('/{virtualTour}/publish', [VirtualTourController::class, 'publish']);
    Route::post('/{virtualTour}/unpublish', [VirtualTourController::class, 'unpublish']);
    Route::post('/{virtualTour}/duplicate', [VirtualTourController::class, 'duplicate']);
    Route::get('/{virtualTour}/analytics', [VirtualTourController::class, 'analytics']);
    Route::delete('/{virtualTour}', [VirtualTourController::class, 'destroy']);

    Route::put('/scenes/{scene}', [VirtualTourController::class, 'updateScene']);
    Route::post('/scenes/{scene}/replace', [VirtualTourController::class, 'replaceSceneImage']);
    Route::post('/scenes/{scene}/reprocess', [VirtualTourController::class, 'reprocessScene']);
    Route::delete('/scenes/{scene}', [VirtualTourController::class, 'deleteScene']);
    Route::post('/scenes/{scene}/hotspots', [VirtualTourController::class, 'createHotspot']);

    Route::put('/hotspots/{hotspot}', [VirtualTourController::class, 'updateHotspot']);
    Route::post('/hotspots/{hotspot}/media', [VirtualTourController::class, 'uploadHotspotMedia']);
    Route::delete('/hotspots/{hotspot}', [VirtualTourController::class, 'deleteHotspot']);
});
