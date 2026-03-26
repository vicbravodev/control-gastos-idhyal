<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\Region;
use App\Models\User;

class RegionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleSlug::SuperAdmin);
    }

    public function view(User $user, Region $region): bool
    {
        return $user->hasRole(RoleSlug::SuperAdmin);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleSlug::SuperAdmin);
    }

    public function update(User $user, Region $region): bool
    {
        return $user->hasRole(RoleSlug::SuperAdmin);
    }

    public function delete(User $user, Region $region): bool
    {
        return $user->hasRole(RoleSlug::SuperAdmin);
    }

    public function restore(User $user, Region $region): bool
    {
        return false;
    }

    public function forceDelete(User $user, Region $region): bool
    {
        return false;
    }
}
