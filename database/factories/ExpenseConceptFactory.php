<?php

namespace Database\Factories;

use App\Models\ExpenseConcept;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseConcept>
 */
class ExpenseConceptFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
