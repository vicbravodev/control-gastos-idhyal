<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('admin.roles.manage');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasPermission('admin.roles.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('admin.roles.manage');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasPermission('admin.roles.manage');
    }

    public function delete(User $user, Role $role): bool
    {
        if (! $user->hasPermission('admin.roles.manage')) {
            return false;
        }

        if ($role->users()->exists()) {
            return false;
        }

        if ($role->approvalPolicyStepsTargeting()->exists()) {
            return false;
        }

        if ($role->approvalPoliciesTargeting()->exists()) {
            return false;
        }

        return true;
    }

    public function restore(User $user, Role $role): bool
    {
        return false;
    }

    public function forceDelete(User $user, Role $role): bool
    {
        return false;
    }
}
