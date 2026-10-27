<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\WalletController;

Route::prefix('wallets')->group(function () {
    Route::get('/', [WalletController::class, 'index']);
    Route::get('stats', [WalletController::class, 'stats']);
    Route::get('transactions/all', [WalletController::class, 'transactions']);

    Route::get('settings/all', [WalletController::class, 'settings']);
    Route::post('settings/save', [WalletController::class, 'saveSettings']);
    Route::post('gateways/save', [WalletController::class, 'saveGateway']);
    Route::post('gateways/{gateway}/save', [WalletController::class, 'saveGateway']);
    Route::delete('gateways/{gateway}', [WalletController::class, 'deleteGateway']);

    Route::get('{user}', [WalletController::class, 'show']);
    Route::post('{user}/adjust', [WalletController::class, 'adjust']);
    Route::post('{user}/status', [WalletController::class, 'setStatus']);
});
