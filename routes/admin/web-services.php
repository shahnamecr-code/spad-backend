<?php
use App\Http\Controllers\Admin\WebServiceRegistryController as Registry;
Route::prefix('web-services')->middleware('throttle:45,1')->group(function () {
 Route::get('/',[Registry::class,'index']);
 Route::post('/',[Registry::class,'upsert']);
 Route::put('/{id}',[Registry::class,'update'])->whereNumber('id');
 Route::post('/{id}/check',[Registry::class,'check'])->whereNumber('id');
 Route::delete('/{id}',[Registry::class,'destroy'])->whereNumber('id');
});
