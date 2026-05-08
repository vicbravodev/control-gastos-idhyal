<?php

namespace App\Policies;

use App\Models\State;
use App\Models\User;

class StatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('admin.states.manage');
    }

    public function view(User $user, State $state): bool
    {
        return $user->hasPermission('admin.states.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('admin.states.manage');
    }

    public function update(User $user, State $state): bool
    {
        return $user->hasPermission('admin.states.manage');
    }

    public function delete(User $user, State $state): bool
    {
        return $user->hasPermission('admin.states.manage');
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
