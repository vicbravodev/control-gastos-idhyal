<?php

namespace App\Services\ExpenseRequests;

use App\Enums\ApprovalPolicyDocumentType;
use App\Enums\RoleSlug;
use App\Models\ExpenseRequest;
use App\Models\Payment;
use App\Models\Settlement;
use App\Models\User;
use App\Notifications\ExpenseRequests\ExpenseReportApprovedNotification;
use App\Notifications\ExpenseRequests\ExpenseReportRejectedNotification;
use App\Notifications\ExpenseRequests\ExpenseReportSubmittedForReviewNotification;
use App\Notifications\ExpenseRequests\ExpenseRequestApprovalProgressNotification;
use App\Notifications\ExpenseRequests\ExpenseRequestFullyApprovedNotification;
use App\Notifications\ExpenseRequests\ExpenseRequestPaidNotification;
use App\Notifications\ExpenseRequests\ExpenseRequestRejectedNotification;
use App\Notifications\ExpenseRequests\ExpenseRequestSubmittedNotification;
use App\Notifications\ExpenseRequests\SettlementClosedNotification;
use App\Notifications\ExpenseRequests\SettlementLiquidatedNotification;
use App\Notifications\ExpenseRequests\SettlementPendingReminderNotification;
use App\Services\Approvals\ApprovalPolicyResolver;
use App\Services\Approvals\ApprovalStepGrouper;
use App\Services\Approvals\Exceptions\NoActiveApprovalPolicyException;

final class ExpenseRequestNotificationDispatcher
{
    public function __construct(
        private readonly ApprovalPolicyResolver $policyResolver,
        private readonly ExpenseRequestApprovalProgressResolver $progressResolver,
    ) {}

    public function notifyApproversOnSubmitted(ExpenseRequest $expenseRequest): void
    {
        $expenseRequest->loadMissing(['user', 'approvals']);

        try {
            $policy = $this->policyResolver->resolve(
                ApprovalPolicyDocumentType::ExpenseRequest,
                $expenseRequest->user,
            );
        } catch (NoActiveApprovalPolicyException) {
            return;
        }

        $orderedSteps = $policy->steps->sortBy('step_order')->values();
        $groups = ApprovalStepGrouper::stepOrderGroups($orderedSteps);
        if ($groups === []) {
            return;
        }

        $firstGroupOrders = $groups[0];
        $roleIds = $expenseRequest->approvals
            ->filter(fn ($a) => in_array($a->step_order, $firstGroupOrders, true))
            ->pluck('role_id')
            ->unique()
            ->values()
            ->all();

        if ($roleIds === []) {
            return;
        }

        $approvers = User::query()
            ->whereIn('role_id', $roleIds)
            ->where('id', '!=', $expenseRequest->user_id)
            ->get();

        foreach ($approvers as $approver) {
            $approver->notify(new ExpenseRequestSubmittedNotification($expenseRequest));
        }
    }

    public function notifyRequesterAfterApproval(ExpenseRequest $expenseRequest, User $actor, bool $workflowComplete): void
    {
        $expenseRequest->loadMissing('user');
        $requester = $expenseRequest->user;
        if ($requester === null) {
            return;
        }

        if ($workflowComplete) {
            $requester->notify(new ExpenseRequestFullyApprovedNotification($expenseRequest, $actor));

            return;
        }

        $progress = $this->progressResolver->snapshot($expenseRequest);
        $requester->notify(new ExpenseRequestApprovalProgressNotification($expenseRequest, $actor, $progress));
    }

    public function notifyRequesterOnRejected(ExpenseRequest $expenseRequest, string $note): void
    {
        $expenseRequest->loadMissing('user');
        $requester = $expenseRequest->user;
        if ($requester === null) {
            return;
        }

        $requester->notify(new ExpenseRequestRejectedNotification($expenseRequest, $note));
    }

    public function notifyRequesterOnPaid(ExpenseRequest $expenseRequest, User $actor, Payment $payment): void
    {
        $expenseRequest->loadMissing('user');
        $requester = $expenseRequest->user;
        if ($requester === null) {
            return;
        }

        $requester->notify(new ExpenseRequestPaidNotification($expenseRequest, $payment));
    }

    public function notifyAccountingOnExpenseReportSubmitted(ExpenseRequest $expenseRequest): void
    {
        $reviewers = User::query()
            ->whereRelation('role', 'slug', RoleSlug::Contabilidad->value)
            ->where('id', '!=', $expenseRequest->user_id)
            ->get();

        foreach ($reviewers as $reviewer) {
            $reviewer->notify(new ExpenseReportSubmittedForReviewNotification($expenseRequest));
        }
    }

    public function notifyRequesterOnExpenseReportApproved(ExpenseRequest $expenseRequest, Settlement $settlement): void
    {
        $expenseRequest->loadMissing('user');
        $requester = $expenseRequest->user;
        if ($requester === null) {
            return;
        }

        $requester->notify(new ExpenseReportApprovedNotification($expenseRequest, $settlement));
    }

    public function notifyRequesterOnExpenseReportRejected(ExpenseRequest $expenseRequest, string $note): void
    {
        $expenseRequest->loadMissing('user');
        $requester = $expenseRequest->user;
        if ($requester === null) {
            return;
        }

        $requester->notify(new ExpenseReportRejectedNotification($expenseRequest, $note));
    }

    public function notifyRequesterOnSettlementLiquidated(ExpenseRequest $expenseRequest, Settlement $settlement): void
    {
        $expenseRequest->loadMissing('user');
        $requester = $expenseRequest->user;
        if ($requester === null) {
            return;
        }

        $requester->notify(new SettlementLiquidatedNotification($expenseRequest, $settlement));
    }

    public function notifyRequesterOnSettlementClosed(ExpenseRequest $expenseRequest, Settlement $settlement): void
    {
        $expenseRequest->loadMissing('user');
        $requester = $expenseRequest->user;
        if ($requester === null) {
            return;
        }

        $requester->notify(new SettlementClosedNotification($expenseRequest, $settlement));
    }

    public function notifySettlementPendingReminders(ExpenseRequest $expenseRequest, Settlement $settlement): void
    {
        $expenseRequest->loadMissing('user');
        $requester = $expenseRequest->user;
        if ($requester !== null) {
            $requester->notify(new SettlementPendingReminderNotification($expenseRequest, $settlement));
        }

        $reviewers = User::query()
            ->whereRelation('role', 'slug', RoleSlug::Contabilidad->value)
            ->where('id', '!=', $expenseRequest->user_id)
            ->get();

        foreach ($reviewers as $reviewer) {
            $reviewer->notify(new SettlementPendingReminderNotification($expenseRequest, $settlement));
        }
    }
}
