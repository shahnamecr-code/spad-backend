<?php

use App\Http\Controllers\Admin\SpadHomeServicesController;
use App\Http\Middleware\SpadHomeServiceAdministrator;
use Illuminate\Support\Facades\Route;

Route::middleware(SpadHomeServiceAdministrator::class)->group(function () {
    Route::get('home-services', [SpadHomeServicesController::class, 'index']);
    Route::put('home-services/{service}', [SpadHomeServicesController::class, 'update']);
    require __DIR__ . '/spad-neighbors.php';
    require __DIR__ . '/spad-home-doctor.php';
    require __DIR__ . '/spad-home-league.php';
});
