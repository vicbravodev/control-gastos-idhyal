<?php

namespace App\Policies;

use App\Enums\ApprovalApproverType;
use App\Enums\ApprovalInstanceStatus;
use App\Enums\VacationRequestStatus;
use App\Models\User;
use App\Models\VacationRequest;
use App\Models\VacationRequestApproval;
use App\Services\Approvals\CanUserActOnApproval;

class VacationRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, VacationRequest $vacationRequest): bool
    {
        if ($user->id === $vacationRequest->user_id) {
            return true;
        }

        if ($user->hasVacationRequestOversight()) {
            return true;
        }

        return $this->hasPendingApprovalForUser($user, $vacationRequest);
    }

    private function hasPendingApprovalForUser(User $user, VacationRequest $vacationRequest): bool
    {
        return $vacationRequest->approvals()
            ->where('status', ApprovalInstanceStatus::Pending)
            ->where(function ($query) use ($user): void {
                if ($user->role_id !== null) {
                    $query->orWhere(function ($q) use ($user): void {
                        $q->where('approver_type', ApprovalApproverType::Role->value)
                            ->where('approver_id', $user->role_id);
                    });
                }
                if ($user->department_id !== null) {
                    $query->orWhere(function ($q) use ($user): void {
                        $q->where('approver_type', ApprovalApproverType::Department->value)
                            ->where('approver_id', $user->department_id);
                    });
                }
                $query->orWhere(function ($q) use ($user): void {
                    $q->where('approver_type', ApprovalApproverType::User->value)
                        ->where('approver_id', $user->id);
                });
            })
            ->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, VacationRequest $vacationRequest): bool
    {
        if ($user->id !== $vacationRequest->user_id) {
            return false;
        }

        return ! $this->isTerminal($vacationRequest);
    }

    public function delete(User $user, VacationRequest $vacationRequest): bool
    {
        return false;
    }

    public function restore(User $user, VacationRequest $vacationRequest): bool
    {
        return false;
    }

    public function forceDelete(User $user, VacationRequest $vacationRequest): bool
    {
        return false;
    }

    public function approveApproval(User $user, VacationRequestApproval $approval): bool
    {
        return $this->allowsActingOnPendingApproval($user, $approval);
    }

    public function rejectApproval(User $user, VacationRequestApproval $approval): bool
    {
        return $this->allowsActingOnPendingApproval($user, $approval);
    }

    /**
     * Recibo PDF tras completar la cadena de aprobación de vacaciones.
     */
    public function downloadFinalApprovalReceipt(User $user, VacationRequest $vacationRequest): bool
    {
        if (! $this->view($user, $vacationRequest)) {
            return false;
        }

        return in_array($vacationRequest->status, [
            VacationRequestStatus::Approved,
            VacationRequestStatus::Completed,
        ], true);
    }

    private function allowsActingOnPendingApproval(User $user, VacationRequestApproval $approval): bool
    {
        $vacationRequest = $approval->vacationRequest;

        if ($vacationRequest->status !== VacationRequestStatus::ApprovalInProgress) {
            return false;
        }

        if ($approval->status !== ApprovalInstanceStatus::Pending) {
            return false;
        }

        return app(CanUserActOnApproval::class)->check($user, $approval);
    }

    private function isTerminal(VacationRequest $vacationRequest): bool
    {
        return in_array($vacationRequest->status, [
            VacationRequestStatus::Rejected,
            VacationRequestStatus::Cancelled,
            VacationRequestStatus::Completed,
        ], true);
    }
}
