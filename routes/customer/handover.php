<?php
// Private, user-owned handover checklist API. Never expose via public routes.
use App\Http\Controllers\PropertyHandoverController;

Route::middleware('auth:sanctum')->prefix('handover-checklists')->group(function () {
    Route::get('/', [PropertyHandoverController::class, 'index']);
    Route::post('/', [PropertyHandoverController::class, 'store']);
    Route::get('/{id}', [PropertyHandoverController::class, 'show'])->whereNumber('id');
    Route::put('/{id}', [PropertyHandoverController::class, 'update'])->whereNumber('id');
    Route::post('/{id}/complete', [PropertyHandoverController::class, 'complete'])->whereNumber('id');
    Route::post('/{id}/photos', [PropertyHandoverController::class, 'uploadPhoto'])->whereNumber('id');
    Route::get('/{id}/photos/{photoId}', [PropertyHandoverController::class, 'photo'])->whereNumber('id')->whereNumber('photoId');
    Route::delete('/{id}/photos/{photoId}', [PropertyHandoverController::class, 'removePhoto'])->whereNumber('id')->whereNumber('photoId');
});
