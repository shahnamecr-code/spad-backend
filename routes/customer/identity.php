<?php
use App\Http\Controllers\SpadIdentityController as Identity;

// Version 4: no PersonInfo endpoint and no silent fallback to old Shahkar.
Route::middleware('auth:sanctum')->prefix('identity')->group(function(){
 Route::get('status',[Identity::class,'status'])->middleware('throttle:40,1');
 Route::post('start',[Identity::class,'start'])->middleware('throttle:15,1');
 Route::post('phone/send',[Identity::class,'sendPhone'])->middleware('throttle:12,1');
 Route::post('phone/verify',[Identity::class,'verifyPhone'])->middleware('throttle:20,1');
 // This billable route has its own per-user quotas + atomic DB reservation.
 Route::post('shahkar-pro',[Identity::class,'shahkarPro']);
 // Deprecated routes reject with 410 and never incur a paid API request.
 Route::post('shahkar',[Identity::class,'legacyEndpoint']);
 Route::post('person-info',[Identity::class,'legacyEndpoint']);
 Route::post('birth-date',[Identity::class,'legacyEndpoint']);
 Route::post('documents',[Identity::class,'legacyEndpoint']);
 Route::post('submit',[Identity::class,'legacyEndpoint']);
});
