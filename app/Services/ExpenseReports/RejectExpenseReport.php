<?php

namespace App\Services\ExpenseReports;

use App\Enums\DocumentEventType;
use App\Enums\ExpenseReportStatus;
use App\Enums\ExpenseRequestStatus;
use App\Models\DocumentEvent;
use App\Models\ExpenseRequest;
use App\Models\User;
use App\Services\ExpenseReports\Exceptions\InvalidExpenseReportException;
use App\Services\ExpenseRequests\ExpenseRequestNotificationDispatcher;
use Illuminate\Support\Facades\DB;

final class RejectExpenseReport
{
    public function __construct(
        private readonly ExpenseRequestNotificationDispatcher $notifications,
    ) {}

    /**
     * @throws InvalidExpenseReportException
     */
    public function reject(
        ExpenseRequest $expenseRequest,
        User $actor,
        string $note,
    ): void {
        $report = $expenseRequest->expenseReport;

        if ($report === null) {
            throw new InvalidExpenseReportException(__('No hay comprobación registrada.'));
        }

        if ($report->status !== ExpenseReportStatus::AccountingReview) {
            throw new InvalidExpenseReportException(__('La comprobación no está en revisión contable.'));
        }

        if ($expenseRequest->status !== ExpenseRequestStatus::ExpenseReportInReview) {
            throw new InvalidExpenseReportException(__('La solicitud no está en revisión de comprobación.'));
        }

        DB::transaction(function () use ($expenseRequest, $actor, $note, $report): void {
            $report->update([
                'status' => ExpenseReportStatus::Rejected,
            ]);

            $expenseRequest->update([
                'status' => ExpenseRequestStatus::ExpenseReportRejected,
            ]);

            DocumentEvent::query()->create([
                'subject_type' => $expenseRequest->getMorphClass(),
                'subject_id' => $expenseRequest->getKey(),
                'event_type' => DocumentEventType::ExpenseReportRejected,
                'actor_user_id' => $actor->id,
                'note' => $note,
                'metadata' => [
                    'expense_report_id' => $report->id,
                ],
            ]);
        });

        DB::afterCommit(function () use ($expenseRequest, $note): void {
            $fresh = $expenseRequest->fresh(['user']);
            if ($fresh !== null) {
                $this->notifications->notifyRequesterOnExpenseReportRejected($fresh, $note);
            }
        });
    }
}
