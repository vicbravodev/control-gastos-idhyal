<?php

namespace Database\Factories;

use App\Enums\DeliveryMethod;
use App\Enums\ExpenseRequestStatus;
use App\Models\ExpenseConcept;
use App\Models\ExpenseRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseRequest>
 */
class ExpenseRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => ExpenseRequestStatus::Submitted,
            'folio' => null,
            'requested_amount_cents' => fake()->randomElement([10_000, 50_000, 100_000]),
            'approved_amount_cents' => null,
            'expense_concept_id' => ExpenseConcept::factory(),
            'concept_description' => fake()->optional()->sentence(),
            'delivery_method' => DeliveryMethod::Cash,
        ];
    }
}
