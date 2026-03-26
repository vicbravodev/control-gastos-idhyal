<?php

namespace Database\Factories;

use App\Enums\ApprovalInstanceStatus;
use App\Models\ExpenseRequest;
use App\Models\ExpenseRequestApproval;
use App\Models\Role;
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
            'role_id' => Role::factory(),
            'status' => ApprovalInstanceStatus::Pending,
            'approver_user_id' => null,
            'note' => null,
            'acted_at' => null,
        ];
    }
}
