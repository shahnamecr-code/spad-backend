<?php

use App\Http\Controllers\PartnerPanelController;
use App\Http\Controllers\InvestorPanelController;
use App\Http\Controllers\PartnerInquiryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'partner.session'])->prefix('partner-panels')->group(function () {
    Route::get('', [PartnerPanelController::class, 'index']);
    Route::get('{partnerPanel}', [PartnerPanelController::class, 'show']);
});


Route::middleware(['auth:sanctum', 'partner.session', 'partner.panel'])
    ->prefix('partner-panels/{partnerPanel}/investor')
    ->group(function () {
        Route::get('dashboard', [InvestorPanelController::class, 'dashboard']);
        Route::get('opportunities', [InvestorPanelController::class, 'opportunities']);
        Route::get('opportunities/{advertisement}', [InvestorPanelController::class, 'showOpportunity']);
        Route::get('my-listings', [InvestorPanelController::class, 'myListings']);
        Route::post('submissions', [InvestorPanelController::class, 'storeSubmission'])->middleware('buy_script');
    });


Route::middleware(['auth:sanctum', 'partner.session', 'partner.panel'])
    ->prefix('partner-panels/{partnerPanel}/inquiries')
    ->group(function () {
        Route::get('dashboard', [PartnerInquiryController::class, 'dashboard']);
        Route::get('', [PartnerInquiryController::class, 'index']);
        Route::get('{inquiry}', [PartnerInquiryController::class, 'show']);
        Route::post('{inquiry}/accept', [PartnerInquiryController::class, 'accept']);
        Route::post('{inquiry}/request-correction', [PartnerInquiryController::class, 'requestCorrection']);
        Route::post('{inquiry}/complete', [PartnerInquiryController::class, 'complete']);
    });
