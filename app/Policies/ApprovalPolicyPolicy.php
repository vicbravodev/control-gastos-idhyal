<?php

namespace App\Policies;

use App\Models\ApprovalPolicy;
use App\Models\User;

/**
 * Authorization for configurable approval policy records ({@see ApprovalPolicy}).
 */
class ApprovalPolicyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['approval_policy.view_any', 'approval_policy.manage']);
    }

    public function view(User $user, ApprovalPolicy $approvalPolicy): bool
    {
        return $user->hasAnyPermission(['approval_policy.view_any', 'approval_policy.manage']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('approval_policy.manage');
    }

    public function update(User $user, ApprovalPolicy $approvalPolicy): bool
    {
        return $user->hasPermission('approval_policy.manage');
    }

    public function delete(User $user, ApprovalPolicy $approvalPolicy): bool
    {
        return $user->hasPermission('approval_policy.delete');
    }

    public function restore(User $user, ApprovalPolicy $approvalPolicy): bool
    {
        return false;
    }

    public function forceDelete(User $user, ApprovalPolicy $approvalPolicy): bool
    {
        return false;
    }
}
