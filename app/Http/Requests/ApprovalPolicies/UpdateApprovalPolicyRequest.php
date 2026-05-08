<?php

namespace App\Http\Requests\ApprovalPolicies;

use App\Enums\ApprovalApproverType;
use App\Enums\ApprovalPolicyDocumentType;
use App\Enums\ApprovalStepMode;
use App\Rules\PolymorphicTargetExists;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateApprovalPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('approval_policy'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', Rule::enum(ApprovalPolicyDocumentType::class)],
            'name' => ['required', 'string', 'max:255'],
            'version' => ['required', 'integer', 'min:1'],
            'applies_to_type' => ['nullable', 'string', Rule::enum(ApprovalApproverType::class)],
            'applies_to_id' => [
                'nullable',
                'integer',
                'required_with:applies_to_type',
                new PolymorphicTargetExists('applies_to_type'),
            ],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['boolean'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.approver_type' => ['required', 'string', Rule::enum(ApprovalApproverType::class)],
            'steps.*.approver_id' => ['required', 'integer'],
            'steps.*.step_mode' => ['required', 'string', Rule::enum(ApprovalStepMode::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $steps = $this->input('steps', []);

            foreach ($steps as $index => $step) {
                $approverType = ApprovalApproverType::tryFrom($step['approver_type'] ?? '');
                if ($approverType === null) {
                    continue;
                }

                $rule = new PolymorphicTargetExists("steps.$index.approver_type");
                $rule->setData($this->all());
                $rule->validate(
                    "steps.$index.approver_id",
                    $step['approver_id'] ?? null,
                    fn (string $message) => $validator->errors()->add("steps.$index.approver_id", $message),
                );
            }

            $groups = $this->groupModes($steps);
            foreach ($groups as $modes) {
                $unique = array_unique($modes);
                if (count($unique) > 1) {
                    $validator->errors()->add(
                        'steps',
                        'No puede combinar "Cualquiera de" con "Todos deben" en el mismo grupo de aprobación.',
                    );
                }
            }
        });
    }

    /**
     * @param  list<array<string, mixed>>  $steps
     * @return list<list<string>>
     */
    private function groupModes(array $steps): array
    {
        $groups = [];
        $current = [];

        foreach ($steps as $step) {
            $mode = $step['step_mode'] ?? null;
            if ($mode === ApprovalStepMode::Sequential->value) {
                if ($current !== []) {
                    $groups[] = $current;
                }
                $current = [];
            } else {
                $current[] = $mode;
            }
        }

        if ($current !== []) {
            $groups[] = $current;
        }

        return $groups;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'steps.required' => 'Debe agregar al menos un paso de aprobación.',
            'steps.min' => 'Debe agregar al menos un paso de aprobación.',
            'steps.*.approver_type.required' => 'Cada paso debe especificar el tipo de aprobador.',
            'steps.*.approver_id.required' => 'Cada paso debe tener un aprobador asignado.',
            'steps.*.step_mode.required' => 'Cada paso debe especificar cómo se combina con el siguiente.',
            'effective_to.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'applies_to_id.required_with' => 'Debe seleccionar el destino específico cuando elige un tipo.',
        ];
    }
}
