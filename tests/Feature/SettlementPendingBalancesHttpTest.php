<?php

namespace Tests\Feature;

use App\Enums\ExpenseReportStatus;
use App\Enums\ExpenseRequestStatus;
use App\Enums\SettlementStatus;
use App\Models\ExpenseReport;
use App\Models\ExpenseRequest;
use App\Models\Settlement;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SettlementPendingBalancesHttpTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        $this->seed(RoleSeeder::class);
    }

    private function makeSettlementPendingTrayRequest(SettlementStatus $settlementStatus): ExpenseRequest
    {
        $requester = User::factory()->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $requester->id,
            'status' => ExpenseRequestStatus::SettlementPending,
            'folio' => 'BAL-TRAY-1',
            'concept_description' => 'Concepto bandeja balances',
        ]);
        $report = ExpenseReport::factory()->create([
            'expense_request_id' => $expenseRequest->id,
            'status' => ExpenseReportStatus::Approved,
            'reported_amount_cents' => 80_000,
            'submitted_at' => now(),
        ]);
        Settlement::query()->create([
            'expense_report_id' => $report->id,
            'status' => $settlementStatus,
            'basis_amount_cents' => 100_000,
            'reported_amount_cents' => 80_000,
            'difference_cents' => 20_000,
        ]);

        return $expenseRequest->fresh();
    }

    public function test_guest_cannot_view_pending_balances_tray(): void
    {
        $this->get(route('expense-requests.settlements.pending-balances'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_oversight_forbidden_from_pending_balances_tray(): void
    {
        $this->seedRoles();
        $user = User::factory()->forRole('asesor')->create();

        $this->actingAs($user)
            ->get(route('expense-requests.settlements.pending-balances'))
            ->assertForbidden();
    }

    public function test_contabilidad_can_view_pending_balances_tray(): void
    {
        $this->seedRoles();
        $expenseRequest = $this->makeSettlementPendingTrayRequest(SettlementStatus::PendingUserReturn);
        $accounting = User::factory()->forRole('contabilidad')->create();

        $this->actingAs($accounting)
            ->get(route('expense-requests.settlements.pending-balances'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('expense-requests/settlements/pending-balances')
                ->has('expenseRequests.data', 1)
                ->has('filters')
                ->where('expenseRequests.data.0.id', $expenseRequest->id)
                ->where('expenseRequests.data.0.settlement.status', SettlementStatus::PendingUserReturn->value));
    }

    public function test_coord_regional_with_oversight_can_view_pending_balances_tray(): void
    {
        $this->seedRoles();
        $expenseRequest = $this->makeSettlementPendingTrayRequest(SettlementStatus::PendingCompanyPayment);
        $coord = User::factory()->forRole('coord_regional')->create();

        $this->actingAs($coord)
            ->get(route('expense-requests.settlements.pending-balances'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('expenseRequests.data', 1)
                ->where('expenseRequests.data.0.id', $expenseRequest->id));
    }

    public function test_tray_excludes_settled_balances_still_awaiting_request_close(): void
    {
        $this->seedRoles();
        $this->makeSettlementPendingTrayRequest(SettlementStatus::Settled);
        $accounting = User::factory()->forRole('contabilidad')->create();

        $this->actingAs($accounting)
            ->get(route('expense-requests.settlements.pending-balances'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('expenseRequests.data', 0));
    }
}
