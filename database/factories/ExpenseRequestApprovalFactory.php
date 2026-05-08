<?php

namespace Database\Factories;

use App\Enums\ApprovalApproverType;
use App\Enums\ApprovalInstanceStatus;
use App\Models\Department;
use App\Models\ExpenseRequest;
use App\Models\ExpenseRequestApproval;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseRequestApproval>
 */
class ExpenseRequestApprovalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expense_request_id' => ExpenseRequest::factory(),
            'step_order' => 1,
            'approver_type' => ApprovalApproverType::Role,
            'approver_id' => Role::factory(),
            'status' => ApprovalInstanceStatus::Pending,
            'approver_user_id' => null,
            'note' => null,
            'acted_at' => null,
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
}
