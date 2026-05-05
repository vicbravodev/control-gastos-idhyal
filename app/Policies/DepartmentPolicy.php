<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleSlug::SuperAdmin);
    }

    public function view(User $user, Department $department): bool
    {
        return $user->hasRole(RoleSlug::SuperAdmin);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleSlug::SuperAdmin);
    }

    public function update(User $user, Department $department): bool
    {
        return $user->hasRole(RoleSlug::SuperAdmin);
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->hasRole(RoleSlug::SuperAdmin);
    }

    public function restore(User $user, Department $department): bool
    {
        return false;
    }

    public function forceDelete(User $user, Department $department): bool
    {
        return false;
    }
}
