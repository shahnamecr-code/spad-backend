<?php
// Private, user-owned handover checklist API. Never expose via public routes.
use App\Http\Controllers\PropertyHandoverController;
use App\Http\Controllers\PropertyHandoverSignatureController;


// Participants can verify only via an unguessable invitation plus an SMS code.
// Never return the underlying checklist or private photos through these endpoints.
Route::get('handover-sign/{token}', [PropertyHandoverSignatureController::class, 'invitationInfo'])->middleware('throttle:30,1');
Route::post('handover-sign/{token}/send', [PropertyHandoverSignatureController::class, 'publicSend'])->middleware('throttle:5,1');
Route::post('handover-sign/{token}/verify', [PropertyHandoverSignatureController::class, 'verify'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->prefix('handover-checklists')->group(function () {
    Route::get('/', [PropertyHandoverController::class, 'index']);
    Route::post('/', [PropertyHandoverController::class, 'store']);
    Route::get('/{id}', [PropertyHandoverController::class, 'show'])->whereNumber('id');
    Route::put('/{id}', [PropertyHandoverController::class, 'update'])->whereNumber('id');
    Route::get('/{id}/signatures', [PropertyHandoverSignatureController::class, 'status'])->whereNumber('id');
    Route::post('/{id}/signatures/start', [PropertyHandoverSignatureController::class, 'start'])->whereNumber('id');
    Route::post('/{id}/signatures/{party}/send', [PropertyHandoverSignatureController::class, 'send'])->whereNumber('id');
    Route::post('/{id}/complete', [PropertyHandoverController::class, 'complete'])->whereNumber('id');
    Route::post('/{id}/photos', [PropertyHandoverController::class, 'uploadPhoto'])->whereNumber('id');
    Route::get('/{id}/photos/{photoId}', [PropertyHandoverController::class, 'photo'])->whereNumber('id')->whereNumber('photoId');
    Route::delete('/{id}/photos/{photoId}', [PropertyHandoverController::class, 'removePhoto'])->whereNumber('id')->whereNumber('photoId');
});
