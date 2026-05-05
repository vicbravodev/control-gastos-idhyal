<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminDepartmentsHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_guest_is_redirected_from_admin_departments(): void
    {
        $this->get(route('admin.departments.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_super_admin_cannot_view_admin_departments(): void
    {
        $user = User::factory()->forRole('asesor')->create();

        $this->actingAs($user)
            ->get(route('admin.departments.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_view_admin_departments_index(): void
    {
        $admin = User::factory()->forRole('super_admin')->create();
        Department::query()->create(['code' => 'TI', 'name' => 'Tecnología']);

        $this->actingAs($admin)
            ->get(route('admin.departments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/departments/index')
                ->has('departments', 1));
    }

    public function test_super_admin_can_create_department(): void
    {
        $admin = User::factory()->forRole('super_admin')->create();

        $this->actingAs($admin)
            ->post(route('admin.departments.store'), [
                'code' => 'CONT',
                'name' => 'Contabilidad',
                'is_active' => true,
                'position' => 1,
            ])
            ->assertRedirect(route('admin.departments.index'));

        $this->assertDatabaseHas('departments', [
            'code' => 'CONT',
            'name' => 'Contabilidad',
            'is_active' => true,
            'position' => 1,
        ]);
    }

    public function test_department_code_must_be_unique(): void
    {
        $admin = User::factory()->forRole('super_admin')->create();
        Department::query()->create(['code' => 'TI', 'name' => 'Tecnología']);

        $this->actingAs($admin)
            ->from(route('admin.departments.create'))
            ->post(route('admin.departments.store'), [
                'code' => 'TI',
                'name' => 'Otro',
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_super_admin_can_update_department(): void
    {
        $admin = User::factory()->forRole('super_admin')->create();
        $department = Department::query()->create([
            'code' => 'TI',
            'name' => 'Tecnología',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.departments.update', $department), [
                'code' => 'TIC',
                'name' => 'Tecnologías de Información',
                'is_active' => false,
                'position' => 5,
            ])
            ->assertRedirect(route('admin.departments.index'));

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'code' => 'TIC',
            'name' => 'Tecnologías de Información',
            'is_active' => false,
            'position' => 5,
        ]);
    }

    public function test_cannot_delete_department_with_users(): void
    {
        $admin = User::factory()->forRole('super_admin')->create();
        $department = Department::query()->create([
            'code' => 'TI',
            'name' => 'Tecnología',
        ]);
        User::factory()->create(['department_id' => $department->id]);

        $this->actingAs($admin)
            ->delete(route('admin.departments.destroy', $department))
            ->assertRedirect(route('admin.departments.index'))
            ->assertSessionHasErrors('department');

        $this->assertModelExists($department);
    }

    public function test_super_admin_can_delete_empty_department(): void
    {
        $admin = User::factory()->forRole('super_admin')->create();
        $department = Department::query()->create([
            'code' => 'TI',
            'name' => 'Tecnología',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.departments.destroy', $department))
            ->assertRedirect(route('admin.departments.index'));

        $this->assertModelMissing($department);
    }

    public function test_user_can_be_assigned_to_department(): void
    {
        $admin = User::factory()->forRole('super_admin')->create();
        $department = Department::query()->create([
            'code' => 'OPS',
            'name' => 'Operaciones',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Empleado',
                'email' => 'emp@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'hire_date' => '2024-06-01',
                'department_id' => $department->id,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'emp@example.com',
            'department_id' => $department->id,
        ]);
    }
}
