<?php

namespace App\Services\ExpenseRequests;

use App\Enums\ExpenseReportStatus;
use App\Models\ExpenseRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ExpenseRequestExpenseReportVerificationReceiptPdf
{
    public function download(ExpenseRequest $expenseRequest): Response
    {
        $expenseRequest->loadMissing(['user', 'expenseConcept', 'expenseReport']);

        $report = $expenseRequest->expenseReport;
        if ($report === null) {
            throw new NotFoundHttpException;
        }

        if (! in_array($report->status, [
            ExpenseReportStatus::AccountingReview,
            ExpenseReportStatus::Approved,
        ], true)) {
            throw new NotFoundHttpException;
        }

        $filename = 'acuse-comprobacion-'.($expenseRequest->folio ?? $expenseRequest->id).'.pdf';

        $pdf = Pdf::loadView('pdf.expense-request-expense-report-verification', [
            'expenseRequest' => $expenseRequest,
            'expenseReport' => $report,
            'generatedAt' => now(),
        ]);

        $pdf->setPaper('a4');

        return $pdf->download($filename);
    }
}
