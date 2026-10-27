<?php

Route::prefix('blog-category')->group(function (){
    Route::get('',[\App\Http\Controllers\Blog\BlogCategoryController::class,'allBlogCategories']);
    Route::post('create',[\App\Http\Controllers\Blog\BlogCategoryController::class,'store']);
    Route::post('{blogCategory}/edit',[\App\Http\Controllers\Blog\BlogCategoryController::class,'update']);
    Route::get('{blogCategory}',[\App\Http\Controllers\Blog\BlogCategoryController::class,'show']);

});
