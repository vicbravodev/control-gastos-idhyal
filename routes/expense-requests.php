<?php

use App\Http\Controllers\ExpenseRequests\ExpenseReportController;
use App\Http\Controllers\ExpenseRequests\ExpenseRequestApprovalController;
use App\Http\Controllers\ExpenseRequests\ExpenseRequestController;
use App\Http\Controllers\ExpenseRequests\ExpenseRequestPaymentController;
use App\Http\Controllers\ExpenseRequests\ExpenseRequestSettlementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('expense-requests/expense-reports/pending-review', [ExpenseReportController::class, 'pendingReview'])
        ->name('expense-requests.expense-reports.pending-review');

    Route::post('expense-requests/{expenseRequest}/expense-report/draft', [ExpenseReportController::class, 'storeDraft'])
        ->name('expense-requests.expense-report.draft');

    Route::post('expense-requests/{expenseRequest}/expense-report/submit', [ExpenseReportController::class, 'submit'])
        ->name('expense-requests.expense-report.submit');

    Route::post('expense-requests/{expenseRequest}/expense-report/approve', [ExpenseReportController::class, 'approve'])
        ->name('expense-requests.expense-report.approve');

    Route::post('expense-requests/{expenseRequest}/expense-report/reject', [ExpenseReportController::class, 'reject'])
        ->name('expense-requests.expense-report.reject');

    Route::post('expense-requests/{expenseRequest}/settlement/liquidation', [ExpenseRequestSettlementController::class, 'storeLiquidation'])
        ->name('expense-requests.settlement.liquidation.store');

    Route::post('expense-requests/{expenseRequest}/settlement/close', [ExpenseRequestSettlementController::class, 'close'])
        ->name('expense-requests.settlement.close');

    Route::get('expense-requests/settlements/pending-balances', [ExpenseRequestSettlementController::class, 'pendingBalances'])
        ->name('expense-requests.settlements.pending-balances');

    Route::get('expense-requests/payments/pending', [ExpenseRequestPaymentController::class, 'pending'])
        ->name('expense-requests.payments.pending');

    Route::post('expense-requests/{expenseRequest}/payments', [ExpenseRequestPaymentController::class, 'store'])
        ->name('expense-requests.payments.store');

    Route::post('expense-requests/{expenseRequest}/cancel', [ExpenseRequestController::class, 'cancel'])
        ->name('expense-requests.cancel');

    Route::post('expense-requests/{expense_request}/submission-attachments', [ExpenseRequestController::class, 'storeSubmissionAttachments'])
        ->name('expense-requests.submission-attachments.store');

    Route::delete('expense-requests/{expense_request}/submission-attachments/{attachment}', [ExpenseRequestController::class, 'destroySubmissionAttachment'])
        ->name('expense-requests.submission-attachments.destroy');

    Route::get('expense-requests/{expense_request}/submission-attachments/{attachment}', [ExpenseRequestController::class, 'downloadSubmissionAttachment'])
        ->name('expense-requests.submission-attachments.download');

    Route::get('expense-requests/approvals/pending', [ExpenseRequestApprovalController::class, 'pending'])
        ->name('expense-requests.approvals.pending');

    Route::post('expense-requests/{expenseRequest}/approvals/{approval}/approve', [ExpenseRequestApprovalController::class, 'approve'])
        ->name('expense-requests.approvals.approve');

    Route::post('expense-requests/{expenseRequest}/approvals/{approval}/reject', [ExpenseRequestApprovalController::class, 'reject'])
        ->name('expense-requests.approvals.reject');

    Route::get('expense-requests/{expense_request}/receipts/submission', [ExpenseRequestController::class, 'downloadSubmissionReceipt'])
        ->name('expense-requests.receipts.submission');

    Route::get('expense-requests/{expense_request}/receipts/final-approval', [ExpenseRequestController::class, 'downloadFinalApprovalReceipt'])
        ->name('expense-requests.receipts.final-approval');

    Route::get('expense-requests/{expense_request}/receipts/payment', [ExpenseRequestController::class, 'downloadPaymentReceipt'])
        ->name('expense-requests.receipts.payment');

    Route::get('expense-requests/{expense_request}/receipts/settlement-liquidation', [ExpenseRequestController::class, 'downloadSettlementLiquidationReceipt'])
        ->name('expense-requests.receipts.settlement-liquidation');

    Route::get('expense-requests/{expense_request}/receipts/expense-report-verification', [ExpenseRequestController::class, 'downloadExpenseReportVerificationReceipt'])
        ->name('expense-requests.receipts.expense-report-verification');

    Route::get('expense-requests/{expense_request}/expense-reports/verification/{attachment}', [ExpenseRequestController::class, 'downloadExpenseReportVerificationAttachment'])
        ->name('expense-requests.expense-reports.verification-attachment');

    Route::get('expense-requests/{expense_request}/settlements/liquidation-evidence/{attachment}', [ExpenseRequestController::class, 'downloadSettlementLiquidationEvidence'])
        ->name('expense-requests.settlements.liquidation-evidence');

    Route::get('expense-requests/{expense_request}/payments/payment-evidence/{attachment}', [ExpenseRequestController::class, 'downloadPaymentEvidence'])
        ->name('expense-requests.payments.payment-evidence');

    Route::resource('expense-requests', ExpenseRequestController::class)->except(['destroy']);
});
