<?php

use App\Http\Controllers\SpadHomeDoctorController;
use Illuminate\Support\Facades\Route;

Route::prefix('home-doctor')->group(function () {
    Route::get('categories', [SpadHomeDoctorController::class, 'categories']);
    Route::middleware(['auth:sanctum', 'throttle:90,1'])->group(function () {
        Route::get('cases', [SpadHomeDoctorController::class, 'index']);
        Route::post('cases', [SpadHomeDoctorController::class, 'store']);
        Route::get('cases/{case}', [SpadHomeDoctorController::class, 'show']);
        Route::delete('cases/{case}', [SpadHomeDoctorController::class, 'destroy']);
        Route::post('cases/{case}/media', [SpadHomeDoctorController::class, 'upload']);
        Route::get('cases/{case}/media/{media}', [SpadHomeDoctorController::class, 'photo']);
        Route::delete('cases/{case}/media/{media}', [SpadHomeDoctorController::class, 'removePhoto']);
        Route::post('cases/{case}/analysis', [SpadHomeDoctorController::class, 'analyze']);
        Route::post('cases/{case}/follow-ups', [SpadHomeDoctorController::class, 'followUp']);
        Route::put('cases/{case}/feedback', [SpadHomeDoctorController::class, 'feedback']);
    });
});
