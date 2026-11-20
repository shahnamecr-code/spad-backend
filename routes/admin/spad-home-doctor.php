<?php

use App\Http\Controllers\Admin\SpadHomeDoctorController;
use Illuminate\Support\Facades\Route;

Route::prefix('home-doctor')->group(function () {
    Route::get('summary', [SpadHomeDoctorController::class, 'index']);
    Route::put('categories/{category}', [SpadHomeDoctorController::class, 'updateCategory']);
});
