<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\State;
use App\Models\User;

class StatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleSlug::SuperAdmin);
    }

    public function view(User $user, State $state): bool
    {
        return $user->hasRole(RoleSlug::SuperAdmin);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleSlug::SuperAdmin);
    }

    public function update(User $user, State $state): bool
    {
        return $user->hasRole(RoleSlug::SuperAdmin);
    }

    public function delete(User $user, State $state): bool
    {
        return $user->hasRole(RoleSlug::SuperAdmin);
    }

    public function restore(User $user, State $state): bool
    {
        return false;
    }

    public function forceDelete(User $user, State $state): bool
    {
        return false;
    }
}
