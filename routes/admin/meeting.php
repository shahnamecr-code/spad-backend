<?php

Route::get('advertisements/{advertisement}/meetings/user/{user}',[\App\Http\Controllers\MeetingController::class,'getUserMeeting']);
Route::post('advertisements/{advertisement}/meetings/user/{user}',[\App\Http\Controllers\MeetingController::class,'setMeeting']);

Route::get('my-meetings/{type?}',[\App\Http\Controllers\MeetingController::class,'myMeetings']);
Route::prefix('meeting')
    ->group(function (){
        Route::put('/{meeting}/cancel',[\App\Http\Controllers\MeetingController::class,'cancelMeeting']);
    });
