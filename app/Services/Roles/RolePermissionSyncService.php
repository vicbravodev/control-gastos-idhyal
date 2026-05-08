<?php

namespace App\Services\Roles;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RolePermissionSyncService
{
    /**
     * Synchronize a role's permissions and flush per-user permission caches
     * for any users currently in memory using that role.
     *
     * @param  list<int>  $permissionIds
     */
    public function syncPermissions(Role $role, array $permissionIds): void
    {
        DB::transaction(function () use ($role, $permissionIds): void {
            $valid = Permission::query()->whereIn('id', $permissionIds)->pluck('id')->all();
            $role->permissions()->sync($valid);
        });

        // Best-effort cache flush for the requesting user, if any.
        if (auth()->check()) {
            /** @var User $user */
            $user = auth()->user();
            $user->flushPermissionCache();
        }
    }
}
