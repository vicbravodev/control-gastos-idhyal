<?php

namespace Tests\Unit;

use App\Models\Budget;
use App\Models\ExpenseRequest;
use App\Models\Region;
use App\Models\State;
use App\Models\User;
use App\Services\Budgets\ExpenseRequestBudgetResolver;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseRequestBudgetResolverTest extends TestCase
{
    use RefreshDatabase;

    private function widePeriod(): array
    {
        return [
            'period_starts_on' => now()->subYear()->toDateString(),
            'period_ends_on' => now()->addYear()->toDateString(),
        ];
    }

    public function test_request_state_id_overrides_user_state_id_for_budget_lookup(): void
    {
        $this->seed(RoleSeeder::class);

        $region = Region::query()->create(['code' => 'RMS', 'name' => 'Región MS']);
        $stateA = State::query()->create(['code' => 'SA', 'name' => 'Estado A', 'region_id' => $region->id]);
        $stateB = State::query()->create(['code' => 'SB', 'name' => 'Estado B', 'region_id' => $region->id]);

        // Coord regional: tiene region pero NO state_id
        $user = User::factory()->forRole('coord_regional')->create([
            'region_id' => $region->id,
            'state_id' => null,
        ]);

        $budgetA = Budget::factory()->forBudgetable('state', $stateA->id)->create([
            ...$this->widePeriod(),
            'amount_limit_cents' => 50_000_000,
            'priority' => 10,
        ]);

        $budgetB = Budget::factory()->forBudgetable('state', $stateB->id)->create([
            ...$this->widePeriod(),
            'amount_limit_cents' => 50_000_000,
            'priority' => 10,
        ]);

        $expenseToA = ExpenseRequest::factory()->create([
            'user_id' => $user->id,
            'state_id' => $stateA->id,
        ]);

        $expenseToB = ExpenseRequest::factory()->create([
            'user_id' => $user->id,
            'state_id' => $stateB->id,
        ]);

        $resolvedA = app(ExpenseRequestBudgetResolver::class)->resolve($expenseToA);
        $resolvedB = app(ExpenseRequestBudgetResolver::class)->resolve($expenseToB);

        $this->assertSame($budgetA->id, $resolvedA?->id);
        $this->assertSame($budgetB->id, $resolvedB?->id);
    }

    public function test_higher_priority_budget_wins(): void
    {
        $this->seed(RoleSeeder::class);

        $region = Region::query()->create([
            'code' => 'R1',
            'name' => 'Región 1',
        ]);

        $user = User::factory()->forRole('asesor')->create([
            'region_id' => $region->id,
        ]);

        $high = Budget::factory()->forBudgetable('region', $region->id)->create([
            ...$this->widePeriod(),
            'amount_limit_cents' => 50_000_000,
            'priority' => 100,
        ]);

        Budget::factory()->forBudgetable('user', $user->id)->create([
            ...$this->widePeriod(),
            'amount_limit_cents' => 50_000_000,
            'priority' => 50,
        ]);

        $expense = ExpenseRequest::factory()->create(['user_id' => $user->id]);

        $resolved = app(ExpenseRequestBudgetResolver::class)->resolve($expense);

        $this->assertNotNull($resolved);
        $this->assertSame($high->id, $resolved->id);
    }

    public function test_same_priority_prefers_user_scope_over_region(): void
    {
        $this->seed(RoleSeeder::class);

        $region = Region::query()->create([
            'code' => 'R2',
            'name' => 'Región 2',
        ]);

        $user = User::factory()->forRole('asesor')->create([
            'region_id' => $region->id,
        ]);

        Budget::factory()->forBudgetable('region', $region->id)->create([
            ...$this->widePeriod(),
            'amount_limit_cents' => 50_000_000,
            'priority' => 10,
        ]);

        $userBudget = Budget::factory()->forBudgetable('user', $user->id)->create([
            ...$this->widePeriod(),
            'amount_limit_cents' => 50_000_000,
            'priority' => 10,
        ]);

        $expense = ExpenseRequest::factory()->create(['user_id' => $user->id]);

        $resolved = app(ExpenseRequestBudgetResolver::class)->resolve($expense);

        $this->assertNotNull($resolved);
        $this->assertSame($userBudget->id, $resolved->id);
    }

    public function test_cancelled_budgets_are_excluded(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->forRole('asesor')->create();

        Budget::factory()->forBudgetable('user', $user->id)->cancelled()->create([
            ...$this->widePeriod(),
            'amount_limit_cents' => 50_000_000,
            'priority' => 100,
        ]);

        $expense = ExpenseRequest::factory()->create(['user_id' => $user->id]);

        $resolved = app(ExpenseRequestBudgetResolver::class)->resolve($expense);

        $this->assertNull($resolved);
    }

    public function test_returns_null_when_no_period_overlap(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->forRole('asesor')->create();

        Budget::factory()->forBudgetable('user', $user->id)->create([
            'period_starts_on' => now()->addMonth()->toDateString(),
            'period_ends_on' => now()->addMonths(2)->toDateString(),
            'amount_limit_cents' => 50_000_000,
            'priority' => 10,
        ]);

        $expense = ExpenseRequest::factory()->create(['user_id' => $user->id]);

        $resolved = app(ExpenseRequestBudgetResolver::class)->resolve($expense);

        $this->assertNull($resolved);
    }
}
