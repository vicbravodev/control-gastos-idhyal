<?php

namespace Database\Factories;

use App\Enums\ApprovalApproverType;
use App\Enums\ApprovalStepMode;
use App\Models\ApprovalPolicy;
use App\Models\ApprovalPolicyStep;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
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
            'approver_type' => ApprovalApproverType::Role,
            'approver_id' => Role::factory(),
            'step_mode' => ApprovalStepMode::Sequential,
        ];
    }

    public function forRole(Role|int $role): static
    {
        return $this->state(fn (): array => [
            'approver_type' => ApprovalApproverType::Role,
            'approver_id' => $role instanceof Role ? $role->id : $role,
        ]);
    }

    public function forDepartment(Department|int $department): static
    {
        return $this->state(fn (): array => [
            'approver_type' => ApprovalApproverType::Department,
            'approver_id' => $department instanceof Department ? $department->id : $department,
        ]);
    }

    public function forUser(User|int $user): static
    {
        return $this->state(fn (): array => [
            'approver_type' => ApprovalApproverType::User,
            'approver_id' => $user instanceof User ? $user->id : $user,
        ]);
    }

    public function mode(ApprovalStepMode $mode): static
    {
        return $this->state(fn (): array => [
            'step_mode' => $mode,
        ]);
    }
}
