<?php
use App\Http\Controllers\SpadIdentityController as Identity;
Route::middleware('auth:sanctum')->prefix('identity')->group(function(){
 Route::get('status',[Identity::class,'status'])->middleware('throttle:30,1');
 Route::post('start',[Identity::class,'start'])->middleware('throttle:4,60');
 Route::post('phone/send',[Identity::class,'sendPhone'])->middleware('throttle:5,60');
 Route::post('phone/verify',[Identity::class,'verifyPhone'])->middleware('throttle:10,10');
 Route::post('birth-date',[Identity::class,'updateBirthDate'])->middleware('throttle:4,60');
 Route::post('person-info',[Identity::class,'personInfo'])->middleware('throttle:3,60');
 Route::post('shahkar',[Identity::class,'shahkar'])->middleware('throttle:5,60');
 Route::post('documents',[Identity::class,'upload'])->middleware('throttle:12,60');
 Route::post('submit',[Identity::class,'submit'])->middleware('throttle:5,60');
});
