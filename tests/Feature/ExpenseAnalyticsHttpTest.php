<?php

namespace Tests\Feature;

use App\Enums\ExpenseRequestStatus;
use App\Models\ExpenseConcept;
use App\Models\ExpenseRequest;
use App\Models\Region;
use App\Models\State;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExpenseAnalyticsHttpTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        $this->seed(RoleSeeder::class);
    }

    public function test_guests_cannot_access_expense_analytics(): void
    {
        $this->get(route('reports.expenses.index'))
            ->assertRedirect(route('login'));
    }

    public function test_asesor_cannot_access_expense_analytics(): void
    {
        $this->seedRoles();

        $user = User::factory()->forRole('asesor')->create();

        $this->actingAs($user)
            ->get(route('reports.expenses.index'))
            ->assertForbidden();
    }

    public function test_coord_regional_cannot_access_expense_analytics(): void
    {
        $this->seedRoles();

        $user = User::factory()->forRole('coord_regional')->create();

        $this->actingAs($user)
            ->get(route('reports.expenses.index'))
            ->assertForbidden();
    }

    public function test_contabilidad_can_access_expense_analytics(): void
    {
        $this->seedRoles();

        $user = User::factory()->forRole('contabilidad')->create();

        ExpenseRequest::factory()->count(3)->create([
            'status' => ExpenseRequestStatus::Submitted,
        ]);

        $this->actingAs($user)
            ->get(route('reports.expenses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('reports/index')
                ->has('summary')
                ->has('summary.total_count')
                ->has('summary.total_requested_cents')
                ->has('summary.total_approved_cents')
                ->has('summary.total_paid_cents')
                ->has('summary.by_status')
                ->has('expenseRequests')
                ->has('filters')
            );
    }

    public function test_super_admin_can_access_expense_analytics(): void
    {
        $this->seedRoles();

        $user = User::factory()->forRole('super_admin')->create();

        $this->actingAs($user)
            ->get(route('reports.expenses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('reports/index')
            );
    }

    public function test_analytics_filters_by_status(): void
    {
        $this->seedRoles();

        $user = User::factory()->forRole('contabilidad')->create();

        ExpenseRequest::factory()->create(['status' => ExpenseRequestStatus::Submitted]);
        ExpenseRequest::factory()->create(['status' => ExpenseRequestStatus::Approved]);
        ExpenseRequest::factory()->create(['status' => ExpenseRequestStatus::Rejected]);

        $this->actingAs($user)
            ->get(route('reports.expenses.index', ['status' => 'approved']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('reports/index')
                ->where('summary.total_count', 1)
                ->where('filters.status', 'approved')
            );
    }

    public function test_analytics_filters_by_date_range(): void
    {
        $this->seedRoles();

        $user = User::factory()->forRole('contabilidad')->create();

        ExpenseRequest::factory()->create([
            'status' => ExpenseRequestStatus::Submitted,
            'created_at' => now()->subDays(10),
        ]);
        ExpenseRequest::factory()->create([
            'status' => ExpenseRequestStatus::Submitted,
            'created_at' => now()->subDays(2),
        ]);
        ExpenseRequest::factory()->create([
            'status' => ExpenseRequestStatus::Submitted,
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('reports.expenses.index', [
                'date_from' => now()->subDays(3)->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('reports/index')
                ->where('summary.total_count', 2)
            );
    }

    public function test_analytics_filters_by_region(): void
    {
        $this->seedRoles();

        $user = User::factory()->forRole('contabilidad')->create();
        $region = Region::query()->create(['code' => 'NOR', 'name' => 'Norte']);
        $state = State::query()->create(['region_id' => $region->id, 'code' => 'NL', 'name' => 'Nuevo León']);
        $regionUser = User::factory()->create([
            'region_id' => $region->id,
            'state_id' => $state->id,
        ]);

        ExpenseRequest::factory()->create([
            'user_id' => $regionUser->id,
            'status' => ExpenseRequestStatus::Submitted,
        ]);
        ExpenseRequest::factory()->create([
            'status' => ExpenseRequestStatus::Submitted,
        ]);

        $this->actingAs($user)
            ->get(route('reports.expenses.index', ['region_id' => $region->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('reports/index')
                ->where('summary.total_count', 1)
            );
    }

    public function test_analytics_filters_by_user(): void
    {
        $this->seedRoles();

        $contabilidad = User::factory()->forRole('contabilidad')->create();
        $targetUser = User::factory()->create();

        ExpenseRequest::factory()->create([
            'user_id' => $targetUser->id,
            'status' => ExpenseRequestStatus::Submitted,
        ]);
        ExpenseRequest::factory()->count(2)->create([
            'status' => ExpenseRequestStatus::Submitted,
        ]);

        $this->actingAs($contabilidad)
            ->get(route('reports.expenses.index', ['user_id' => $targetUser->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('reports/index')
                ->where('summary.total_count', 1)
            );
    }

    public function test_guest_cannot_export_pdf(): void
    {
        $this->get(route('reports.expenses.export-pdf'))
            ->assertRedirect(route('login'));
    }

    public function test_asesor_cannot_export_pdf(): void
    {
        $this->seedRoles();

        $user = User::factory()->forRole('asesor')->create();

        $this->actingAs($user)
            ->get(route('reports.expenses.export-pdf'))
            ->assertForbidden();
    }

    public function test_contabilidad_can_export_pdf(): void
    {
        $this->seedRoles();

        $user = User::factory()->forRole('contabilidad')->create();

        ExpenseRequest::factory()->count(2)->create([
            'status' => ExpenseRequestStatus::Submitted,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.expenses.export-pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_summary_counts_match_statuses(): void
    {
        $this->seedRoles();

        $user = User::factory()->forRole('contabilidad')->create();

        ExpenseRequest::factory()->count(2)->create(['status' => ExpenseRequestStatus::Submitted]);
        ExpenseRequest::factory()->count(3)->create(['status' => ExpenseRequestStatus::Approved]);
        ExpenseRequest::factory()->create(['status' => ExpenseRequestStatus::Rejected]);

        $this->actingAs($user)
            ->get(route('reports.expenses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('reports/index')
                ->where('summary.total_count', 6)
                ->has('summary.by_status', 3)
            );
    }

    public function test_analytics_loads_filter_options_lazily(): void
    {
        $this->seedRoles();

        $user = User::factory()->forRole('contabilidad')->create();

        $this->actingAs($user)
            ->get(route('reports.expenses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('reports/index')
                ->has('filters')
            );
    }
}
