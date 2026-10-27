<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WalletPaymentController;

Route::match(['get', 'post'], 'wallet-payments/callback/{topup}/{token}', [WalletPaymentController::class, 'callback']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('wallet', [WalletController::class, 'summary']);
    Route::get('wallet/transactions', [WalletController::class, 'transactions']);
    Route::post('wallet/top-up', [WalletController::class, 'topUp']);
    Route::get('wallet/top-ups/{topup}', [WalletController::class, 'topUpStatus']);
});
