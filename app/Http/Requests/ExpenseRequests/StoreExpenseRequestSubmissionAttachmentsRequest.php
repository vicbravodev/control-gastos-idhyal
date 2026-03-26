<?php

namespace App\Http\Requests\ExpenseRequests;

use App\Models\ExpenseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreExpenseRequestSubmissionAttachmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ExpenseRequest $expenseRequest */
        $expenseRequest = $this->route('expense_request');

        return $this->user()->can('addSubmissionAttachments', $expenseRequest);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $mimes = config('expense_requests.submission_attachments_mime_extensions');
        $maxKb = (int) config('expense_requests.submission_attachments_max_kb');

        return [
            'attachments' => ['required', 'array', 'min:1'],
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
