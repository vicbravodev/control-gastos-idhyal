<?php

namespace App\Http\Requests\Admin\Users;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreVacationEntitlementAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageStaffDirectory', User::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $currentYear = (int) now()->year;

        return [
            'calendar_year' => ['required', 'integer', 'min:'.($currentYear - 5), 'max:'.($currentYear + 1)],
            'days' => ['required', 'integer', 'between:-60,60', 'not_in:0'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'days.not_in' => __('El ajuste debe ser distinto de cero.'),
            'days.between' => __('El ajuste debe estar entre -60 y +60 días.'),
        ];
    }
}
