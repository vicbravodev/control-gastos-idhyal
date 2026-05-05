<?php

namespace App\Services\ExpenseReports;

use App\Enums\DocumentEventType;
use App\Enums\ExpenseReportDocumentType;
use App\Enums\ExpenseReportStatus;
use App\Enums\ExpenseRequestStatus;
use App\Models\DocumentEvent;
use App\Models\ExpenseReport;
use App\Models\ExpenseRequest;
use App\Models\User;
use App\Services\ExpenseReports\Exceptions\InvalidExpenseReportException;
use App\Services\ExpenseRequests\ExpenseRequestNotificationDispatcher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class SubmitExpenseReportForReview
{
    public function __construct(
        private readonly ExpenseReportAttachmentWriter $attachments,
        private readonly ExpenseRequestNotificationDispatcher $notifications,
        private readonly CfdiComprobanteIngestor $cfdiIngestor,
    ) {}

    /**
     * @throws InvalidExpenseReportException
     */
    public function submit(
        ExpenseRequest $expenseRequest,
        User $actor,
        int $reportedAmountCents,
        UploadedFile $pdf,
        ?UploadedFile $xml,
        ExpenseReportDocumentType $documentType,
    ): ExpenseReport {
        if ($expenseRequest->user_id !== $actor->id) {
            throw new InvalidExpenseReportException(__('No puedes enviar esta comprobación.'));
        }

        if (! in_array($expenseRequest->status, [
            ExpenseRequestStatus::AwaitingExpenseReport,
            ExpenseRequestStatus::ExpenseReportRejected,
        ], true)) {
            throw new InvalidExpenseReportException(__('La solicitud no admite envío de comprobación en este momento.'));
        }

        if (! $expenseRequest->payments()->exists()) {
            throw new InvalidExpenseReportException(__('La solicitud no tiene pago registrado.'));
        }

        if ($documentType === ExpenseReportDocumentType::Factura && $xml === null) {
            throw new InvalidExpenseReportException(__('Debes adjuntar el XML del CFDI cuando la comprobación es una factura.'));
        }

        $report = $expenseRequest->expenseReport;

        if ($report !== null && ! in_array($report->status, [
            ExpenseReportStatus::Draft,
            ExpenseReportStatus::Rejected,
        ], true)) {
            throw new InvalidExpenseReportException(__('La comprobación ya fue enviada o cerrada.'));
        }

        $resolvedAmount = $reportedAmountCents;
        $cfdiAttributes = [
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
        ];

        if ($xml !== null) {
            $ingestion = $this->cfdiIngestor->ingest($xml, $reportedAmountCents, $report);
            $resolvedAmount = $ingestion->resolvedAmountCents;
            $cfdiAttributes = $this->cfdiIngestor->metadataAttributes($ingestion->cfdi);
        }

        if ($resolvedAmount <= 0) {
            throw new InvalidExpenseReportException(__('El monto comprobado es inválido.'));
        }

        $submitted = DB::transaction(function () use ($expenseRequest, $actor, $resolvedAmount, $pdf, $xml, $documentType, $report, $cfdiAttributes): ExpenseReport {
            $payload = array_merge([
                'reported_amount_cents' => $resolvedAmount,
                'document_type' => $documentType,
            ], $cfdiAttributes);

            if ($report === null) {
                $report = ExpenseReport::query()->create(array_merge([
                    'expense_request_id' => $expenseRequest->id,
                    'status' => ExpenseReportStatus::Draft,
                    'submitted_at' => null,
                ], $payload));
            } else {
                $report->update($payload);
            }

            $report = $report->fresh();
            $this->attachments->storeKind($report, $actor, $pdf, 'pdf');

            if ($documentType === ExpenseReportDocumentType::Recibo) {
                $this->attachments->removeKind($report->fresh(), 'xml');
            } elseif ($xml !== null) {
                $report = $report->fresh();
                $this->attachments->storeKind($report, $actor, $xml, 'xml');
            }

            $report->update([
                'status' => ExpenseReportStatus::AccountingReview,
                'submitted_at' => now(),
            ]);

            $expenseRequest->update([
                'status' => ExpenseRequestStatus::ExpenseReportInReview,
            ]);

            DocumentEvent::query()->create([
                'subject_type' => $expenseRequest->getMorphClass(),
                'subject_id' => $expenseRequest->getKey(),
                'event_type' => DocumentEventType::ExpenseReportSubmitted,
                'actor_user_id' => $actor->id,
                'note' => '-',
                'metadata' => [
                    'expense_report_id' => $report->id,
                    'reported_amount_cents' => $resolvedAmount,
                    'document_type' => $documentType->value,
                    'cfdi_uuid' => $cfdiAttributes['cfdi_uuid'] ?? null,
                ],
            ]);

            return $report->fresh(['attachments']);
        });

        DB::afterCommit(function () use ($expenseRequest): void {
            $fresh = $expenseRequest->fresh(['user']);
            if ($fresh !== null) {
                $this->notifications->notifyAccountingOnExpenseReportSubmitted($fresh);
            }
        });

        return $submitted;
    }
}
