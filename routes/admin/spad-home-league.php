<?php

use App\Http\Controllers\SpadHomeLeagueController as League;
use Illuminate\Support\Facades\Route;

// Included in the API's authenticated admin route group.
Route::prefix('home-league')->group(function () {
    Route::get('contests', [League::class, 'adminContests']);
    Route::post('contests', [League::class, 'adminCreate']);
    Route::put('contests/{id}', [League::class, 'adminUpdate'])->whereNumber('id');
    Route::get('contests/{id}/review', [League::class, 'adminReview'])->whereNumber('id');
    Route::post('contests/{id}/freeze', [League::class, 'adminFreeze'])->whereNumber('id');
    Route::post('entries/{id}/moderate', [League::class, 'adminModerate'])->whereNumber('id');
    Route::get('entries/{id}/photos/{photo}', [League::class, 'adminPhoto'])->whereNumber('id')->whereNumber('photo');
    Route::post('votes/{id}/invalidate', [League::class, 'adminInvalidate'])->whereNumber('id');
    Route::post('reports/{id}/resolve', [League::class, 'adminResolveReport'])->whereNumber('id');
});
