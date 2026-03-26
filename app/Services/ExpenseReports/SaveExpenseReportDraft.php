<?php

namespace App\Services\ExpenseReports;

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
        private readonly CfdiComprobanteValidator $cfdiValidator,
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

        return DB::transaction(function () use ($expenseRequest, $actor, $reportedAmountCents, $pdf, $xml, $report): ExpenseReport {
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

            if ($pdf !== null) {
                $this->attachments->storeKind($report->fresh(), $actor, $pdf, 'pdf');
            }

            if ($xml !== null) {
                $this->cfdiValidator->validate($xml, $reportedAmountCents);
                $this->attachments->storeKind($report->fresh(), $actor, $xml, 'xml');
            }

            return $report->fresh(['attachments']);
        });
    }
}
