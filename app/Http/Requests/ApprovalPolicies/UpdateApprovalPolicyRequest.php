<?php

namespace App\Http\Requests\ApprovalPolicies;

use App\Enums\ApprovalPolicyDocumentType;
use App\Enums\CombineWithNext;
use App\Models\ApprovalPolicy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'requester_role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['boolean'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.role_id' => ['required', 'integer', 'exists:roles,id'],
            'steps.*.combine_with_next' => ['required', 'string', Rule::enum(CombineWithNext::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'steps.required' => 'Debe agregar al menos un paso de aprobación.',
            'steps.min' => 'Debe agregar al menos un paso de aprobación.',
            'steps.*.role_id.required' => 'Cada paso debe tener un rol asignado.',
            'steps.*.role_id.exists' => 'El rol seleccionado no es válido.',
            'effective_to.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ];
    }
}
