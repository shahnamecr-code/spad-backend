<?php
use App\Http\Controllers\OnlineRenovationController as R;
use App\Http\Controllers\RenovationAiController as Ai;
use Illuminate\Support\Facades\Route;
Route::get('online-renovation/projects',[R::class,'adminProjects']);
Route::patch('online-renovation/projects/{id}',[R::class,'adminUpdate'])->whereNumber('id');
Route::get('online-renovation/projects/{id}',[R::class,'adminProject'])->whereNumber('id');
Route::get('online-renovation/projects/{id}/media/{mediaId}',[R::class,'adminMedia'])->whereNumber('id')->whereNumber('mediaId');
Route::get('online-renovation/rates',[R::class,'adminRates']);
Route::put('online-renovation/rates/{code}',[R::class,'adminRate'])->where('code','[a-z0-9-]+');

Route::get('online-renovation/assets',[R::class,'adminAssets']);
Route::post('online-renovation/assets',[R::class,'adminCreateAsset']);
Route::put('online-renovation/assets/{id}',[R::class,'adminSaveAsset'])->whereNumber('id');
Route::delete('online-renovation/assets/{id}',[R::class,'adminDisableAsset'])->whereNumber('id');
Route::post('online-renovation/assets/{id}/reference',[Ai::class,'uploadReference'])->whereNumber('id')->middleware('throttle:12,1');
