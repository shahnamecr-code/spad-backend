<?php

Route::prefix('group-attribute')->group(function (){
    Route::get('{item}', [\App\Http\Controllers\Admin\GroupAttributesController::class, 'show']);
    Route::post('{category}/create', [\App\Http\Controllers\Admin\GroupAttributesController::class, 'create']);
    Route::post('{item}/edit', [\App\Http\Controllers\Admin\GroupAttributesController::class, 'update']);
});
