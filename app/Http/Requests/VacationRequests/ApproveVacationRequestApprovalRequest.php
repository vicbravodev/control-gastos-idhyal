<?php

namespace App\Http\Requests\VacationRequests;

use App\Models\VacationRequest;
use App\Models\VacationRequestApproval;
use Illuminate\Foundation\Http\FormRequest;

class ApproveVacationRequestApprovalRequest extends FormRequest
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
