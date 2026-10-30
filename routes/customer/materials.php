<?php
use App\Http\Controllers\MaterialPricesController;
use Illuminate\Support\Facades\Route;
Route::get('materials/prices',[MaterialPricesController::class,'index'])->middleware('throttle:90,1');
