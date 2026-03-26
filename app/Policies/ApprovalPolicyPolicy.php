<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\ApprovalPolicy;
use App\Models\User;

/**
 * Authorization for configurable approval policy records ({@see ApprovalPolicy}).
 */
class ApprovalPolicyPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allowsManagement($user);
    }

    public function view(User $user, ApprovalPolicy $approvalPolicy): bool
    {
        return $this->allowsManagement($user);
    }

    public function create(User $user): bool
    {
        return $this->allowsManagement($user);
    }

    public function update(User $user, ApprovalPolicy $approvalPolicy): bool
    {
        return $this->allowsManagement($user);
    }

    public function delete(User $user, ApprovalPolicy $approvalPolicy): bool
    {
        return $user->hasRole(RoleSlug::SuperAdmin);
    }

    public function restore(User $user, ApprovalPolicy $approvalPolicy): bool
    {
        return false;
    }

    public function forceDelete(User $user, ApprovalPolicy $approvalPolicy): bool
    {
        return false;
    }

    private function allowsManagement(User $user): bool
    {
        return $user->hasAnyRole(RoleSlug::SuperAdmin, RoleSlug::SecretarioGeneral);
    }
}
