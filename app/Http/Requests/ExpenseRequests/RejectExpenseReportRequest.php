<?php

namespace App\Http\Requests\ExpenseRequests;

use App\Models\ExpenseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RejectExpenseReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ExpenseRequest $expenseRequest */
        $expenseRequest = $this->route('expenseRequest');

        return $this->user()->can('reviewExpenseReport', $expenseRequest);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'note.required' => __('La nota de rechazo es obligatoria.'),
        ];
    }
}
