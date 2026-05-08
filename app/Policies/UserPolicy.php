<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Catálogo de personal en el panel de administración (rol, región, estado).
     */
    public function manageStaffDirectory(User $user): bool
    {
        return $user->hasPermission('admin.users.manage');
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['admin.users.view', 'admin.users.manage']);
    }

    public function view(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasAnyPermission(['admin.users.view', 'admin.users.manage']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('admin.users.manage');
    }

    public function update(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasPermission('admin.users.manage');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->hasPermission('admin.users.manage');
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
