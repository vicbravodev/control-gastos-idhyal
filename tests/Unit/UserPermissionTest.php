<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_no_role_has_no_permissions(): void
    {
        $user = User::factory()->create(['role_id' => null]);

        $this->assertSame([], $user->permissionSlugs());
        $this->assertFalse($user->hasPermission('expense_request.create'));
    }

    public function test_user_has_permission_when_role_does(): void
    {
        $perm = Permission::query()->create([
            'slug' => 'expense_request.approve',
            'name' => 'Approve',
            'module' => 'expense_requests',
        ]);
        $role = Role::query()->create(['slug' => 'role-x', 'name' => 'X']);
        $role->permissions()->attach($perm);

        $user = User::factory()->create(['role_id' => $role->id]);

        $this->assertTrue($user->hasPermission('expense_request.approve'));
        $this->assertFalse($user->hasPermission('admin.users.manage'));
    }

    public function test_system_bypass_all_grants_every_permission(): void
    {
        $bypass = Permission::query()->create([
            'slug' => User::PERMISSION_BYPASS_ALL,
            'name' => 'Bypass',
            'module' => 'system',
        ]);
        $role = Role::query()->create(['slug' => 'super', 'name' => 'Super']);
        $role->permissions()->attach($bypass);

        $user = User::factory()->create(['role_id' => $role->id]);

        $this->assertTrue($user->hasPermission('expense_request.approve'));
        $this->assertTrue($user->hasPermission('admin.users.manage'));
        $this->assertTrue($user->hasPermission('any.random.thing'));
    }

    public function test_permission_slugs_are_cached_per_request(): void
    {
        $perm = Permission::query()->create([
            'slug' => 'budget.manage',
            'name' => 'Manage budgets',
            'module' => 'budgets',
        ]);
        $role = Role::query()->create(['slug' => 'role-y', 'name' => 'Y']);
        $role->permissions()->attach($perm);

        $user = User::factory()->create(['role_id' => $role->id]);

        $first = $user->permissionSlugs();
        $second = $user->permissionSlugs();

        $this->assertSame($first, $second);
        $this->assertContains('budget.manage', $first);
    }
}
