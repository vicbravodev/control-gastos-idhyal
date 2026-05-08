<?php

namespace App\Services\Approvals;

use App\Enums\ApprovalApproverType;
use App\Models\ApprovalPolicyStep;
use App\Models\ExpenseRequestApproval;
use App\Models\User;
use App\Models\VacationRequestApproval;
use Illuminate\Support\Collection;

/**
 * Resolves the concrete users who can act on an approval step / row, given
 * its polymorphic approver target (Role | Department | User).
 */
class ResolveApproversForStep
{
    /**
     * Resolve the eligible approver users for a pending approval row,
     * excluding the document's requester.
     *
     * @return Collection<int, User>
     */
    public function resolveForApproval(ExpenseRequestApproval|VacationRequestApproval $approval): Collection
    {
        $type = $approval->approver_type;
        $id = (int) $approval->approver_id;
        $requesterId = $this->requesterIdFor($approval);

        return $this->resolveForType($type, $id)
            ->reject(fn (User $u): bool => $requesterId !== null && $u->id === $requesterId)
            ->values();
    }

    /**
     * Resolve eligible approver users for a policy step (no requester
     * exclusion since there is no concrete document yet).
     *
     * @return Collection<int, User>
     */
    public function resolveForStep(ApprovalPolicyStep $step): Collection
    {
        return $this->resolveForType($step->approver_type, (int) $step->approver_id);
    }

    /**
     * @return Collection<int, User>
     */
    public function resolveForType(ApprovalApproverType $type, int $id): Collection
    {
        return match ($type) {
            ApprovalApproverType::Role => User::query()->where('role_id', $id)->get(),
            ApprovalApproverType::Department => User::query()->where('department_id', $id)->get(),
            ApprovalApproverType::User => User::query()->whereKey($id)->get(),
        };
    }

    private function requesterIdFor(ExpenseRequestApproval|VacationRequestApproval $approval): ?int
    {
        if ($approval instanceof ExpenseRequestApproval) {
            return (int) $approval->expenseRequest?->user_id ?: null;
        }

        return (int) $approval->vacationRequest?->user_id ?: null;
    }
}
