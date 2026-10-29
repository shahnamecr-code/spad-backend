<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\InquiryController;
use App\Http\Controllers\ContractDocumentController;

Route::prefix('inquiries')->group(function () {
    Route::get('services', [InquiryController::class, 'services']);
    Route::get('services/{service}', [InquiryController::class, 'service']);
    Route::post('services', [InquiryController::class, 'saveService']);
    Route::post('services/{service}', [InquiryController::class, 'saveService']);
    Route::delete('services/{service}', [InquiryController::class, 'deleteService']);

    Route::get('requests', [InquiryController::class, 'requests']);
    Route::get('requests/{inquiry}', [InquiryController::class, 'request']);
    Route::get('requests/{inquiry}/contract-documents', [ContractDocumentController::class, 'adminIndex']);
    Route::get('requests/{inquiry}/contract-documents/{document}/download', [ContractDocumentController::class, 'adminDownload']);
    Route::post('requests/{inquiry}/assign', [InquiryController::class, 'assign']);
    Route::post('requests/{inquiry}/assign-partner', [InquiryController::class, 'assignPartner']);
    Route::post('requests/{inquiry}/complete', [InquiryController::class, 'complete']);
    Route::post('requests/{inquiry}/reject', [InquiryController::class, 'reject']);
    Route::post('requests/{inquiry}/retry-api', [InquiryController::class, 'retryApi']);
    Route::post('requests/{inquiry}/refund', [InquiryController::class, 'refund']);

    Route::get('seven-options', [InquiryController::class, 'sevenOptions']);
    Route::get('settings', [InquiryController::class, 'settings']);
    Route::post('settings', [InquiryController::class, 'saveSettings']);
});
