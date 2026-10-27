<?php

Route::middleware('auth:sanctum')->group(function (){
    Route::post('advertisement/{advertisement}/meeting',[\App\Http\Controllers\MeetingController::class,'addMeeting'])->middleware('buy_script');

    // User profile - visiting requests
    Route::put('my-meetings/{meeting}',[\App\Http\Controllers\MeetingController::class,'updateMyMeeting']);
    Route::put('my-meetings/{meeting}/cancel',[\App\Http\Controllers\MeetingController::class,'cancelMyMeeting']);
});
