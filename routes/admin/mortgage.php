<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MortgageController;

Route::get('mortgage/settings', [MortgageController::class, 'adminSettings']);
Route::post('mortgage/settings', [MortgageController::class, 'updateSettings']);
Route::get('mortgage/requests', [MortgageController::class, 'adminRequests']);
Route::patch('mortgage/requests/{id}', [MortgageController::class, 'updateRequest']);
