<?php

namespace App\Services\ExpenseRequests;

use App\Models\ExpenseRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ExpenseRequestPaymentReceiptPdf
{
    public function download(ExpenseRequest $expenseRequest): Response
    {
        $payment = $expenseRequest->payments()->with(['recordedBy', 'expenseRequest.user'])->orderBy('id')->first();
        if ($payment === null) {
            throw new NotFoundHttpException;
        }

        $expenseRequest->loadMissing(['user', 'expenseConcept']);

        $filename = 'recibo-pago-'.($expenseRequest->folio ?? $expenseRequest->id).'.pdf';

        $pdf = Pdf::loadView('pdf.expense-request-payment', [
            'expenseRequest' => $expenseRequest,
            'payment' => $payment,
            'generatedAt' => now(),
        ]);

        $pdf->setPaper('a4');

        return $pdf->download($filename);
    }
}
