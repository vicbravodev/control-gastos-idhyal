<?php

namespace Database\Factories;

use App\Models\VacationRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VacationRule>
 */
class VacationRuleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('RULE_##??'),
            'name' => fake()->words(3, true),
            'min_years_service' => 1.0,
            'max_years_service' => null,
            'days_granted_per_year' => 12,
            'max_days_per_request' => null,
            'max_days_per_month' => null,
            'max_days_per_quarter' => null,
            'max_days_per_year' => null,
            'blackout_dates' => null,
            'sort_order' => 0,
        ];
    }
}
