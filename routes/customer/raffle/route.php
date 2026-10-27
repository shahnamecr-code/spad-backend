<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RaffleController;
use App\Http\Controllers\RafflePaymentController;

Route::prefix('raffles')->group(function () {
    Route::get('', [RaffleController::class, 'index']);
    Route::get('active', [RaffleController::class, 'active']);
    Route::get('history', [RaffleController::class, 'history']);
    Route::get('gateways', [RaffleController::class, 'gateways']);
    Route::get('{slug}', [RaffleController::class, 'show']);
});

Route::match(['get', 'post'], 'raffle-payments/callback/{order}/{token}', [RafflePaymentController::class, 'callback']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('my-raffle-tickets', [RaffleController::class, 'myTickets']);
    Route::post('raffles/{raffle}/purchase', [RaffleController::class, 'purchase']);
    Route::post('raffles/{raffle}/purchase-wallet', [RaffleController::class, 'purchaseWithWallet']);
    Route::get('raffle-orders/{order}/status', [RaffleController::class, 'orderStatus']);
});
