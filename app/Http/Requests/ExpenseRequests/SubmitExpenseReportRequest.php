<?php

namespace App\Http\Requests\ExpenseRequests;

use App\Models\ExpenseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubmitExpenseReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ExpenseRequest $expenseRequest */
        $expenseRequest = $this->route('expenseRequest');

        return $this->user()->can('submitExpenseReport', $expenseRequest);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reported_amount_cents' => ['required', 'integer', 'min:1'],
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'xml' => ['required', 'file', 'mimes:xml', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pdf.required' => __('Debes adjuntar el PDF de la comprobación.'),
            'xml.required' => __('Debes adjuntar el XML de la comprobación.'),
        ];
    }
}
