<?php

Route::group([],function () {
    Route::controller(\App\Http\Controllers\Admin\DirectoryController::class)
        ->prefix('media')
        ->group(function (){
            Route::post('move','move');
            Route::delete('remove','remove');
            Route::get('get/{directory_id?}','getStructure');
        });
    Route::controller(\App\Http\Controllers\Admin\DirectoryController::class)
        ->prefix('directory')
        ->group(function () {
            Route::post('', 'store');
            Route::put('{directory}/rename', 'rename');
            Route::put('{directory}/move', 'move');
        });
    Route::controller(\App\Http\Controllers\Admin\FileController::class)
        ->prefix('file')
        ->group(function () {
            Route::post('/upload', 'upload');
            Route::put('{file}/rename', 'rename');
//            Route::put('{directory}/move', 'move');
        });
});
