<?php

namespace Database\Factories;

use App\Enums\ApprovalApproverType;
use App\Enums\ApprovalPolicyDocumentType;
use App\Models\ApprovalPolicy;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalPolicy>
 */
class ApprovalPolicyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_type' => ApprovalPolicyDocumentType::ExpenseRequest,
            'name' => fake()->words(3, true),
            'version' => 1,
            'applies_to_type' => null,
            'applies_to_id' => null,
            'effective_from' => null,
            'effective_to' => null,
            'is_active' => true,
        ];
    }

    public function forDocumentType(ApprovalPolicyDocumentType $type): static
    {
        return $this->state(fn (): array => [
            'document_type' => $type,
        ]);
    }

    public function appliesToRole(Role|int|null $role): static
    {
        $id = $role instanceof Role ? $role->id : $role;

        return $this->state(fn (): array => [
            'applies_to_type' => $id !== null ? ApprovalApproverType::Role : null,
            'applies_to_id' => $id,
        ]);
    }

    public function appliesToDepartment(Department|int $department): static
    {
        return $this->state(fn (): array => [
            'applies_to_type' => ApprovalApproverType::Department,
            'applies_to_id' => $department instanceof Department ? $department->id : $department,
        ]);
    }

    public function appliesToUser(User|int $user): static
    {
        return $this->state(fn (): array => [
            'applies_to_type' => ApprovalApproverType::User,
            'applies_to_id' => $user instanceof User ? $user->id : $user,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
