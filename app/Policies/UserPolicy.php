<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\User;

class UserPolicy
{
    /**
     * Catálogo de personal en el panel de administración (rol, región, estado).
     */
    public function manageStaffDirectory(User $user): bool
    {
        return $user->hasRole(RoleSlug::SuperAdmin);
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(RoleSlug::SuperAdmin, RoleSlug::SecretarioGeneral);
    }

    public function view(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasAnyRole(RoleSlug::SuperAdmin, RoleSlug::SecretarioGeneral);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleSlug::SuperAdmin);
    }

    public function update(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasAnyRole(RoleSlug::SuperAdmin, RoleSlug::SecretarioGeneral);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->hasRole(RoleSlug::SuperAdmin);
    }

    public function restore(User $user, User $model): bool
    {
        return false;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}
