<?php

use App\Http\Controllers\Admin\PartnerPanelController;
use App\Http\Controllers\Admin\InvestorListingController;
use Illuminate\Support\Facades\Route;

Route::prefix('partner-panels')->middleware('role:Super Admin,admin')->group(function () {
    Route::get('meta', [PartnerPanelController::class, 'meta']);
    Route::get('', [PartnerPanelController::class, 'index']);
    Route::post('', [PartnerPanelController::class, 'store']);
    Route::get('{partnerPanel}', [PartnerPanelController::class, 'show']);
    Route::put('{partnerPanel}', [PartnerPanelController::class, 'update']);
    Route::delete('{partnerPanel}', [PartnerPanelController::class, 'destroy']);
});


Route::prefix('investor-listings')->middleware('role:Super Admin,admin')->group(function () {
    Route::get('meta', [InvestorListingController::class, 'meta']);
    Route::get('', [InvestorListingController::class, 'index']);
    Route::post('', [InvestorListingController::class, 'store']);
    Route::put('{investorListing}', [InvestorListingController::class, 'update']);
    Route::post('{investorListing}/approve', [InvestorListingController::class, 'approve']);
    Route::post('{investorListing}/reject', [InvestorListingController::class, 'reject']);
    Route::delete('{investorListing}', [InvestorListingController::class, 'destroy']);
});
