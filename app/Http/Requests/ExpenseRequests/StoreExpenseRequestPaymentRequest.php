<?php

namespace App\Http\Requests\ExpenseRequests;

use App\Enums\PaymentMethod;
use App\Models\ExpenseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequestPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ExpenseRequest $expenseRequest */
        $expenseRequest = $this->route('expenseRequest');

        return $this->user()->can('recordPayment', $expenseRequest);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var ExpenseRequest $expenseRequest */
        $expenseRequest = $this->route('expenseRequest');

        return [
            'amount_cents' => [
                'required',
                'integer',
                'min:1',
                Rule::in([$expenseRequest->approved_amount_cents]),
            ],
            'payment_method' => ['required', 'string', Rule::enum(PaymentMethod::class)],
            'paid_on' => ['required', 'date'],
            'transfer_reference' => ['nullable', 'required_if:payment_method,transfer', 'string', 'max:255'],
            'evidence' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount_cents.in' => __('El monto debe coincidir exactamente con el monto aprobado.'),
            'evidence.required' => __('Debes adjuntar la evidencia del pago.'),
        ];
    }
}
