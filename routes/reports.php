<?php

use App\Http\Controllers\Reports\ExpenseAnalyticsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('reports/expenses', [ExpenseAnalyticsController::class, 'index'])
        ->name('reports.expenses.index');

    Route::get('reports/expenses/export-pdf', [ExpenseAnalyticsController::class, 'exportPdf'])
        ->name('reports.expenses.export-pdf');
});
