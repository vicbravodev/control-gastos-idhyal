<?php

namespace App\Http\Requests\ExpenseConcepts;

use App\Models\ExpenseConcept;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseConceptRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ExpenseConcept $expenseConcept */
        $expenseConcept = $this->route('expense_concept');

        return $this->user()->can('update', $expenseConcept);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var ExpenseConcept $expenseConcept */
        $expenseConcept = $this->route('expense_concept');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('expense_concepts', 'name')->ignore($expenseConcept->id),
            ],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
