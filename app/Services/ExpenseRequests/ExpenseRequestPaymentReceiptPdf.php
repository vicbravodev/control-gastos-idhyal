<?php

namespace App\Services\ExpenseRequests;

use App\Models\ExpenseReport;
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
        $expenseRequest->load([
            'expenseReports' => fn ($q) => $q->whereNotNull('cfdi_uuid')->orderBy('id'),
            'expenseReports.cfdiTraslados',
            'expenseReports.cfdiRetenciones',
            'expenseReports.cfdiImpuestosLocales',
        ]);

        $filename = 'recibo-pago-'.($expenseRequest->folio ?? $expenseRequest->id).'.pdf';

        $pdf = Pdf::loadView('pdf.expense-request-payment', [
            'expenseRequest' => $expenseRequest,
            'payment' => $payment,
            'cfdiSummaries' => self::buildCfdiSummaries($expenseRequest->expenseReports),
            'generatedAt' => now(),
        ]);

        $pdf->setPaper('a4');

        return $pdf->download($filename);
    }

    /**
     * @param  iterable<ExpenseReport>  $reports
     * @return list<array{folio: string, emisor: string, fecha: ?string, subtotal_cents: int, traslados_cents: int, retenciones_cents: int, locales_cents: int, total_cents: int}>
     */
    public static function buildCfdiSummaries(iterable $reports): array
    {
        $out = [];
        foreach ($reports as $report) {
            $out[] = self::summarizeReport($report);
        }

        return $out;
    }

    /**
     * @return array{folio: string, emisor: string, fecha: ?string, subtotal_cents: int, traslados_cents: int, retenciones_cents: int, locales_cents: int, total_cents: int}
     */
    private static function summarizeReport(ExpenseReport $report): array
    {
        $conceptos = is_array($report->cfdi_conceptos) ? $report->cfdi_conceptos : [];
        $subtotalCents = 0;
        foreach ($conceptos as $c) {
            $importe = is_array($c) ? ($c['importe'] ?? null) : null;
            if (is_numeric($importe)) {
                $subtotalCents += (int) round(((float) $importe) * 100);
            }
        }

        $folio = trim(((string) ($report->cfdi_serie ?? '')).' '.((string) ($report->cfdi_folio ?? '')));
        if ($folio === '') {
            $folio = (string) $report->cfdi_uuid;
        }

        return [
            'folio' => $folio,
            'emisor' => (string) ($report->cfdi_emisor_nombre ?? $report->cfdi_emisor_rfc ?? '—'),
            'fecha' => $report->cfdi_fecha?->format('d/m/Y'),
            'subtotal_cents' => $subtotalCents,
            'traslados_cents' => self::sumImpuestosCents($report->cfdiTraslados),
            'retenciones_cents' => self::sumImpuestosCents($report->cfdiRetenciones),
            'locales_cents' => (int) $report->cfdiImpuestosLocales->sum('importe_cents'),
            'total_cents' => (int) $report->reported_amount_cents,
        ];
    }

    /**
     * Sums document-level rows when present (the CFDI authoritative total),
     * falling back to per-concept rows to avoid double counting.
     *
     * @param  iterable<object>  $rows
     */
    private static function sumImpuestosCents(iterable $rows): int
    {
        $documentTotal = 0;
        $hasDocument = false;
        $conceptTotal = 0;

        foreach ($rows as $row) {
            $amount = (int) ($row->importe_cents ?? 0);
            if (($row->nivel ?? null) === 'document') {
                $documentTotal += $amount;
                $hasDocument = true;
            } else {
                $conceptTotal += $amount;
            }
        }

        return $hasDocument ? $documentTotal : $conceptTotal;
    }
}
