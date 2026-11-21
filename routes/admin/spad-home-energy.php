<?php

use App\Http\Controllers\Admin\SpadHomeEnergyController;
use Illuminate\Support\Facades\Route;

Route::get('home-energy/summary', [SpadHomeEnergyController::class, 'summary']);
