<?php

namespace App\Http\Requests\ExpenseRequests;

use App\Enums\DeliveryMethod;
use App\Models\ExpenseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreExpenseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ExpenseRequest::class);
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
            'expense_concept_id' => [
                'required',
                'integer',
                Rule::exists('expense_concepts', 'id')->where(fn ($q) => $q->where('is_active', true)),
            ],
            'concept_description' => ['nullable', 'string', 'max:2000'],
            'delivery_method' => ['required', 'string', Rule::enum(DeliveryMethod::class)],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:'.$mimes, 'max:'.$maxKb],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $files = $this->file('attachments', []);
            if (! is_array($files)) {
                return;
            }
            $max = (int) config('expense_requests.submission_attachments_max_count');
            if (count($files) > $max) {
                $validator->errors()->add(
                    'attachments',
                    __('Máximo :max archivos por solicitud.', ['max' => $max]),
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
