<?php

namespace App\Services\Approvals;

use App\Enums\ApprovalGroupCombinator;
use App\Enums\ApprovalInstanceStatus;
use App\Enums\ApprovalPolicyDocumentType;
use App\Enums\DocumentEventType;
use App\Enums\ExpenseRequestApprovalReason;
use App\Enums\ExpenseRequestStatus;
use App\Models\DocumentEvent;
use App\Models\ExpenseRequest;
use App\Models\ExpenseRequestApproval;
use App\Models\User;
use App\Services\Approvals\Exceptions\InvalidApprovalStateException;
use App\Services\Approvals\Exceptions\NoActiveApprovalPolicyException;
use App\Services\Budgets\ExpenseRequestBudgetLedgerWriter;
use App\Services\ExpenseRequests\ExpenseRequestNotificationDispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ExpenseRequestApprovalService
{
    public function __construct(
        private readonly ApprovalPolicyResolver $resolver,
        private readonly ExpenseRequestNotificationDispatcher $notifications,
        private readonly ExpenseRequestBudgetLedgerWriter $budgetLedger,
    ) {}

    public function startWorkflow(ExpenseRequest $expenseRequest): void
    {
        DB::transaction(function () use ($expenseRequest): void {
            $expenseRequest->refresh();
            if ($expenseRequest->status !== ExpenseRequestStatus::Submitted) {
                throw new InvalidApprovalStateException('Expense request must be submitted to start approvals.');
            }
            if ($expenseRequest->approvals()->exists()) {
                throw new InvalidApprovalStateException('Approval workflow was already started.');
            }

            $requester = $expenseRequest->user;
            $policy = $this->resolver->resolve(ApprovalPolicyDocumentType::ExpenseRequest, $requester);

            foreach ($policy->steps->sortBy('step_order') as $step) {
                ExpenseRequestApproval::query()->create([
                    'expense_request_id' => $expenseRequest->id,
                    'step_order' => $step->step_order,
                    'approver_type' => $step->approver_type,
                    'approver_id' => $step->approver_id,
                    'reason' => ExpenseRequestApprovalReason::Initial,
                    'status' => ApprovalInstanceStatus::Pending,
                ]);
            }

            $expenseRequest->update([
                'status' => ExpenseRequestStatus::ApprovalInProgress,
            ]);
        });
    }

    public function approve(ExpenseRequestApproval $approval, User $actor): void
    {
        Gate::forUser($actor)->authorize('approve', $approval);

        DB::transaction(function () use ($approval, $actor): void {
            $approval->refresh();
            $expenseRequest = $approval->expenseRequest()->lockForUpdate()->firstOrFail();
            $expenseRequest->load(['approvals', 'user']);

            $reason = $approval->reason ?? ExpenseRequestApprovalReason::Initial;

            // Initial approval requires request in ApprovalInProgress status.
            // Over-cap extension is independent of the request's lifecycle status.
            if ($reason === ExpenseRequestApprovalReason::Initial
                && $expenseRequest->status !== ExpenseRequestStatus::ApprovalInProgress) {
                throw new InvalidApprovalStateException('Expense request is not awaiting approvals.');
            }
            if ($approval->status !== ApprovalInstanceStatus::Pending) {
                throw new InvalidApprovalStateException('This approval step is not pending.');
            }

            $policy = $this->resolver->resolve(
                ApprovalPolicyDocumentType::ExpenseRequest,
                $expenseRequest->user,
            );

            // Filter approvals to this reason so we can support multiple chains on one request.
            $approvals = $expenseRequest->approvals
                ->filter(fn (ExpenseRequestApproval $a) => ($a->reason ?? ExpenseRequestApprovalReason::Initial) === $reason)
                ->sortBy('step_order')
                ->values();

            $orderedSteps = $policy->steps->sortBy('step_order')->values();

            // For over-cap chains the persisted step_orders are offset from the policy.
            // Compute the offset so we can reuse the grouper/validator on virtual orders.
            $orderOffset = (int) ($approvals->first()?->step_order ?? 0) - (int) ($orderedSteps->first()?->step_order ?? 0);
            $virtualApprovals = $approvals->map(fn (ExpenseRequestApproval $a) => $this->virtualizeApproval($a, $orderOffset));
            ApprovalChainValidator::assertApprovalsMatchPolicy($virtualApprovals, $policy);

            $groups = ApprovalStepGrouper::stepOrderGroups($orderedSteps);
            $activeIndex = ApprovalStepGrouper::firstIncompleteGroupIndex($virtualApprovals, $groups, $orderedSteps);
            if ($activeIndex === null) {
                throw new InvalidApprovalStateException('Approval chain is already complete.');
            }

            $activeOrders = $groups[$activeIndex];
            $approvalVirtualOrder = $approval->step_order - $orderOffset;
            if (! in_array($approvalVirtualOrder, $activeOrders, true)) {
                throw new InvalidApprovalStateException('This approval step is not active yet.');
            }

            $approval->update([
                'status' => ApprovalInstanceStatus::Approved,
                'approver_user_id' => $actor->id,
                'acted_at' => now(),
            ]);

            // Only skip peers in AnyOf groups (one approval satisfies the group).
            // In AllOf groups every step must independently be approved.
            $combinator = ApprovalStepGrouper::groupCombinator($orderedSteps, $activeOrders);
            if ($combinator === ApprovalGroupCombinator::AnyOf) {
                foreach ($approvals as $peer) {
                    if ($peer->id === $approval->id) {
                        continue;
                    }
                    $peerVirtualOrder = $peer->step_order - $orderOffset;
                    if (in_array($peerVirtualOrder, $activeOrders, true) && $peer->status === ApprovalInstanceStatus::Pending) {
                        $peer->update(['status' => ApprovalInstanceStatus::Skipped]);
                    }
                }
            }

            $expenseRequest->refresh();
            $expenseRequest->load('approvals');
            $refreshedApprovals = $expenseRequest->approvals
                ->filter(fn (ExpenseRequestApproval $a) => ($a->reason ?? ExpenseRequestApprovalReason::Initial) === $reason)
                ->sortBy('step_order')
                ->values();
            $refreshedVirtual = $refreshedApprovals->map(fn (ExpenseRequestApproval $a) => $this->virtualizeApproval($a, $orderOffset));

            $allDone = ApprovalStepGrouper::firstIncompleteGroupIndex($refreshedVirtual, $groups, $orderedSteps) === null;

            if ($allDone && $reason === ExpenseRequestApprovalReason::Initial) {
                $expenseRequest->update([
                    'status' => ExpenseRequestStatus::PendingPayment,
                    'approved_amount_cents' => $expenseRequest->approved_amount_cents ?? $expenseRequest->requested_amount_cents,
                ]);
                $expenseRequest->refresh();

                DocumentEvent::query()->create([
                    'subject_type' => $expenseRequest->getMorphClass(),
                    'subject_id' => $expenseRequest->getKey(),
                    'event_type' => DocumentEventType::ExpenseRequestChainApproved,
                    'actor_user_id' => $actor->id,
                    'note' => '-',
                    'metadata' => [
                        'approved_amount_cents' => $expenseRequest->approved_amount_cents,
                    ],
                ]);

                $this->budgetLedger->recordCommitIfApplicable($expenseRequest);
            } elseif ($allDone && $reason === ExpenseRequestApprovalReason::OverCapExtension) {
                $this->budgetLedger->recordCommitForApprovalReasonIfApplicable($expenseRequest, $reason);
            }

            $notifications = $this->notifications;
            DB::afterCommit(function () use ($expenseRequest, $actor, $allDone, $notifications, $reason): void {
                $fresh = $expenseRequest->fresh(['user']);
                if ($fresh !== null && $reason === ExpenseRequestApprovalReason::Initial) {
                    $notifications->notifyRequesterAfterApproval($fresh, $actor, $allDone);
                }
            });
        });
    }

    /**
     * Discards any pending initial-chain approvals and rebuilds them against the
     * currently-active approval policy. Use when a policy change leaves an
     * in-flight request with a chain that no longer matches.
     *
     * @throws InvalidApprovalStateException
     * @throws NoActiveApprovalPolicyException
     */
    public function rebuildWorkflow(ExpenseRequest $expenseRequest, User $actor): void
    {
        DB::transaction(function () use ($expenseRequest, $actor): void {
            $expenseRequest->refresh();
            $expenseRequest->load('approvals');

            if ($expenseRequest->status !== ExpenseRequestStatus::ApprovalInProgress) {
                throw new InvalidApprovalStateException('Only requests in approval can have their chain rebuilt.');
            }

            $requester = $expenseRequest->user;
            $policy = $this->resolver->resolve(ApprovalPolicyDocumentType::ExpenseRequest, $requester);

            $expenseRequest->approvals()
                ->where('reason', ExpenseRequestApprovalReason::Initial)
                ->delete();

            foreach ($policy->steps->sortBy('step_order') as $step) {
                ExpenseRequestApproval::query()->create([
                    'expense_request_id' => $expenseRequest->id,
                    'step_order' => $step->step_order,
                    'approver_type' => $step->approver_type,
                    'approver_id' => $step->approver_id,
                    'reason' => ExpenseRequestApprovalReason::Initial,
                    'status' => ApprovalInstanceStatus::Pending,
                ]);
            }

            DocumentEvent::query()->create([
                'subject_type' => $expenseRequest->getMorphClass(),
                'subject_id' => $expenseRequest->getKey(),
                'event_type' => DocumentEventType::ExpenseRequestApprovalChainRebuilt,
                'actor_user_id' => $actor->id,
                'note' => 'Cadena de aprobación rearmada con la política vigente.',
                'metadata' => [
                    'approval_policy_id' => $policy->id,
                ],
            ]);
        });

        $notifications = $this->notifications;
        DB::afterCommit(function () use ($expenseRequest, $notifications): void {
            $fresh = $expenseRequest->fresh(['user', 'approvals']);
            if ($fresh !== null) {
                $notifications->notifyApproversOnSubmitted($fresh);
            }
        });
    }

    public function requestOverCapExtension(ExpenseRequest $expenseRequest): void
    {
        $created = DB::transaction(function () use ($expenseRequest): bool {
            $expenseRequest->refresh();
            $expenseRequest->load('approvals');

            $existing = $expenseRequest->approvals
                ->first(fn (ExpenseRequestApproval $a) => ($a->reason ?? ExpenseRequestApprovalReason::Initial) === ExpenseRequestApprovalReason::OverCapExtension);
            if ($existing !== null) {
                return false;
            }

            $requester = $expenseRequest->user;
            try {
                $policy = $this->resolver->resolve(ApprovalPolicyDocumentType::ExpenseRequest, $requester);
            } catch (NoActiveApprovalPolicyException) {
                return false;
            }

            $maxStepOrder = (int) ($expenseRequest->approvals->max('step_order') ?? 0);

            foreach ($policy->steps->sortBy('step_order')->values() as $idx => $step) {
                ExpenseRequestApproval::query()->create([
                    'expense_request_id' => $expenseRequest->id,
                    'step_order' => $maxStepOrder + $idx + 1,
                    'approver_type' => $step->approver_type,
                    'approver_id' => $step->approver_id,
                    'reason' => ExpenseRequestApprovalReason::OverCapExtension,
                    'status' => ApprovalInstanceStatus::Pending,
                ]);
            }

            return true;
        });

        if (! $created) {
            return;
        }

        $notifications = $this->notifications;
        DB::afterCommit(function () use ($expenseRequest, $notifications): void {
            $fresh = $expenseRequest->fresh(['user', 'approvals']);
            if ($fresh !== null) {
                $notifications->notifyApproversOnOverCapRequested($fresh);
            }
        });
    }

    /**
     * Whether this approval row is in the current active group (same rule as approve/reject).
     */
    public function isPendingStepActive(ExpenseRequestApproval $approval): bool
    {
        $approval->loadMissing(['expenseRequest.approvals', 'expenseRequest.user']);
        $expenseRequest = $approval->expenseRequest;

        $reason = $approval->reason ?? ExpenseRequestApprovalReason::Initial;

        if ($reason === ExpenseRequestApprovalReason::Initial
            && $expenseRequest->status !== ExpenseRequestStatus::ApprovalInProgress) {
            return false;
        }

        if ($approval->status !== ApprovalInstanceStatus::Pending) {
            return false;
        }

        try {
            $policy = $this->resolver->resolve(
                ApprovalPolicyDocumentType::ExpenseRequest,
                $expenseRequest->user,
            );
            $approvals = $expenseRequest->approvals
                ->filter(fn (ExpenseRequestApproval $a) => ($a->reason ?? ExpenseRequestApprovalReason::Initial) === $reason)
                ->sortBy('step_order')
                ->values();
            $orderedSteps = $policy->steps->sortBy('step_order')->values();
            $orderOffset = (int) ($approvals->first()?->step_order ?? 0) - (int) ($orderedSteps->first()?->step_order ?? 0);
            $virtualApprovals = $approvals->map(fn (ExpenseRequestApproval $a) => $this->virtualizeApproval($a, $orderOffset));
            ApprovalChainValidator::assertApprovalsMatchPolicy($virtualApprovals, $policy);
            $groups = ApprovalStepGrouper::stepOrderGroups($orderedSteps);
            $activeIndex = ApprovalStepGrouper::firstIncompleteGroupIndex($virtualApprovals, $groups, $orderedSteps);
            if ($activeIndex === null) {
                return false;
            }
            $activeOrders = $groups[$activeIndex];

            return in_array($approval->step_order - $orderOffset, $activeOrders, true);
        } catch (InvalidApprovalStateException|NoActiveApprovalPolicyException) {
            return false;
        }
    }

    private function virtualizeApproval(ExpenseRequestApproval $a, int $orderOffset): ExpenseRequestApproval
    {
        $clone = new ExpenseRequestApproval;
        $clone->setRawAttributes([
            'id' => $a->id,
            'expense_request_id' => $a->expense_request_id,
            'step_order' => $a->step_order - $orderOffset,
            'approver_type' => $a->approver_type->value,
            'approver_id' => $a->approver_id,
            'status' => $a->status->value,
        ], true);

        return $clone;
    }

    public function reject(ExpenseRequestApproval $approval, User $actor, string $note): void
    {
        $note = trim($note);
        if ($note === '') {
            throw new InvalidApprovalStateException('A rejection note is required.');
        }

        Gate::forUser($actor)->authorize('reject', $approval);

        DB::transaction(function () use ($approval, $actor, $note): void {
            $approval->refresh();
            $expenseRequest = $approval->expenseRequest()->lockForUpdate()->firstOrFail();
            $expenseRequest->load(['approvals', 'user']);

            $reason = $approval->reason ?? ExpenseRequestApprovalReason::Initial;

            if ($reason === ExpenseRequestApprovalReason::Initial
                && $expenseRequest->status !== ExpenseRequestStatus::ApprovalInProgress) {
                throw new InvalidApprovalStateException('Expense request is not awaiting approvals.');
            }
            if ($approval->status !== ApprovalInstanceStatus::Pending) {
                throw new InvalidApprovalStateException('This approval step is not pending.');
            }

            $policy = $this->resolver->resolve(
                ApprovalPolicyDocumentType::ExpenseRequest,
                $expenseRequest->user,
            );

            $approvals = $expenseRequest->approvals
                ->filter(fn (ExpenseRequestApproval $a) => ($a->reason ?? ExpenseRequestApprovalReason::Initial) === $reason)
                ->sortBy('step_order')
                ->values();
            $orderedSteps = $policy->steps->sortBy('step_order')->values();
            $orderOffset = (int) ($approvals->first()?->step_order ?? 0) - (int) ($orderedSteps->first()?->step_order ?? 0);
            $virtualApprovals = $approvals->map(fn (ExpenseRequestApproval $a) => $this->virtualizeApproval($a, $orderOffset));
            ApprovalChainValidator::assertApprovalsMatchPolicy($virtualApprovals, $policy);

            $groups = ApprovalStepGrouper::stepOrderGroups($orderedSteps);
            $activeIndex = ApprovalStepGrouper::firstIncompleteGroupIndex($virtualApprovals, $groups, $orderedSteps);
            if ($activeIndex === null) {
                throw new InvalidApprovalStateException('Approval chain is already complete.');
            }

            $activeOrders = $groups[$activeIndex];
            if (! in_array($approval->step_order - $orderOffset, $activeOrders, true)) {
                throw new InvalidApprovalStateException('This approval step is not active yet.');
            }

            $approval->update([
                'status' => ApprovalInstanceStatus::Rejected,
                'approver_user_id' => $actor->id,
                'note' => $note,
                'acted_at' => now(),
            ]);

            if ($reason === ExpenseRequestApprovalReason::Initial) {
                $expenseRequest->update([
                    'status' => ExpenseRequestStatus::Rejected,
                ]);
            }

            DocumentEvent::query()->create([
                'subject_type' => $expenseRequest->getMorphClass(),
                'subject_id' => $expenseRequest->getKey(),
                'event_type' => DocumentEventType::Rejection,
                'actor_user_id' => $actor->id,
                'note' => $note,
            ]);

            $notifications = $this->notifications;
            DB::afterCommit(function () use ($expenseRequest, $note, $notifications, $reason): void {
                $fresh = $expenseRequest->fresh(['user']);
                if ($fresh !== null && $reason === ExpenseRequestApprovalReason::Initial) {
                    $notifications->notifyRequesterOnRejected($fresh, $note);
                }
            });
        });
    }
}
