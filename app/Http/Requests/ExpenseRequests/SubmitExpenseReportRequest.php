<?php

namespace App\Http\Requests\ExpenseRequests;

use App\Enums\ExpenseReportDocumentType;
use App\Models\ExpenseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $isFactura = $this->documentType() === ExpenseReportDocumentType::Factura;

        return [
            // El monto es opcional cuando viene XML porque se auto-llena del CFDI;
            // para recibos sigue siendo obligatorio.
            'reported_amount_cents' => [$isFactura ? 'nullable' : 'required', 'integer', 'min:0'],
            'document_type' => ['required', Rule::enum(ExpenseReportDocumentType::class)],
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'xml' => [$isFactura ? 'required' : 'nullable', 'file', 'mimes:xml', 'max:10240'],
            'label' => ['nullable', 'string', 'max:64'],
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
            'document_type.required' => __('Selecciona el tipo de documento.'),
        ];
    }

    public function documentType(): ExpenseReportDocumentType
    {
        $value = $this->input('document_type');

        return ExpenseReportDocumentType::tryFrom(is_string($value) ? $value : '')
            ?? ExpenseReportDocumentType::Factura;
    }
}
