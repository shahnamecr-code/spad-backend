<?php

Route::prefix('category')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\CategoriesController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Admin\CategoriesController::class, 'store']);
    Route::get('get/{category}', [\App\Http\Controllers\Admin\CategoriesController::class, 'show']);
    Route::post('{category}/edit', [\App\Http\Controllers\Admin\CategoriesController::class, 'update']);
    Route::get('{category}/group_attributes', [\App\Http\Controllers\Admin\CategoriesController::class, 'group_attributes']);
    Route::post('search_categories', [\App\Http\Controllers\Admin\CategoriesController::class, 'search_categories']);
    //Route::get('get_categories', [\App\Http\Controllers\Admin\CategoriesController::class, 'get_categories_data']);
    Route::get('get_categories', [\App\Http\Controllers\Admin\CategoriesController::class, 'get_categories_data']);
});
