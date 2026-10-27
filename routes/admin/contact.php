<?php

Route::prefix('contact')
    ->controller(\App\Http\Controllers\Admin\ContactsController::class)
    ->group(function (){
        Route::get('/list','index');
    });
