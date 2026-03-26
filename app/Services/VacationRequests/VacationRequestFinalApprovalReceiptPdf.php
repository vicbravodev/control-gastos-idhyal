<?php

namespace App\Services\VacationRequests;

use App\Models\VacationRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class VacationRequestFinalApprovalReceiptPdf
{
    public function download(VacationRequest $vacationRequest): Response
    {
        $vacationRequest->load([
            'user',
            'approvals' => fn ($q) => $q->orderBy('step_order'),
            'approvals.role',
            'approvals.approver',
        ]);

        $filename = 'recibo-aprobacion-vacaciones-'.($vacationRequest->folio ?? $vacationRequest->id).'.pdf';

        $pdf = Pdf::loadView('pdf.vacation-request-final-approval', [
            'vacationRequest' => $vacationRequest,
            'generatedAt' => now(),
        ]);

        $pdf->setPaper('a4');

        return $pdf->download($filename);
    }
}
