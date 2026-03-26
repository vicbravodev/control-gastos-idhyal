<?php

namespace Database\Factories;

use App\Enums\VacationRequestStatus;
use App\Models\User;
use App\Models\VacationRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VacationRequest>
 */
class VacationRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => VacationRequestStatus::Draft,
            'folio' => null,
            'starts_on' => now()->startOfMonth(),
            'ends_on' => now()->startOfMonth()->addDays(4),
            'business_days_count' => 5,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (): array => [
            'status' => VacationRequestStatus::Submitted,
        ]);
    }
}
