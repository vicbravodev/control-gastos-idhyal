<?php

namespace App\Services\ExpenseRequests;

use App\Models\Attachment;
use App\Models\ExpenseRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ExpenseRequestSettlementLiquidationReceiptPdf
{
    public function download(ExpenseRequest $expenseRequest): Response
    {
        $expenseRequest->loadMissing(['user', 'expenseConcept', 'expenseReport.settlement']);

        $settlement = $expenseRequest->expenseReport?->settlement;
        if ($settlement === null) {
            throw new NotFoundHttpException;
        }

        $settlement->load(['attachments.uploadedBy']);

        /** @var Attachment|null $evidence */
        $evidence = $settlement->attachments->sortBy('id')->first();
        if ($evidence === null) {
            throw new NotFoundHttpException;
        }

        $filename = 'recibo-liquidacion-'.($expenseRequest->folio ?? $expenseRequest->id).'.pdf';

        $pdf = Pdf::loadView('pdf.expense-request-settlement-liquidation', [
            'expenseRequest' => $expenseRequest,
            'settlement' => $settlement,
            'evidence' => $evidence,
            'generatedAt' => now(),
        ]);

        $pdf->setPaper('a4');

        return $pdf->download($filename);
    }
}
