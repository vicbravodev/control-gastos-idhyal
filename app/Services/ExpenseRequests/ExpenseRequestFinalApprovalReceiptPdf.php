<?php

namespace App\Services\ExpenseRequests;

use App\Models\ExpenseRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class ExpenseRequestFinalApprovalReceiptPdf
{
    public function download(ExpenseRequest $expenseRequest): Response
    {
        $expenseRequest->load([
            'user',
            'expenseConcept',
            'approvals' => fn ($q) => $q->orderBy('step_order'),
            'approvals.role',
            'approvals.approver',
        ]);

        $filename = 'recibo-aprobacion-'.($expenseRequest->folio ?? $expenseRequest->id).'.pdf';

        $pdf = Pdf::loadView('pdf.expense-request-final-approval', [
            'expenseRequest' => $expenseRequest,
            'generatedAt' => now(),
        ]);

        $pdf->setPaper('a4');

        return $pdf->download($filename);
    }
}
