<?php

use App\Http\Controllers\VacationRequests\VacationRequestApprovalController;
use App\Http\Controllers\VacationRequests\VacationRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('vacation-requests/approvals/pending', [VacationRequestApprovalController::class, 'pending'])
        ->name('vacation-requests.approvals.pending');

    Route::post('vacation-requests/{vacation_request}/approvals/{approval}/approve', [VacationRequestApprovalController::class, 'approve'])
        ->name('vacation-requests.approvals.approve');

    Route::post('vacation-requests/{vacation_request}/approvals/{approval}/reject', [VacationRequestApprovalController::class, 'reject'])
        ->name('vacation-requests.approvals.reject');

    Route::get('vacation-requests/{vacation_request}/receipts/final-approval', [VacationRequestController::class, 'downloadFinalApprovalReceipt'])
        ->name('vacation-requests.receipts.final-approval');

    Route::resource('vacation-requests', VacationRequestController::class)->only(['index', 'create', 'store', 'show']);
});
