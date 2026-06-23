<?php

use App\Http\Controllers\Reports\ExpenseAnalyticsController;
use App\Http\Controllers\Reports\ReportExportController;
use App\Http\Controllers\Reports\ReportScheduleController;
use App\Http\Controllers\Reports\ReportTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('reports/expenses', [ExpenseAnalyticsController::class, 'index'])
        ->name('reports.expenses.index');

    Route::get('reports/expenses/export/{format}', ReportExportController::class)
        ->whereIn('format', ['pdf', 'csv'])
        ->name('reports.expenses.export');

    // Backward-compatible alias for the old PDF-only route.
    Route::get('reports/expenses/export-pdf', fn (\Illuminate\Http\Request $r, \App\Services\Reports\PeriodPresetResolver $p, \App\Services\Reports\ExpenseAggregator $a) => app(ReportExportController::class)($r, 'pdf', $p, $a))
        ->name('reports.expenses.export-pdf');

    Route::post('reports/templates', [ReportTemplateController::class, 'store'])
        ->name('reports.templates.store');
    Route::patch('reports/templates/{template}', [ReportTemplateController::class, 'update'])
        ->name('reports.templates.update');
    Route::delete('reports/templates/{template}', [ReportTemplateController::class, 'destroy'])
        ->name('reports.templates.destroy');

    Route::post('reports/schedules', [ReportScheduleController::class, 'store'])
        ->name('reports.schedules.store');
    Route::patch('reports/schedules/{schedule}', [ReportScheduleController::class, 'update'])
        ->name('reports.schedules.update');
    Route::delete('reports/schedules/{schedule}', [ReportScheduleController::class, 'destroy'])
        ->name('reports.schedules.destroy');
});
