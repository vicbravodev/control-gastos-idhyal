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

class AdminIndexFiltersHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_staff_users_index_supports_search_and_role_filters(): void
    {
        $admin = User::factory()->forRole('super_admin')->create();
        $asesorRole = Role::query()->where('slug', 'asesor')->firstOrFail();
        User::factory()->forRole('asesor')->create([
            'name' => 'Zeta Unique',
            'email' => 'zeta-user@example.com',
        ]);
        User::factory()->forRole('contabilidad')->create([
            'name' => 'Other Person',
            'email' => 'other@example.com',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', [
                'search' => 'zeta-user@',
                'role' => (string) $asesorRole->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/index')
                ->has('users', 1)
                ->where('users.0.email', 'zeta-user@example.com')
                ->where('filters.search', 'zeta-user@')
                ->where('filters.role', (string) $asesorRole->id)
                ->has('roles'));
    }

    public function test_states_index_supports_search_and_region_filters(): void
    {
        $admin = User::factory()->forRole('super_admin')->create();
        $r1 = Region::query()->create(['code' => 'R1', 'name' => 'Región Uno']);
        $r2 = Region::query()->create(['code' => 'R2', 'name' => 'Región Dos']);
        State::query()->create([
            'region_id' => $r1->id,
            'code' => 'MX-A',
            'name' => 'Alpha Estado',
        ]);
        State::query()->create([
            'region_id' => $r2->id,
            'code' => 'MX-B',
            'name' => 'Beta Estado',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.states.index', [
                'search' => 'Alpha',
                'region' => (string) $r1->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/states/index')
                ->has('states', 1)
                ->where('states.0.name', 'Alpha Estado')
                ->where('filters.search', 'Alpha')
                ->where('filters.region', (string) $r1->id)
                ->has('regions'));
    }

    public function test_regions_index_supports_search(): void
    {
        $admin = User::factory()->forRole('super_admin')->create();
        Region::query()->create(['code' => 'NTE', 'name' => 'Norte Filter']);
        Region::query()->create(['code' => 'SUR', 'name' => 'Sur Place']);

        $this->actingAs($admin)
            ->get(route('admin.regions.index', ['search' => 'Norte']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/regions/index')
                ->has('regions', 1)
                ->where('regions.0.code', 'NTE')
                ->where('filters.search', 'Norte'));
    }
}
