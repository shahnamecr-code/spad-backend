<?php

use App\Http\Controllers\SpadHomeEnergyController as Energy;
use Illuminate\Support\Facades\Route;

Route::prefix('home-energy')->group(function () {
    Route::get('capabilities', [Energy::class, 'capabilities']);
    Route::middleware(['auth:sanctum', 'throttle:90,1'])->group(function () {
        Route::get('dashboard', [Energy::class, 'dashboard']);
        Route::post('locations', [Energy::class, 'storeLocation']);
        Route::patch('locations/{id}', [Energy::class, 'updateLocation'])->whereNumber('id');
        Route::delete('locations/{id}', [Energy::class, 'deleteLocation'])->whereNumber('id');
        Route::post('accounts', [Energy::class, 'storeAccount']);
        Route::get('accounts/{id}', [Energy::class, 'account'])->whereNumber('id');
        Route::patch('accounts/{id}', [Energy::class, 'updateAccount'])->whereNumber('id');
        Route::delete('accounts/{id}', [Energy::class, 'deleteAccount'])->whereNumber('id');
        Route::post('accounts/{id}/bills', [Energy::class, 'storeBill'])->whereNumber('id');
        Route::patch('bills/{id}', [Energy::class, 'updateBill'])->whereNumber('id');
        Route::delete('bills/{id}', [Energy::class, 'deleteBill'])->whereNumber('id');
        Route::post('bills/{id}/receipt', [Energy::class, 'uploadReceipt'])->whereNumber('id');
        Route::get('bills/{id}/receipt', [Energy::class, 'receipt'])->whereNumber('id');
        Route::delete('bills/{id}/receipt', [Energy::class, 'deleteReceipt'])->whereNumber('id');
        Route::get('building-sources', [Energy::class, 'buildingSources']);
        Route::post('building-import', [Energy::class, 'importBuilding']);
    });
});
