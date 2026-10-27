<?php

Route::prefix('attribute')->group(function(){
    Route::get('/', [\App\Http\Controllers\Admin\AttributesController::class, 'index']);
    Route::get('get/{attribute}', [\App\Http\Controllers\Admin\AttributesController::class, 'show']);
    Route::post('', [\App\Http\Controllers\Admin\AttributesController::class, 'store'])->name('attribute');
    Route::post('{attribute}/edit', [\App\Http\Controllers\Admin\AttributesController::class, 'update']);
    Route::post('search_attributes', [\App\Http\Controllers\Admin\AttributesController::class, 'search_attributes']);
    Route::post('search_variable_attributes/{template}', [\App\Http\Controllers\Admin\AttributesController::class, 'search_variable_attributes']);
});
