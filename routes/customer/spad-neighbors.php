<?php

use App\Http\Controllers\SpadNeighborsController as Neighbors;
use Illuminate\Support\Facades\Route;

Route::prefix('neighbors')->group(function () {
    Route::get('regions', [Neighbors::class, 'regions']);
    Route::get('posts', [Neighbors::class, 'index']);
    Route::get('posts/{id}', [Neighbors::class, 'show'])->whereNumber('id');
    Route::get('posts/{id}/comments', [Neighbors::class, 'comments'])->whereNumber('id');
    Route::get('media/{id}', [Neighbors::class, 'media'])->whereNumber('id');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [Neighbors::class, 'me']);
        Route::patch('preferences', [Neighbors::class, 'preferences'])->middleware('throttle:20,1');
        Route::match(['put', 'delete'], 'follows/{region}', [Neighbors::class, 'follow'])->whereNumber('region')->middleware('throttle:20,1');
        Route::post('residency/{region}', [Neighbors::class, 'residency'])->whereNumber('region')->middleware('throttle:6,60');
        Route::get('my-posts', [Neighbors::class, 'mine']);
        Route::get('my-comments', [Neighbors::class, 'myComments']);
        Route::get('bookmarks', [Neighbors::class, 'bookmarks']);
        Route::get('notifications', [Neighbors::class, 'notices']);
        Route::patch('notifications/{id}', [Neighbors::class, 'readNotice'])->whereNumber('id');
        Route::post('posts', [Neighbors::class, 'save'])->middleware('throttle:12,60');
        Route::patch('posts/{id}', [Neighbors::class, 'save'])->whereNumber('id')->middleware('throttle:12,60');
        Route::delete('posts/{id}', [Neighbors::class, 'delete'])->whereNumber('id');
        Route::patch('posts/{id}/resolve', [Neighbors::class, 'resolveLost'])->whereNumber('id');
        Route::post('posts/{id}/media', [Neighbors::class, 'upload'])->whereNumber('id')->middleware('throttle:18,60');
        Route::delete('posts/{id}/media/{media}', [Neighbors::class, 'deleteMedia'])->whereNumber('id')->whereNumber('media');
        Route::get('my-media/{id}', [Neighbors::class, 'ownMedia'])->whereNumber('id');
        Route::post('posts/{id}/comments', [Neighbors::class, 'comment'])->whereNumber('id')->middleware('throttle:30,60');
        Route::delete('comments/{id}', [Neighbors::class, 'deleteComment'])->whereNumber('id');
        Route::match(['put', 'delete'], 'posts/{id}/interactions/{kind}', [Neighbors::class, 'interact'])
            ->whereNumber('id')->where('kind', 'like|bookmark')->middleware('throttle:45,1');
        Route::post('reports/{type}/{id}', [Neighbors::class, 'report'])->whereNumber('id')->where('type', 'post|comment')->middleware('throttle:12,60');
        Route::post('appeals/{type}/{id}', [Neighbors::class, 'appeal'])->whereNumber('id')->where('type', 'post|comment')->middleware('throttle:12,60');
    });
});
