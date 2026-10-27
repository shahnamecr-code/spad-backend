<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\InquiryPaymentController;

Route::get('inquiries', [InquiryController::class, 'index']);
Route::get('inquiries/{service:slug}', [InquiryController::class, 'show']);
Route::match(['get', 'post'], 'inquiry-payments/callback/{inquiry}/{token}', [InquiryPaymentController::class, 'callback']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('inquiry-payment-options', [InquiryController::class, 'paymentOptions']);
    Route::post('inquiries/{service:slug}/submit', [InquiryController::class, 'store']);
    Route::get('my-inquiries', [InquiryController::class, 'myRequests']);
    Route::get('my-inquiries/{reference}', [InquiryController::class, 'requestStatus']);
    Route::post('my-inquiries/{reference}/correct', [InquiryController::class, 'correctInputs']);
    Route::get('my-inquiries/{reference}/result-file', [InquiryController::class, 'downloadResultFile']);
});
