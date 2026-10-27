<?php

Route::prefix('payments')->group(function (){
    Route::get('/',[\App\Http\Controllers\Admin\Accounting\PaymentsController::class,'index']);
    Route::get('/document',[\App\Http\Controllers\Admin\Accounting\PaymentsController::class,'search_document']);
    Route::post('/create',[\App\Http\Controllers\Admin\Accounting\PaymentsController::class,'store']);
    Route::post('/{payment}/edit',[\App\Http\Controllers\Admin\Accounting\PaymentsController::class,'update']);
    Route::delete('/{payment}/delete',[\App\Http\Controllers\Admin\Accounting\PaymentsController::class,'delete']);
    Route::get('/{payment}',[\App\Http\Controllers\Admin\Accounting\PaymentsController::class,'show']);
});
