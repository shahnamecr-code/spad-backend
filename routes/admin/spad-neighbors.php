<?php

use App\Http\Controllers\Admin\SpadNeighborsController as Neighbors;
use Illuminate\Support\Facades\Route;

Route::prefix('neighbors')->group(function () {
    Route::get('media/{id}', [Neighbors::class, 'media'])->whereNumber('id');
    Route::get('{group}', [Neighbors::class, 'index'])->where('group', 'posts|comments|reports|appeals|residencies|audit');
    Route::patch('{group}/{id}', [Neighbors::class, 'moderate'])->whereNumber('id')->where('group', 'posts|comments|reports|appeals|residencies');
});
