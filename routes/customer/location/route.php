<?php
Route::prefix('location')->group(function (){
    Route::get('/provinces',[\App\Http\Controllers\LocationController::class,'provinces']);
    Route::get('{province}/cities',[\App\Http\Controllers\LocationController::class,'cities']);
});
