<?php

namespace App\Http\Requests\ExpenseConcepts;

use App\Models\ExpenseConcept;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseConceptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ExpenseConcept::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('expense_concepts', 'name')],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
