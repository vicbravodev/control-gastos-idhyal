<?php

namespace App\Services\Approvals;

use App\Enums\ApprovalInstanceStatus;
use App\Enums\ApprovalPolicyDocumentType;
use App\Enums\DocumentEventType;
use App\Enums\VacationRequestStatus;
use App\Models\DocumentEvent;
use App\Models\User;
use App\Models\VacationRequest;
use App\Models\VacationRequestApproval;
use App\Services\Approvals\Exceptions\InvalidApprovalStateException;
use App\Services\Approvals\Exceptions\NoActiveApprovalPolicyException;
use App\Services\VacationRequests\VacationRequestNotificationDispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class VacationRequestApprovalService
{
    public function __construct(
        private readonly ApprovalPolicyResolver $resolver,
        private readonly VacationRequestNotificationDispatcher $notifications,
    ) {}

    public function startWorkflow(VacationRequest $vacationRequest): void
    {
        DB::transaction(function () use ($vacationRequest): void {
            $vacationRequest->refresh();
            if ($vacationRequest->status !== VacationRequestStatus::Submitted) {
                throw new InvalidApprovalStateException('Vacation request must be submitted to start approvals.');
            }
            if ($vacationRequest->approvals()->exists()) {
                throw new InvalidApprovalStateException('Approval workflow was already started.');
            }

            $requester = $vacationRequest->user;
            $policy = $this->resolver->resolve(ApprovalPolicyDocumentType::VacationRequest, $requester);

            foreach ($policy->steps->sortBy('step_order') as $step) {
                VacationRequestApproval::query()->create([
                    'vacation_request_id' => $vacationRequest->id,
                    'step_order' => $step->step_order,
                    'role_id' => $step->role_id,
                    'status' => ApprovalInstanceStatus::Pending,
                ]);
            }

            $vacationRequest->update([
                'status' => VacationRequestStatus::ApprovalInProgress,
            ]);
        });

        $notifications = $this->notifications;
        DB::afterCommit(function () use ($vacationRequest, $notifications): void {
            $fresh = $vacationRequest->fresh(['user', 'approvals']);
            if ($fresh !== null) {
                $notifications->notifyApproversOnSubmitted($fresh);
            }
        });
    }

    public function approve(VacationRequestApproval $approval, User $actor): void
    {
        Gate::forUser($actor)->authorize('approve', $approval);

        DB::transaction(function () use ($approval, $actor): void {
            $approval->refresh();
            $vacationRequest = $approval->vacationRequest()->lockForUpdate()->firstOrFail();
            $vacationRequest->load(['approvals', 'user']);

            if ($vacationRequest->status !== VacationRequestStatus::ApprovalInProgress) {
                throw new InvalidApprovalStateException('Vacation request is not awaiting approvals.');
            }
            if ($approval->status !== ApprovalInstanceStatus::Pending) {
                throw new InvalidApprovalStateException('This approval step is not pending.');
            }

            $policy = $this->resolver->resolve(
                ApprovalPolicyDocumentType::VacationRequest,
                $vacationRequest->user,
            );

            $approvals = $vacationRequest->approvals->sortBy('step_order')->values();
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

            foreach ($vacationRequest->approvals as $peer) {
                if ($peer->id === $approval->id) {
                    continue;
                }
                if (in_array($peer->step_order, $activeOrders, true) && $peer->status === ApprovalInstanceStatus::Pending) {
                    $peer->update(['status' => ApprovalInstanceStatus::Skipped]);
                }
            }

            $vacationRequest->refresh();
            $vacationRequest->load('approvals');
            $refreshedApprovals = $vacationRequest->approvals->sortBy('step_order')->values();

            $allDone = ApprovalStepGrouper::firstIncompleteGroupIndex($refreshedApprovals, $groups) === null;
            if ($allDone) {
                $vacationRequest->update([
                    'status' => VacationRequestStatus::Approved,
                ]);
            }

            $notifications = $this->notifications;
            DB::afterCommit(function () use ($vacationRequest, $actor, $allDone, $notifications): void {
                $fresh = $vacationRequest->fresh(['user']);
                if ($fresh !== null) {
                    $notifications->notifyRequesterAfterApproval($fresh, $actor, $allDone);
                }
            });
        });
    }

    public function reject(VacationRequestApproval $approval, User $actor, string $note): void
    {
        $note = trim($note);
        if ($note === '') {
            throw new InvalidApprovalStateException('A rejection note is required.');
        }

        Gate::forUser($actor)->authorize('reject', $approval);

        DB::transaction(function () use ($approval, $actor, $note): void {
            $approval->refresh();
            $vacationRequest = $approval->vacationRequest()->lockForUpdate()->firstOrFail();
            $vacationRequest->load(['approvals', 'user']);

            if ($vacationRequest->status !== VacationRequestStatus::ApprovalInProgress) {
                throw new InvalidApprovalStateException('Vacation request is not awaiting approvals.');
            }
            if ($approval->status !== ApprovalInstanceStatus::Pending) {
                throw new InvalidApprovalStateException('This approval step is not pending.');
            }

            $policy = $this->resolver->resolve(
                ApprovalPolicyDocumentType::VacationRequest,
                $vacationRequest->user,
            );

            $approvals = $vacationRequest->approvals->sortBy('step_order')->values();
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

            $vacationRequest->update([
                'status' => VacationRequestStatus::Rejected,
            ]);

            DocumentEvent::query()->create([
                'subject_type' => $vacationRequest->getMorphClass(),
                'subject_id' => $vacationRequest->getKey(),
                'event_type' => DocumentEventType::Rejection,
                'actor_user_id' => $actor->id,
                'note' => $note,
            ]);

            $notifications = $this->notifications;
            DB::afterCommit(function () use ($vacationRequest, $note, $notifications): void {
                $fresh = $vacationRequest->fresh(['user']);
                if ($fresh !== null) {
                    $notifications->notifyRequesterOnRejected($fresh, $note);
                }
            });
        });
    }

    /**
     * Whether this approval row is in the current active group (same rule as approve/reject).
     */
    public function isPendingStepActive(VacationRequestApproval $approval): bool
    {
        $approval->loadMissing(['vacationRequest.approvals', 'vacationRequest.user']);
        $vacationRequest = $approval->vacationRequest;

        if ($vacationRequest->status !== VacationRequestStatus::ApprovalInProgress) {
            return false;
        }

        if ($approval->status !== ApprovalInstanceStatus::Pending) {
            return false;
        }

        try {
            $policy = $this->resolver->resolve(
                ApprovalPolicyDocumentType::VacationRequest,
                $vacationRequest->user,
            );
            $approvals = $vacationRequest->approvals->sortBy('step_order')->values();
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
}
