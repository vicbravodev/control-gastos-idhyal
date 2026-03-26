<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VacationEntitlement;
use App\Models\VacationRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VacationEntitlement>
 */
class VacationEntitlementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'calendar_year' => (int) now()->year,
            'days_allocated' => 12,
            'days_used' => 0,
            'vacation_rule_id' => VacationRule::factory(),
        ];
    }
}
