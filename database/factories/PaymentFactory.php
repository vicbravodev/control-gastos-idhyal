<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\ExpenseRequest;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expense_request_id' => ExpenseRequest::factory(),
            'recorded_by_user_id' => User::factory(),
            'amount_cents' => fake()->randomElement([10_000, 50_000, 100_000]),
            'payment_method' => PaymentMethod::Transfer,
            'paid_on' => now()->toDateString(),
            'transfer_reference' => fake()->optional()->numerify('TRF-########'),
        ];
    }
}
