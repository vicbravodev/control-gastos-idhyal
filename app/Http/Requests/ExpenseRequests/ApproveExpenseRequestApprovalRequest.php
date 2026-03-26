<?php

namespace App\Http\Requests\ExpenseRequests;

use App\Models\ExpenseRequest;
use App\Models\ExpenseRequestApproval;
use Illuminate\Foundation\Http\FormRequest;

class ApproveExpenseRequestApprovalRequest extends FormRequest
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

        return $this->user()->can('approve', $approval);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
