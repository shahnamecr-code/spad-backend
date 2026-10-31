<?php
use App\Http\Controllers\OnlineRenovationController as R;
use Illuminate\Support\Facades\Route;
Route::get('online-renovation/projects',[R::class,'adminProjects']);
Route::patch('online-renovation/projects/{id}',[R::class,'adminUpdate'])->whereNumber('id');
Route::get('online-renovation/rates',[R::class,'adminRates']);
Route::put('online-renovation/rates/{code}',[R::class,'adminRate'])->where('code','[a-z0-9-]+');
