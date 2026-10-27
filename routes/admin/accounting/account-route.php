<?php

Route::prefix('accounts')->group(function(){
    Route::get('/',[\App\Http\Controllers\Admin\Accounting\AccountsController::class,'index']);
    Route::post('/create',[\App\Http\Controllers\Admin\Accounting\AccountsController::class,'store']);
    Route::post('/{account}/edit',[\App\Http\Controllers\Admin\Accounting\AccountsController::class,'update']);
    Route::get('/{account}',[\App\Http\Controllers\Admin\Accounting\AccountsController::class,'show']);
});
