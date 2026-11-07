<?php

use App\Http\Controllers\Admin\VirtualTourController;

// Finished tour upload only. Scene editing, GLB, hotspots and the old
// studio routes are intentionally no longer registered.
Route::prefix('virtual-tours')->group(function () {
    Route::get('/property/{advertisement}', [VirtualTourController::class, 'forAdvertisement']);
    Route::post('/property/{advertisement}/zip', [VirtualTourController::class, 'importZipForAdvertisement']);
});
