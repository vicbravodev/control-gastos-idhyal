<?php

namespace Database\Factories;

use App\Enums\CombineWithNext;
use App\Models\ApprovalPolicy;
use App\Models\ApprovalPolicyStep;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalPolicyStep>
 */
class ApprovalPolicyStepFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'approval_policy_id' => ApprovalPolicy::factory(),
            'step_order' => 1,
            'role_id' => Role::factory(),
            'combine_with_next' => CombineWithNext::And,
        ];
    }

    public function combineWithNext(CombineWithNext $combine): static
    {
        return $this->state(fn (): array => [
            'combine_with_next' => $combine,
        ]);
    }
}
