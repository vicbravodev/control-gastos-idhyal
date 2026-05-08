<?php

namespace App\Services\Approvals;

use App\Enums\ApprovalApproverType;
use App\Models\ExpenseRequestApproval;
use App\Models\User;
use App\Models\VacationRequestApproval;

/**
 * Single source of truth for "can this user act on this approval row?"
 *
 * Used by both Policies and notification dispatchers to keep the two in sync.
 */
class CanUserActOnApproval
{
    public function __construct(private readonly ResolveApproversForStep $resolver) {}

    public function check(User $user, ExpenseRequestApproval|VacationRequestApproval $approval): bool
    {
        $requesterId = $this->requesterIdFor($approval);
        if ($requesterId !== null && $user->id === $requesterId) {
            return false;
        }

        return match ($approval->approver_type) {
            ApprovalApproverType::Role => $user->role_id !== null
                && (int) $user->role_id === (int) $approval->approver_id,
            ApprovalApproverType::Department => $user->department_id !== null
                && (int) $user->department_id === (int) $approval->approver_id,
            ApprovalApproverType::User => (int) $user->id === (int) $approval->approver_id,
        };
    }

    private function requesterIdFor(ExpenseRequestApproval|VacationRequestApproval $approval): ?int
    {
        if ($approval instanceof ExpenseRequestApproval) {
            return $approval->expense_request_id !== null
                ? (int) ($approval->expenseRequest?->user_id ?? 0) ?: null
                : null;
        }

        return $approval->vacation_request_id !== null
            ? (int) ($approval->vacationRequest?->user_id ?? 0) ?: null
            : null;
    }
}
