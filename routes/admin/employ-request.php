<?php

Route::prefix('employ-request')->group(function (){
    Route::get('/list',[\App\Http\Controllers\Admin\AdminEmployRequestController::class,'list']);
});
