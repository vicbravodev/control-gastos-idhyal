<?php

namespace App\Services\VacationRequests;

use App\Enums\ApprovalPolicyDocumentType;
use App\Models\User;
use App\Models\VacationRequest;
use App\Notifications\VacationRequests\VacationRequestApprovalProgressNotification;
use App\Notifications\VacationRequests\VacationRequestFullyApprovedNotification;
use App\Notifications\VacationRequests\VacationRequestRejectedNotification;
use App\Notifications\VacationRequests\VacationRequestSubmittedNotification;
use App\Services\Approvals\ApprovalPolicyResolver;
use App\Services\Approvals\ApprovalStepGrouper;
use App\Services\Approvals\Exceptions\NoActiveApprovalPolicyException;

final class VacationRequestNotificationDispatcher
{
    public function __construct(
        private readonly ApprovalPolicyResolver $policyResolver,
        private readonly VacationRequestApprovalProgressResolver $progressResolver,
    ) {}

    public function notifyApproversOnSubmitted(VacationRequest $vacationRequest): void
    {
        $vacationRequest->loadMissing(['user', 'approvals']);

        try {
            $policy = $this->policyResolver->resolve(
                ApprovalPolicyDocumentType::VacationRequest,
                $vacationRequest->user,
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
        $roleIds = $vacationRequest->approvals
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
            ->where('id', '!=', $vacationRequest->user_id)
            ->get();

        foreach ($approvers as $approver) {
            $approver->notify(new VacationRequestSubmittedNotification($vacationRequest));
        }
    }

    public function notifyRequesterAfterApproval(VacationRequest $vacationRequest, User $actor, bool $workflowComplete): void
    {
        $vacationRequest->loadMissing('user');
        $requester = $vacationRequest->user;
        if ($requester === null) {
            return;
        }

        if ($workflowComplete) {
            $requester->notify(new VacationRequestFullyApprovedNotification($vacationRequest, $actor));

            return;
        }

        $progress = $this->progressResolver->snapshot($vacationRequest);
        $requester->notify(new VacationRequestApprovalProgressNotification($vacationRequest, $actor, $progress));
    }

    public function notifyRequesterOnRejected(VacationRequest $vacationRequest, string $note): void
    {
        $vacationRequest->loadMissing('user');
        $requester = $vacationRequest->user;
        if ($requester === null) {
            return;
        }

        $requester->notify(new VacationRequestRejectedNotification($vacationRequest, $note));
    }
}
