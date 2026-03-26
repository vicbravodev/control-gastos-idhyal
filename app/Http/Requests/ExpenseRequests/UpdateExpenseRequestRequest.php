<?php

namespace App\Http\Requests\ExpenseRequests;

use App\Enums\DeliveryMethod;
use App\Models\ExpenseConcept;
use App\Models\ExpenseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateExpenseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ExpenseRequest $expenseRequest */
        $expenseRequest = $this->route('expense_request');

        return $this->user()->can('update', $expenseRequest);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $mimes = config('expense_requests.submission_attachments_mime_extensions');
        $maxKb = (int) config('expense_requests.submission_attachments_max_kb');

        return [
            'requested_amount_cents' => ['required', 'integer', 'min:1'],
            'expense_concept_id' => ['required', 'integer', 'exists:expense_concepts,id'],
            'concept_description' => ['nullable', 'string', 'max:2000'],
            'delivery_method' => ['required', 'string', Rule::enum(DeliveryMethod::class)],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:'.$mimes, 'max:'.$maxKb],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var ExpenseRequest $expenseRequest */
            $expenseRequest = $this->route('expense_request');
            $files = $this->file('attachments', []);
            if (! is_array($files)) {
                return;
            }
            $max = (int) config('expense_requests.submission_attachments_max_count');
            $existing = $expenseRequest->attachments()->count();
            if ($existing + count($files) > $max) {
                $validator->errors()->add(
                    'attachments',
                    __('Máximo :max archivos por solicitud.', ['max' => $max]),
                );
            }

            $conceptId = (int) $this->input('expense_concept_id');
            if ($conceptId === 0) {
                return;
            }
            $concept = ExpenseConcept::query()->find($conceptId);
            if ($concept === null) {
                return;
            }
            if (! $concept->is_active && $conceptId !== (int) $expenseRequest->expense_concept_id) {
                $validator->errors()->add(
                    'expense_concept_id',
                    __('El concepto seleccionado no está disponible.'),
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attachments.*.mimes' => __('Cada archivo debe ser PDF o imagen (JPG, PNG, WEBP).'),
            'attachments.*.max' => __('Cada archivo no debe superar :max kilobytes.', ['max' => (int) config('expense_requests.submission_attachments_max_kb')]),
        ];
    }
}
