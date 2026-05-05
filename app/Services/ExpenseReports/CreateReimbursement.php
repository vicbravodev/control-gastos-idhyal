<?php

namespace App\Services\ExpenseReports;

use App\Enums\DeliveryMethod;
use App\Enums\DocumentEventType;
use App\Enums\ExpenseReportDocumentType;
use App\Enums\ExpenseReportStatus;
use App\Enums\ExpenseRequestStatus;
use App\Models\DocumentEvent;
use App\Models\ExpenseReport;
use App\Models\ExpenseRequest;
use App\Models\User;
use App\Services\ExpenseReports\Exceptions\InvalidExpenseReportException;
use App\Services\ExpenseRequests\ExpenseRequestFolioGenerator;
use App\Services\ExpenseRequests\ExpenseRequestNotificationDispatcher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class CreateReimbursement
{
    public function __construct(
        private readonly ExpenseReportAttachmentWriter $attachments,
        private readonly CfdiComprobanteValidator $cfdiValidator,
        private readonly ExpenseRequestFolioGenerator $folioGenerator,
        private readonly ExpenseRequestNotificationDispatcher $notifications,
    ) {}

    /**
     * Crea una solicitud "directa" sin flujo de aprobación previo, con su comprobación
     * adjunta lista para revisión contable. La empresa queda debiendo el monto al
     * usuario; al aprobarse la comprobación se genera un settlement de tipo
     * pending_company_payment con base 0.
     *
     * @throws InvalidExpenseReportException
     */
    public function create(
        User $actor,
        int $reportedAmountCents,
        int $expenseConceptId,
        ?string $conceptDescription,
        ExpenseReportDocumentType $documentType,
        UploadedFile $pdf,
        ?UploadedFile $xml,
    ): ExpenseRequest {
        if ($documentType === ExpenseReportDocumentType::Factura && $xml === null) {
            throw new InvalidExpenseReportException(__('Debes adjuntar el XML del CFDI cuando la comprobación es una factura.'));
        }

        if ($xml !== null) {
            $this->cfdiValidator->validate($xml, $reportedAmountCents);
        }

        $expenseRequest = DB::transaction(function () use (
            $actor,
            $reportedAmountCents,
            $expenseConceptId,
            $conceptDescription,
            $documentType,
            $pdf,
            $xml,
        ): ExpenseRequest {
            $expenseRequest = ExpenseRequest::query()->create([
                'user_id' => $actor->id,
                'status' => ExpenseRequestStatus::ExpenseReportInReview,
                'folio' => null,
                'requested_amount_cents' => $reportedAmountCents,
                'approved_amount_cents' => $reportedAmountCents,
                'expense_concept_id' => $expenseConceptId,
                'concept_description' => $conceptDescription,
                'delivery_method' => DeliveryMethod::Transfer,
                'is_reimbursement' => true,
            ]);

            $this->folioGenerator->assign($expenseRequest);
            $expenseRequest = $expenseRequest->fresh();

            $report = ExpenseReport::query()->create([
                'expense_request_id' => $expenseRequest->id,
                'status' => ExpenseReportStatus::AccountingReview,
                'reported_amount_cents' => $reportedAmountCents,
                'document_type' => $documentType,
                'submitted_at' => now(),
            ]);

            $report = $report->fresh();
            $this->attachments->storeKind($report, $actor, $pdf, 'pdf');
            if ($documentType === ExpenseReportDocumentType::Factura && $xml !== null) {
                $report = $report->fresh();
                $this->attachments->storeKind($report, $actor, $xml, 'xml');
            }

            DocumentEvent::query()->create([
                'subject_type' => $expenseRequest->getMorphClass(),
                'subject_id' => $expenseRequest->getKey(),
                'event_type' => DocumentEventType::ExpenseRequestReimbursementCreated,
                'actor_user_id' => $actor->id,
                'note' => '-',
                'metadata' => [
                    'expense_report_id' => $report->id,
                    'reported_amount_cents' => $reportedAmountCents,
                    'document_type' => $documentType->value,
                ],
            ]);

            return $expenseRequest;
        });

        DB::afterCommit(function () use ($expenseRequest): void {
            $fresh = $expenseRequest->fresh(['user']);
            if ($fresh !== null) {
                $this->notifications->notifyAccountingOnExpenseReportSubmitted($fresh);
            }
        });

        return $expenseRequest;
    }
}
