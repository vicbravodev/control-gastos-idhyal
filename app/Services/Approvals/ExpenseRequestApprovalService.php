<?php

namespace App\Services\Approvals;

use App\Enums\ApprovalInstanceStatus;
use App\Enums\ApprovalPolicyDocumentType;
use App\Enums\DocumentEventType;
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
                    'role_id' => $step->role_id,
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

            if ($expenseRequest->status !== ExpenseRequestStatus::ApprovalInProgress) {
                throw new InvalidApprovalStateException('Expense request is not awaiting approvals.');
            }
            if ($approval->status !== ApprovalInstanceStatus::Pending) {
                throw new InvalidApprovalStateException('This approval step is not pending.');
            }

            $policy = $this->resolver->resolve(
                ApprovalPolicyDocumentType::ExpenseRequest,
                $expenseRequest->user,
            );

            $approvals = $expenseRequest->approvals->sortBy('step_order')->values();
            ApprovalChainValidator::assertApprovalsMatchPolicy($approvals, $policy);

            $groups = ApprovalStepGrouper::stepOrderGroups($policy->steps->sortBy('step_order')->values());
            $activeIndex = ApprovalStepGrouper::firstIncompleteGroupIndex($approvals, $groups);
            if ($activeIndex === null) {
                throw new InvalidApprovalStateException('Approval chain is already complete.');
            }

            $activeOrders = $groups[$activeIndex];
            if (! in_array($approval->step_order, $activeOrders, true)) {
                throw new InvalidApprovalStateException('This approval step is not active yet.');
            }

            $approval->update([
                'status' => ApprovalInstanceStatus::Approved,
                'approver_user_id' => $actor->id,
                'acted_at' => now(),
            ]);

            foreach ($expenseRequest->approvals as $peer) {
                if ($peer->id === $approval->id) {
                    continue;
                }
                if (in_array($peer->step_order, $activeOrders, true) && $peer->status === ApprovalInstanceStatus::Pending) {
                    $peer->update(['status' => ApprovalInstanceStatus::Skipped]);
                }
            }

            $expenseRequest->refresh();
            $expenseRequest->load('approvals');
            $refreshedApprovals = $expenseRequest->approvals->sortBy('step_order')->values();

            $allDone = ApprovalStepGrouper::firstIncompleteGroupIndex($refreshedApprovals, $groups) === null;
            if ($allDone) {
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
            }

            $notifications = $this->notifications;
            DB::afterCommit(function () use ($expenseRequest, $actor, $allDone, $notifications): void {
                $fresh = $expenseRequest->fresh(['user']);
                if ($fresh !== null) {
                    $notifications->notifyRequesterAfterApproval($fresh, $actor, $allDone);
                }
            });
        });
    }

    /**
     * Whether this approval row is in the current active group (same rule as approve/reject).
     */
    public function isPendingStepActive(ExpenseRequestApproval $approval): bool
    {
        $approval->loadMissing(['expenseRequest.approvals', 'expenseRequest.user']);
        $expenseRequest = $approval->expenseRequest;

        if ($expenseRequest->status !== ExpenseRequestStatus::ApprovalInProgress) {
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
            $approvals = $expenseRequest->approvals->sortBy('step_order')->values();
            ApprovalChainValidator::assertApprovalsMatchPolicy($approvals, $policy);
            $groups = ApprovalStepGrouper::stepOrderGroups($policy->steps->sortBy('step_order')->values());
            $activeIndex = ApprovalStepGrouper::firstIncompleteGroupIndex($approvals, $groups);
            if ($activeIndex === null) {
                return false;
            }
            $activeOrders = $groups[$activeIndex];

            return in_array($approval->step_order, $activeOrders, true);
        } catch (InvalidApprovalStateException|NoActiveApprovalPolicyException) {
            return false;
        }
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

            if ($expenseRequest->status !== ExpenseRequestStatus::ApprovalInProgress) {
                throw new InvalidApprovalStateException('Expense request is not awaiting approvals.');
            }
            if ($approval->status !== ApprovalInstanceStatus::Pending) {
                throw new InvalidApprovalStateException('This approval step is not pending.');
            }

            $policy = $this->resolver->resolve(
                ApprovalPolicyDocumentType::ExpenseRequest,
                $expenseRequest->user,
            );

            $approvals = $expenseRequest->approvals->sortBy('step_order')->values();
            ApprovalChainValidator::assertApprovalsMatchPolicy($approvals, $policy);

            $groups = ApprovalStepGrouper::stepOrderGroups($policy->steps->sortBy('step_order')->values());
            $activeIndex = ApprovalStepGrouper::firstIncompleteGroupIndex($approvals, $groups);
            if ($activeIndex === null) {
                throw new InvalidApprovalStateException('Approval chain is already complete.');
            }

            $activeOrders = $groups[$activeIndex];
            if (! in_array($approval->step_order, $activeOrders, true)) {
                throw new InvalidApprovalStateException('This approval step is not active yet.');
            }

            $approval->update([
                'status' => ApprovalInstanceStatus::Rejected,
                'approver_user_id' => $actor->id,
                'note' => $note,
                'acted_at' => now(),
            ]);

            $expenseRequest->update([
                'status' => ExpenseRequestStatus::Rejected,
            ]);

            DocumentEvent::query()->create([
                'subject_type' => $expenseRequest->getMorphClass(),
                'subject_id' => $expenseRequest->getKey(),
                'event_type' => DocumentEventType::Rejection,
                'actor_user_id' => $actor->id,
                'note' => $note,
            ]);

            $notifications = $this->notifications;
            DB::afterCommit(function () use ($expenseRequest, $note, $notifications): void {
                $fresh = $expenseRequest->fresh(['user']);
                if ($fresh !== null) {
                    $notifications->notifyRequesterOnRejected($fresh, $note);
                }
            });
        });
    }
}
