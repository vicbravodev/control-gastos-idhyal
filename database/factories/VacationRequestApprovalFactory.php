<?php

namespace Database\Factories;

use App\Enums\ApprovalInstanceStatus;
use App\Models\Role;
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
            'role_id' => Role::factory(),
            'status' => ApprovalInstanceStatus::Pending,
            'approver_user_id' => null,
            'note' => null,
            'acted_at' => null,
        ];
    }
}
