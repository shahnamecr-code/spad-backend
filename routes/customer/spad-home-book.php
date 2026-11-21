<?php

use App\Http\Controllers\SpadHomeBookController as Book;
use Illuminate\Support\Facades\Route;

Route::prefix('home-book')->middleware(['auth:sanctum', 'throttle:75,1'])->group(function () {
    Route::get('/', [Book::class, 'index']);
    Route::post('homes', [Book::class, 'createHome']);
    Route::patch('homes/{home}', [Book::class, 'updateHome'])->whereNumber('home');
    Route::delete('homes/{home}', [Book::class, 'removeHome'])->whereNumber('home');
    Route::post('devices', [Book::class, 'createDevice']);
    Route::post('label', [Book::class, 'readLabel'])->middleware('throttle:3,60,spad-homebook-label');
    Route::prefix('devices/{device}')->where(['device' => '[0-9]+'])->group(function () {
        Route::get('/', [Book::class, 'show']);
        Route::patch('/', [Book::class, 'updateDevice']);
        Route::delete('/', [Book::class, 'removeDevice']);
        Route::post('documents', [Book::class, 'upload'])->middleware('throttle:12,1,spad-homebook-documents');
        Route::get('documents/{document}', [Book::class, 'document'])->whereNumber('document');
        Route::delete('documents/{document}', [Book::class, 'removeDocument'])->whereNumber('document');
        Route::post('maintenance', [Book::class, 'addMaintenance']);
        Route::patch('maintenance/{item}', [Book::class, 'completeMaintenance'])->whereNumber('item');
        Route::delete('maintenance/{item}', [Book::class, 'removeMaintenance'])->whereNumber('item');
    });
});
