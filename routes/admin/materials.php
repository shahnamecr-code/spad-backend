<?php
use App\Http\Controllers\MaterialPricesController;
use Illuminate\Support\Facades\Route;
Route::get('materials/prices',[MaterialPricesController::class,'adminIndex']);
Route::post('materials/prices/settings',[MaterialPricesController::class,'adminSaveSettings']);
Route::post('materials/prices/items',[MaterialPricesController::class,'adminSaveProduct']);
Route::put('materials/prices/items/{id}',[MaterialPricesController::class,'adminSaveProduct'])->whereNumber('id');
Route::get('materials/prices/items/{id}/history',[MaterialPricesController::class,'adminHistory'])->whereNumber('id');
Route::post('materials/prices/sync',[MaterialPricesController::class,'adminSync'])->middleware('throttle:3,1');
