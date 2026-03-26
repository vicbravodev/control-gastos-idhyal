<?php

namespace App\Http\Requests\VacationRequests;

use App\Models\VacationRequest;
use App\Models\VacationRequestApproval;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RejectVacationRequestApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var VacationRequest $vacationRequest */
        $vacationRequest = $this->route('vacation_request');
        /** @var VacationRequestApproval $approval */
        $approval = $this->route('approval');

        if ((int) $approval->vacation_request_id !== (int) $vacationRequest->getKey()) {
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
