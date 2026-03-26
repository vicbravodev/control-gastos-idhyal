<?php

namespace Database\Factories;

use App\Enums\ApprovalPolicyDocumentType;
use App\Models\ApprovalPolicy;
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
            'requester_role_id' => null,
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

    public function forRequesterRole(?int $roleId): static
    {
        return $this->state(fn (): array => [
            'requester_role_id' => $roleId,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
