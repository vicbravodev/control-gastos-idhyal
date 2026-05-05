<?php

namespace App\Services\ExpenseReports;

use App\Enums\ExpenseReportDocumentType;
use App\Enums\ExpenseReportStatus;
use App\Enums\ExpenseRequestStatus;
use App\Models\ExpenseReport;
use App\Models\ExpenseRequest;
use App\Models\User;
use App\Services\ExpenseReports\Exceptions\InvalidExpenseReportException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class SaveExpenseReportDraft
{
    public function __construct(
        private readonly ExpenseReportAttachmentWriter $attachments,
        private readonly CfdiComprobanteIngestor $cfdiIngestor,
    ) {}

    /**
     * @throws InvalidExpenseReportException
     */
    public function save(
        ExpenseRequest $expenseRequest,
        User $actor,
        int $reportedAmountCents,
        ?UploadedFile $pdf,
        ?UploadedFile $xml,
        ?ExpenseReportDocumentType $documentType = null,
    ): ExpenseReport {
        if ($expenseRequest->user_id !== $actor->id) {
            throw new InvalidExpenseReportException(__('No puedes editar esta comprobación.'));
        }

        if (! in_array($expenseRequest->status, [
            ExpenseRequestStatus::AwaitingExpenseReport,
            ExpenseRequestStatus::ExpenseReportRejected,
        ], true)) {
            throw new InvalidExpenseReportException(__('La solicitud no admite comprobación en este momento.'));
        }

        $report = $expenseRequest->expenseReport;

        if ($report !== null && ! in_array($report->status, [
            ExpenseReportStatus::Draft,
            ExpenseReportStatus::Rejected,
        ], true)) {
            throw new InvalidExpenseReportException(__('La comprobación no puede editarse en su estado actual.'));
        }

        $resolvedType = $documentType ?? $report?->document_type ?? ExpenseReportDocumentType::Factura;
        $resolvedAmount = $reportedAmountCents;
        $cfdiAttributes = null;

        if ($xml !== null) {
            $ingestion = $this->cfdiIngestor->ingest($xml, $reportedAmountCents, $report);
            $resolvedAmount = $ingestion->resolvedAmountCents;
            $cfdiAttributes = $this->cfdiIngestor->metadataAttributes($ingestion->cfdi);
        }

        return DB::transaction(function () use ($expenseRequest, $actor, $resolvedAmount, $pdf, $xml, $resolvedType, $report, $cfdiAttributes): ExpenseReport {
            $payload = [
                'reported_amount_cents' => $resolvedAmount,
                'document_type' => $resolvedType,
            ];

            if ($cfdiAttributes !== null) {
                $payload = array_merge($payload, $cfdiAttributes);
            } elseif ($resolvedType === ExpenseReportDocumentType::Recibo) {
                $payload = array_merge($payload, [
                    'cfdi_uuid' => null,
                    'cfdi_emisor_rfc' => null,
                    'cfdi_emisor_nombre' => null,
                    'cfdi_receptor_rfc' => null,
                    'cfdi_receptor_nombre' => null,
                    'cfdi_fecha' => null,
                    'cfdi_serie' => null,
                    'cfdi_folio' => null,
                    'cfdi_forma_pago' => null,
                    'cfdi_metodo_pago' => null,
                    'cfdi_uso_cfdi' => null,
                    'cfdi_conceptos' => null,
                ]);
            }

            if ($report === null) {
                $report = ExpenseReport::query()->create(array_merge([
                    'expense_request_id' => $expenseRequest->id,
                    'status' => ExpenseReportStatus::Draft,
                    'submitted_at' => null,
                ], $payload));
            } else {
                $report->update($payload);
            }

            if ($resolvedType === ExpenseReportDocumentType::Recibo) {
                $this->attachments->removeKind($report->fresh(), 'xml');
            }

            if ($pdf !== null) {
                $this->attachments->storeKind($report->fresh(), $actor, $pdf, 'pdf');
            }

            if ($xml !== null) {
                $this->attachments->storeKind($report->fresh(), $actor, $xml, 'xml');
            }

            return $report->fresh(['attachments']);
        });
    }
}
