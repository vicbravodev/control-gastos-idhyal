<?php

namespace App\Policies;

use App\Enums\ApprovalInstanceStatus;
use App\Enums\VacationRequestStatus;
use App\Models\User;
use App\Models\VacationRequest;
use App\Models\VacationRequestApproval;

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

        if ($user->role_id === null) {
            return false;
        }

        return $vacationRequest->approvals()
            ->where('role_id', $user->role_id)
            ->where('status', ApprovalInstanceStatus::Pending)
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

        if ($user->role_id === null || $approval->role_id !== $user->role_id) {
            return false;
        }

        if ($user->id === $vacationRequest->user_id) {
            return false;
        }

        return true;
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
