<?php
use App\Http\Controllers\OnlineRenovationController as R;
use Illuminate\Support\Facades\Route;
Route::prefix('online-renovation')->group(function(){
 Route::get('catalog',[R::class,'catalog']);Route::get('rates',[R::class,'rates']);
 Route::middleware('auth:sanctum')->group(function(){
  Route::get('mine',[R::class,'mine']);Route::post('projects',[R::class,'create'])->middleware('throttle:6,1');
  Route::get('{id}',[R::class,'show'])->whereNumber('id');
  Route::put('{id}',[R::class,'update'])->whereNumber('id')->middleware('throttle:20,1');
  Route::post('{id}/images',[R::class,'upload'])->whereNumber('id')->middleware('throttle:12,1');
  Route::get('{id}/media/{mediaId}',[R::class,'media'])->whereNumber('id')->whereNumber('mediaId');
  Route::post('{id}/finalize',[R::class,'finalize'])->whereNumber('id')->middleware('throttle:4,1');
  Route::post('{id}/outputs',[R::class,'export'])->whereNumber('id')->middleware('throttle:8,1');
 });
});
