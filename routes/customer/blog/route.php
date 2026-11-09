<?php

Route::prefix('blog')
    ->controller(\App\Http\Controllers\PublicBlogController::class)
    ->group(function (){
        Route::get('posts','index');
        Route::get('/category/{category:slug}/posts','showCategoryPosts')->where('slug', '[^/]+');
        Route::get('post/{post:slug}','showSinglePost')->where('slug', '[^/]+');
    });
