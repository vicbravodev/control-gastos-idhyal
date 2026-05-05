<?php

namespace App\Http\Requests\ExpenseRequests;

use App\Enums\ExpenseReportDocumentType;
use App\Models\ExpenseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReimbursementExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ExpenseRequest::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isFactura = $this->documentType() === ExpenseReportDocumentType::Factura;

        return [
            // El monto se auto-llena del CFDI cuando se envía un XML.
            'reported_amount_cents' => [$isFactura ? 'nullable' : 'required', 'integer', 'min:0'],
            'expense_concept_id' => [
                'required',
                'integer',
                Rule::exists('expense_concepts', 'id')->where(fn ($q) => $q->where('is_active', true)),
            ],
            'concept_description' => ['nullable', 'string', 'max:2000'],
            'document_type' => ['required', Rule::enum(ExpenseReportDocumentType::class)],
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'xml' => [$isFactura ? 'required' : 'nullable', 'file', 'mimes:xml', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pdf.required' => __('Debes adjuntar el PDF de la comprobación.'),
            'xml.required' => __('Debes adjuntar el XML del CFDI cuando la comprobación es una factura.'),
        ];
    }

    public function documentType(): ExpenseReportDocumentType
    {
        $value = $this->input('document_type');

        return ExpenseReportDocumentType::tryFrom(is_string($value) ? $value : '')
            ?? ExpenseReportDocumentType::Factura;
    }
}
