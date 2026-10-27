<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\RaffleController;

Route::prefix('raffles')->group(function () {
    Route::get('/', [RaffleController::class, 'index']);
    Route::get('advertisements', [RaffleController::class, 'advertisements']);
    Route::post('upload', [RaffleController::class, 'upload']);

    Route::get('settings/all', [RaffleController::class, 'getSettings']);
    Route::post('settings/save', [RaffleController::class, 'saveSettings']);
    Route::post('gateways/save', [RaffleController::class, 'saveGateway']);
    Route::post('gateways/{gateway}/save', [RaffleController::class, 'saveGateway']);
    Route::delete('gateways/{gateway}', [RaffleController::class, 'deleteGateway']);
    Route::post('ticket-designs/save', [RaffleController::class, 'saveTicketDesign']);
    Route::post('ticket-designs/{design}/save', [RaffleController::class, 'saveTicketDesign']);
    Route::delete('ticket-designs/{design}', [RaffleController::class, 'deleteTicketDesign']);

    Route::post('create', [RaffleController::class, 'store']);
    Route::get('{raffle}', [RaffleController::class, 'show']);
    Route::post('{raffle}/edit', [RaffleController::class, 'update']);
    Route::delete('{raffle}', [RaffleController::class, 'delete']);
    Route::get('{raffle}/tickets', [RaffleController::class, 'tickets']);
    Route::get('{raffle}/orders', [RaffleController::class, 'orders']);
    Route::post('{raffle}/tickets/{ticket}/winner', [RaffleController::class, 'setWinner']);
    Route::post('{raffle}/winner/reset', [RaffleController::class, 'resetWinner']);
});
