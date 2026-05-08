<?php

namespace App\Rules;

use App\Enums\ApprovalApproverType;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that an (approver_type, approver_id) pair points at an
 * existing record in the appropriate table.
 */
class PolymorphicTargetExists implements DataAwareRule, ValidationRule
{
    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    public function __construct(
        protected string $typeAttribute,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $type = data_get($this->data, $this->typeAttribute);

        if (! is_string($type) || ! is_numeric($value)) {
            return;
        }

        $approverType = ApprovalApproverType::tryFrom($type);
        if ($approverType === null) {
            return;
        }

        $exists = match ($approverType) {
            ApprovalApproverType::Role => Role::query()->whereKey((int) $value)->exists(),
            ApprovalApproverType::Department => Department::query()->whereKey((int) $value)->exists(),
            ApprovalApproverType::User => User::query()->whereKey((int) $value)->exists(),
        };

        if (! $exists) {
            $fail('El destino seleccionado no existe.');
        }
    }
}
