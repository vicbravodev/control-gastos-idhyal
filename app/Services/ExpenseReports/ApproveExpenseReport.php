<?php

namespace App\Services\ExpenseReports;

use App\Enums\ApprovalInstanceStatus;
use App\Enums\DocumentEventType;
use App\Enums\ExpenseReportStatus;
use App\Enums\ExpenseRequestApprovalReason;
use App\Enums\ExpenseRequestStatus;
use App\Enums\SettlementStatus;
use App\Models\DocumentEvent;
use App\Models\ExpenseReport;
use App\Models\ExpenseRequest;
use App\Models\Settlement;
use App\Models\User;
use App\Services\ExpenseReports\Exceptions\InvalidExpenseReportException;
use App\Services\ExpenseRequests\ExpenseRequestNotificationDispatcher;
use Illuminate\Support\Facades\DB;

final class ApproveExpenseReport
{
    public function __construct(
        private readonly ExpenseRequestNotificationDispatcher $notifications,
    ) {}

    /**
     * @throws InvalidExpenseReportException
     */
    public function approve(
        ExpenseRequest $expenseRequest,
        ExpenseReport $report,
        User $actor,
        ?string $note,
    ): ?Settlement {
        if ((int) $report->expense_request_id !== (int) $expenseRequest->id) {
            throw new InvalidExpenseReportException(__('La comprobación no pertenece a esta solicitud.'));
        }

        if ($report->status !== ExpenseReportStatus::AccountingReview) {
            throw new InvalidExpenseReportException(__('La comprobación no está en revisión contable.'));
        }

        if ($expenseRequest->status !== ExpenseRequestStatus::ExpenseReportInReview) {
            throw new InvalidExpenseReportException(__('La solicitud no está en revisión de comprobación.'));
        }

        $settlement = DB::transaction(function () use ($expenseRequest, $report, $actor, $note): ?Settlement {
            $report->update([
                'status' => ExpenseReportStatus::Approved,
                'reviewer_user_id' => $actor->id,
                'reviewed_at' => now(),
            ]);

            DocumentEvent::query()->create([
                'subject_type' => $expenseRequest->getMorphClass(),
                'subject_id' => $expenseRequest->getKey(),
                'event_type' => DocumentEventType::ExpenseReportApproved,
                'actor_user_id' => $actor->id,
                'note' => $note !== null && $note !== '' ? $note : '-',
                'metadata' => [
                    'expense_report_id' => $report->id,
                ],
            ]);

            $expenseRequest->refresh();
            $expenseRequest->load(['expenseReports', 'approvals', 'payments']);

            $allReportsApproved = $expenseRequest->expenseReports->isNotEmpty()
                && $expenseRequest->expenseReports->every(
                    fn (ExpenseReport $r) => $r->status === ExpenseReportStatus::Approved,
                );

            $hasPendingOverCap = $expenseRequest->approvals->contains(
                fn ($a) => ($a->reason ?? ExpenseRequestApprovalReason::Initial) === ExpenseRequestApprovalReason::OverCapExtension
                    && $a->status === ApprovalInstanceStatus::Pending,
            );

            if (! $allReportsApproved || $hasPendingOverCap) {
                return null;
            }

            // Existing settlement? (idempotency)
            $existing = $expenseRequest->settlement;
            if ($existing !== null) {
                return $existing;
            }

            $basisCents = (int) $expenseRequest->payments->sum('amount_cents');
            $reportedCents = (int) $expenseRequest->expenseReports->sum('reported_amount_cents');
            $differenceCents = $basisCents - $reportedCents;

            $initialSettlementStatus = match (true) {
                $differenceCents === 0 => SettlementStatus::Closed,
                $differenceCents > 0 => SettlementStatus::PendingUserReturn,
                default => SettlementStatus::PendingCompanyPayment,
            };

            $nextRequestStatus = $differenceCents === 0
                ? ExpenseRequestStatus::Closed
                : ExpenseRequestStatus::SettlementPending;

            $expenseRequest->update([
                'status' => $nextRequestStatus,
            ]);

            $settlement = Settlement::query()->create([
                'expense_request_id' => $expenseRequest->id,
                'status' => $initialSettlementStatus,
                'basis_amount_cents' => $basisCents,
                'reported_amount_cents' => $reportedCents,
                'difference_cents' => $differenceCents,
            ]);

            DocumentEvent::query()->create([
                'subject_type' => $expenseRequest->getMorphClass(),
                'subject_id' => $expenseRequest->getKey(),
                'event_type' => DocumentEventType::ExpenseReportApproved,
                'actor_user_id' => $actor->id,
                'note' => '-',
                'metadata' => [
                    'settlement_id' => $settlement->id,
                    'difference_cents' => $differenceCents,
                ],
            ]);

            return $settlement;
        });

        DB::afterCommit(function () use ($expenseRequest, $settlement): void {
            if ($settlement === null) {
                return;
            }
            $fresh = $expenseRequest->fresh(['user']);
            if ($fresh !== null) {
                $this->notifications->notifyRequesterOnExpenseReportApproved($fresh, $settlement);
            }
        });

        return $settlement;
    }
}
