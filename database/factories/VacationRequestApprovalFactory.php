<?php

namespace Database\Factories;

use App\Enums\ApprovalApproverType;
use App\Enums\ApprovalInstanceStatus;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Models\VacationRequest;
use App\Models\VacationRequestApproval;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VacationRequestApproval>
 */
class VacationRequestApprovalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vacation_request_id' => VacationRequest::factory(),
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
