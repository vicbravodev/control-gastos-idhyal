<?php

namespace App\Http\Requests\ExpenseRequests;

use App\Models\ExpenseRequest;
use App\Models\ExpenseRequestApproval;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RejectExpenseRequestApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ExpenseRequest $expenseRequest */
        $expenseRequest = $this->route('expenseRequest');
        /** @var ExpenseRequestApproval $approval */
        $approval = $this->route('approval');

        if ((int) $approval->expense_request_id !== (int) $expenseRequest->getKey()) {
            return false;
        }

        return $this->user()->can('reject', $approval);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'min:1', 'max:65535'],
        ];
    }
}
