<?php

use App\Http\Controllers\SpadHomeLeagueController as League;
use Illuminate\Support\Facades\Route;

Route::prefix('home-league')->group(function () {
    Route::get('/', [League::class, 'index']);
    Route::get('my-entries', [League::class, 'myEntries'])->middleware('auth:sanctum');
    Route::get('my-entries/{id}', [League::class, 'myEntry'])->whereNumber('id')->middleware('auth:sanctum');
    Route::get('my-entries/{id}/photos/{photo}', [League::class, 'myPhoto'])->whereNumber('id')->whereNumber('photo')->middleware('auth:sanctum');
    Route::get('{slug}', [League::class, 'show']);
    Route::get('{slug}/entries/{id}', [League::class, 'entry'])->whereNumber('id');
    Route::get('{slug}/entries/{id}/photos/{photo}', [League::class, 'photo'])->whereNumber('id')->whereNumber('photo');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('{slug}/my-vote', [League::class, 'myVote']);
        Route::post('{slug}/entries', [League::class, 'submit'])->middleware('throttle:5,60,home-league-submit');
        Route::post('{slug}/entries/{id}', [League::class, 'edit'])->whereNumber('id')->middleware('throttle:10,60,home-league-edit');
        Route::delete('{slug}/entries/{id}', [League::class, 'withdraw'])->whereNumber('id');
        Route::post('{slug}/entries/{id}/vote', [League::class, 'vote'])->whereNumber('id')->middleware('throttle:12,1,home-league-vote');
        Route::post('{slug}/entries/{id}/report', [League::class, 'report'])->whereNumber('id')->middleware('throttle:6,60,home-league-report');
    });
});
