<?php

Route::prefix('blog-post')->group(function (){
    Route::get('',[\App\Http\Controllers\Blog\BlogPostController::class,'index']);
    Route::post('create',[\App\Http\Controllers\Blog\BlogPostController::class,'store']);
    Route::post('{blogPost}/edit',[\App\Http\Controllers\Blog\BlogPostController::class,'update']);
    Route::get('{blogPost}',[\App\Http\Controllers\Blog\BlogPostController::class,'show']);
});
