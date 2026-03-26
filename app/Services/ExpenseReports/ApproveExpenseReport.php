<?php

namespace App\Services\ExpenseReports;

use App\Enums\DocumentEventType;
use App\Enums\ExpenseReportStatus;
use App\Enums\ExpenseRequestStatus;
use App\Enums\SettlementStatus;
use App\Models\DocumentEvent;
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
        User $actor,
        ?string $note,
    ): Settlement {
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

        if ($report->settlement()->exists()) {
            throw new InvalidExpenseReportException(__('Ya existe un balance para esta comprobación.'));
        }

        $payment = $expenseRequest->payments()->orderBy('id')->first();
        if ($payment === null) {
            throw new InvalidExpenseReportException(__('No hay pago asociado para calcular el balance.'));
        }

        $basisCents = $payment->amount_cents;
        $reportedCents = $report->reported_amount_cents;
        $differenceCents = $basisCents - $reportedCents;

        $initialSettlementStatus = match (true) {
            $differenceCents === 0 => SettlementStatus::Closed,
            $differenceCents > 0 => SettlementStatus::PendingUserReturn,
            default => SettlementStatus::PendingCompanyPayment,
        };

        $settlement = DB::transaction(function () use (
            $expenseRequest,
            $actor,
            $note,
            $report,
            $basisCents,
            $reportedCents,
            $differenceCents,
            $initialSettlementStatus,
        ): Settlement {
            $report->update([
                'status' => ExpenseReportStatus::Approved,
            ]);

            $nextRequestStatus = $differenceCents === 0
                ? ExpenseRequestStatus::Closed
                : ExpenseRequestStatus::SettlementPending;

            $expenseRequest->update([
                'status' => $nextRequestStatus,
            ]);

            $settlement = Settlement::query()->create([
                'expense_report_id' => $report->id,
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
                'note' => $note !== null && $note !== '' ? $note : '-',
                'metadata' => [
                    'expense_report_id' => $report->id,
                    'settlement_id' => $settlement->id,
                    'difference_cents' => $differenceCents,
                ],
            ]);

            return $settlement;
        });

        DB::afterCommit(function () use ($expenseRequest, $settlement): void {
            $fresh = $expenseRequest->fresh(['user']);
            if ($fresh !== null) {
                $this->notifications->notifyRequesterOnExpenseReportApproved($fresh, $settlement);
            }
        });

        return $settlement;
    }
}
