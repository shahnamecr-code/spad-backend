<?php

Route::prefix('expert-request')->group(function (){
    Route::get('/list',[\App\Http\Controllers\Admin\AdminExpertRequestController::class,'list']);
});
