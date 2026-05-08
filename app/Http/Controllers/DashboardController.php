<?php

namespace App\Http\Controllers;

use App\Enums\ExpenseRequestStatus;
use App\Models\ExpenseRequest;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DashboardController extends Controller
{
    /**
     * Persisted statuses considered "in flight" for the requesting user.
     * Excludes terminal outcomes (rejected, cancelled, closed).
     *
     * @var list<ExpenseRequestStatus>
     */
    private const ACTIVE_STATUSES = [
        ExpenseRequestStatus::Submitted,
        ExpenseRequestStatus::ApprovalInProgress,
        ExpenseRequestStatus::Approved,
        ExpenseRequestStatus::PendingPayment,
        ExpenseRequestStatus::Paid,
        ExpenseRequestStatus::AwaitingExpenseReport,
        ExpenseRequestStatus::ExpenseReportInReview,
        ExpenseRequestStatus::ExpenseReportApproved,
        ExpenseRequestStatus::ExpenseReportRejected,
        ExpenseRequestStatus::SettlementPending,
    ];

    /**
     * @var list<ExpenseRequestStatus>
     */
    private const PENDING_APPROVAL_STATUSES = [
        ExpenseRequestStatus::Submitted,
        ExpenseRequestStatus::ApprovalInProgress,
    ];

    public function __invoke(): InertiaResponse
    {
        $userId = auth()->id();

        $activeStatuses = array_map(static fn (ExpenseRequestStatus $s) => $s->value, self::ACTIVE_STATUSES);
        $pendingStatuses = array_map(static fn (ExpenseRequestStatus $s) => $s->value, self::PENDING_APPROVAL_STATUSES);

        $activeQuery = ExpenseRequest::query()
            ->where('user_id', $userId)
            ->whereIn('status', $activeStatuses);

        $expenseStats = [
            'active' => (clone $activeQuery)->count(),
            'pending_approval' => ExpenseRequest::query()
                ->where('user_id', $userId)
                ->whereIn('status', $pendingStatuses)
                ->count(),
            'in_flight_amount_cents' => (int) (clone $activeQuery)->sum('requested_amount_cents'),
        ];

        $recentExpenses = ExpenseRequest::query()
            ->where('user_id', $userId)
            ->with('expenseConcept:id,name')
            ->latest()
            ->limit(3)
            ->get()
            ->map(static fn (ExpenseRequest $r) => [
                'id' => $r->id,
                'folio' => $r->folio,
                'concept_label' => $r->conceptLabel(),
                'status' => $r->status->value,
                'requested_amount_cents' => (int) $r->requested_amount_cents,
            ])
            ->all();

        return Inertia::render('dashboard', [
            'expenseStats' => $expenseStats,
            'recentExpenses' => $recentExpenses,
        ]);
    }
}
