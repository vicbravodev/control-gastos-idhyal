<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        return [
            'budgetable_type' => 'user',
            'budgetable_id' => User::factory(),
            'period_starts_on' => $start,
            'period_ends_on' => $end,
            'amount_limit_cents' => 10_000_000,
            'priority' => 1,
        ];
    }

    public function forBudgetable(string $morphType, int $id): static
    {
        return $this->state(fn (): array => [
            'budgetable_type' => $morphType,
            'budgetable_id' => $id,
        ]);
    }
}
