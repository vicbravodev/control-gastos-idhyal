<?php

namespace App\Http\Requests\ExpenseRequests;

use App\Models\ExpenseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseReportDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ExpenseRequest $expenseRequest */
        $expenseRequest = $this->route('expenseRequest');

        return $this->user()->can('saveExpenseReportDraft', $expenseRequest);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reported_amount_cents' => ['required', 'integer', 'min:1'],
            'pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'xml' => ['nullable', 'file', 'mimes:xml', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pdf.mimes' => __('El archivo PDF debe ser un PDF válido.'),
            'xml.mimes' => __('El archivo XML debe ser XML válido.'),
        ];
    }
}
