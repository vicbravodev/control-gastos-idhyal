<?php

namespace App\Services\ExpenseRequests;

use App\Models\ExpenseRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class ExpenseRequestSubmissionReceiptPdf
{
    public function download(ExpenseRequest $expenseRequest): Response
    {
        $expenseRequest->load(['user', 'expenseConcept', 'approvals.role']);

        $filename = 'acuse-solicitud-'.($expenseRequest->folio ?? $expenseRequest->id).'.pdf';

        $pdf = Pdf::loadView('pdf.expense-request-submission', [
            'expenseRequest' => $expenseRequest,
            'generatedAt' => now(),
        ]);

        $pdf->setPaper('a4');

        return $pdf->download($filename);
    }
}
