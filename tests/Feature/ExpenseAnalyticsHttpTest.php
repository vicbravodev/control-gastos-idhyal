<?php

namespace Tests\Feature;

use App\Enums\ExpenseRequestStatus;
use App\Models\ExpenseRequest;
use App\Models\Region;
use App\Models\ReportTemplate;
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
                ->has('kpis.total_count')
                ->has('kpis.total_requested_cents')
                ->has('kpis.total_approved_cents')
                ->has('kpis.total_paid_cents')
                ->has('byStatus', 13)
                ->has('templates')
                ->has('period.id')
                ->has('period.range_label')
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
                ->where('kpis.total_count', 1)
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
                ->where('kpis.total_count', 2)
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
                ->where('kpis.total_count', 1)
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
                ->where('kpis.total_count', 1)
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

    public function test_contabilidad_can_export_csv(): void
    {
        $this->seedRoles();

        $user = User::factory()->forRole('contabilidad')->create();

        ExpenseRequest::factory()->count(2)->create([
            'status' => ExpenseRequestStatus::Submitted,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.expenses.export', ['format' => 'csv']));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $body = $response->streamedContent();
        $this->assertStringContainsString('Folio,Solicitante', $body);
    }

    public function test_by_status_returns_all_status_cases(): void
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
                ->where('kpis.total_count', 6)
                ->has('byStatus', 13)
            );
    }

    public function test_kpis_include_previous_period_when_compare_enabled(): void
    {
        $this->seedRoles();

        $user = User::factory()->forRole('contabilidad')->create();

        ExpenseRequest::factory()->create([
            'status' => ExpenseRequestStatus::Submitted,
            'requested_amount_cents' => 100_00,
            'created_at' => now()->subDays(2),
        ]);
        ExpenseRequest::factory()->create([
            'status' => ExpenseRequestStatus::Submitted,
            'requested_amount_cents' => 50_00,
            'created_at' => now()->subDays(35),
        ]);

        $this->actingAs($user)
            ->get(route('reports.expenses.index', [
                'period' => 'l30',
                'compare' => '1',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('reports/index')
                ->where('compare', true)
                ->where('kpis.total_count', 1)
                ->where('kpis.total_requested_cents', 100_00)
                ->where('kpis.total_count_prev', 1)
                ->where('kpis.total_requested_cents_prev', 50_00)
                ->has('kpis.total_requested_cents_delta_pct')
            );
    }

    public function test_template_id_loads_filters(): void
    {
        $this->seedRoles();

        $user = User::factory()->forRole('contabilidad')->create();

        $template = ReportTemplate::query()->create([
            'owner_user_id' => null,
            'slug' => 'test-builtin',
            'name' => 'Test built-in',
            'description' => null,
            'icon' => 'bookmark',
            'view' => 'pivote',
            'group_by' => 'concepto',
            'filters' => ['status' => 'approved', 'period' => 'ytd'],
            'is_built_in' => true,
            'is_shared' => true,
        ]);

        ExpenseRequest::factory()->create(['status' => ExpenseRequestStatus::Approved]);
        ExpenseRequest::factory()->create(['status' => ExpenseRequestStatus::Submitted]);

        $this->actingAs($user)
            ->get(route('reports.expenses.index', ['template_id' => $template->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('reports/index')
                ->where('view', 'pivote')
                ->where('group_by', 'concepto')
                ->where('filters.status', 'approved')
                ->where('kpis.total_count', 1)
                ->where('active_template_id', $template->id)
                ->has('byDimension')
            );
    }

    public function test_detail_view_returns_paginated_rows(): void
    {
        $this->seedRoles();

        $user = User::factory()->forRole('contabilidad')->create();
        ExpenseRequest::factory()->count(2)->create(['status' => ExpenseRequestStatus::Submitted]);

        $this->actingAs($user)
            ->get(route('reports.expenses.index', ['view' => 'detalle']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('reports/index')
                ->where('view', 'detalle')
                ->has('expenseRequests.data', 2)
            );
    }
}
