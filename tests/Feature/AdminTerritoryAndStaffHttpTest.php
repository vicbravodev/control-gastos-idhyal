<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\Role;
use App\Models\State;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminTerritoryAndStaffHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_guest_is_redirected_from_admin_regions(): void
    {
        $this->get(route('admin.regions.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_super_admin_cannot_view_admin_regions(): void
    {
        $user = User::factory()->forRole('asesor')->create();

        $this->actingAs($user)
            ->get(route('admin.regions.index'))
            ->assertForbidden();
    }

    public function test_secretario_general_cannot_view_admin_regions(): void
    {
        $user = User::factory()->forRole('secretario_general')->create();

        $this->actingAs($user)
            ->get(route('admin.regions.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_view_admin_regions_index(): void
    {
        $admin = User::factory()->forRole('super_admin')->create();

        Region::query()->create(['code' => 'R1', 'name' => 'Región 1']);

        $this->actingAs($admin)
            ->get(route('admin.regions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/regions/index')
                ->has('regions', 1));
    }

    public function test_super_admin_can_create_region(): void
    {
        $admin = User::factory()->forRole('super_admin')->create();

        $this->actingAs($admin)
            ->post(route('admin.regions.store'), [
                'code' => 'NORTE',
                'name' => 'Región Norte',
            ])
            ->assertRedirect(route('admin.regions.index'));

        $this->assertDatabaseHas('regions', [
            'code' => 'NORTE',
            'name' => 'Región Norte',
        ]);
    }

    public function test_super_admin_can_list_states_json_for_region(): void
    {
        $admin = User::factory()->forRole('super_admin')->create();
        $region = Region::query()->create(['code' => 'R', 'name' => 'R']);
        $state = State::query()->create([
            'region_id' => $region->id,
            'code' => 'NL',
            'name' => 'Nuevo León',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.regions.states', $region))
            ->assertOk()
            ->assertJsonPath('data.0.id', $state->id)
            ->assertJsonPath('data.0.name', 'Nuevo León');
    }

    public function test_non_super_admin_cannot_access_region_states_json(): void
    {
        $user = User::factory()->forRole('asesor')->create();
        $region = Region::query()->create(['code' => 'R', 'name' => 'R']);

        $this->actingAs($user)
            ->getJson(route('admin.regions.states', $region))
            ->assertForbidden();
    }

    public function test_super_admin_can_create_user_with_matching_region_and_state(): void
    {
        $admin = User::factory()->forRole('super_admin')->create();
        $asesorRole = Role::query()->where('slug', 'asesor')->firstOrFail();
        $region = Region::query()->create(['code' => 'R', 'name' => 'R']);
        $state = State::query()->create([
            'region_id' => $region->id,
            'code' => 'S',
            'name' => 'Estado S',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Usuario Nuevo',
                'email' => 'nuevo-staff@example.com',
                'username' => 'nuevo_staff',
                'phone' => null,
                'password' => 'password',
                'password_confirmation' => 'password',
                'hire_date' => '2024-01-15',
                'role_id' => $asesorRole->id,
                'region_id' => $region->id,
                'state_id' => $state->id,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'nuevo-staff@example.com',
            'role_id' => $asesorRole->id,
            'region_id' => $region->id,
            'state_id' => $state->id,
        ]);
        $this->assertTrue(
            User::query()
                ->where('email', 'nuevo-staff@example.com')
                ->whereDate('hire_date', '2024-01-15')
                ->exists(),
        );
    }

    public function test_staff_store_rejects_state_not_in_region(): void
    {
        $admin = User::factory()->forRole('super_admin')->create();
        $regionA = Region::query()->create(['code' => 'A', 'name' => 'A']);
        $regionB = Region::query()->create(['code' => 'B', 'name' => 'B']);
        $stateInA = State::query()->create([
            'region_id' => $regionA->id,
            'code' => 'SA',
            'name' => 'En A',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => 'X',
                'email' => 'x@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'hire_date' => '2023-06-01',
                'role_id' => null,
                'region_id' => $regionB->id,
                'state_id' => $stateInA->id,
            ])
            ->assertSessionHasErrors('state_id');
    }

    public function test_cannot_delete_region_with_states(): void
    {
        $admin = User::factory()->forRole('super_admin')->create();
        $region = Region::query()->create(['code' => 'R', 'name' => 'R']);
        State::query()->create([
            'region_id' => $region->id,
            'code' => 'S',
            'name' => 'S',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.regions.destroy', $region))
            ->assertRedirect(route('admin.regions.index'))
            ->assertSessionHasErrors('region');

        $this->assertModelExists($region);
    }

    public function test_manage_staff_directory_gate_is_false_for_non_super_admin(): void
    {
        $user = User::factory()->forRole('contabilidad')->create();

        $this->assertFalse($user->can('manageStaffDirectory', User::class));
    }

    public function test_manage_staff_directory_gate_is_true_for_super_admin(): void
    {
        $user = User::factory()->forRole('super_admin')->create();

        $this->assertTrue($user->can('manageStaffDirectory', User::class));
    }
}
