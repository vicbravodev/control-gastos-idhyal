<?php

namespace App\Services\ExpenseReports;

use App\Enums\DocumentEventType;
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
        private readonly CfdiComprobanteValidator $cfdiValidator,
    ) {}

    /**
     * @throws InvalidExpenseReportException
     */
    public function submit(
        ExpenseRequest $expenseRequest,
        User $actor,
        int $reportedAmountCents,
        UploadedFile $pdf,
        UploadedFile $xml,
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

        $report = $expenseRequest->expenseReport;

        if ($report !== null && ! in_array($report->status, [
            ExpenseReportStatus::Draft,
            ExpenseReportStatus::Rejected,
        ], true)) {
            throw new InvalidExpenseReportException(__('La comprobación ya fue enviada o cerrada.'));
        }

        $this->cfdiValidator->validate($xml, $reportedAmountCents);

        $submitted = DB::transaction(function () use ($expenseRequest, $actor, $reportedAmountCents, $pdf, $xml, $report): ExpenseReport {
            if ($report === null) {
                $report = ExpenseReport::query()->create([
                    'expense_request_id' => $expenseRequest->id,
                    'status' => ExpenseReportStatus::Draft,
                    'reported_amount_cents' => $reportedAmountCents,
                    'submitted_at' => null,
                ]);
            } else {
                $report->update([
                    'reported_amount_cents' => $reportedAmountCents,
                ]);
            }

            $report = $report->fresh();
            $this->attachments->storeKind($report, $actor, $pdf, 'pdf');
            $report = $report->fresh();
            $this->attachments->storeKind($report, $actor, $xml, 'xml');

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
                    'reported_amount_cents' => $reportedAmountCents,
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
