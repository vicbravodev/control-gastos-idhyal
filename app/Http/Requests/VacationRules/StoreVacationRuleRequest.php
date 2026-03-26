<?php

namespace App\Http\Requests\VacationRules;

use App\Models\VacationRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreVacationRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', VacationRule::class);
    }

    protected function prepareForValidation(): void
    {
        foreach ([
            'max_years_service',
            'max_days_per_request',
            'max_days_per_month',
            'max_days_per_quarter',
            'max_days_per_year',
        ] as $key) {
            if ($this->input($key) === '') {
                $this->merge([$key => null]);
            }
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:64', Rule::unique(VacationRule::class, 'code')],
            'name' => ['required', 'string', 'max:255'],
            'min_years_service' => ['required', 'numeric', 'min:0'],
            'max_years_service' => ['nullable', 'numeric', 'min:0'],
            'days_granted_per_year' => ['required', 'integer', 'min:0', 'max:65535'],
            'max_days_per_request' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'max_days_per_month' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'max_days_per_quarter' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'max_days_per_year' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'blackout_dates' => ['nullable', 'string'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $min = (float) $this->input('min_years_service');
            $maxRaw = $this->input('max_years_service');
            if ($maxRaw !== null && $maxRaw !== '') {
                $max = (float) $maxRaw;
                if ($max < $min) {
                    $validator->errors()->add(
                        'max_years_service',
                        __('El tope de años debe ser mayor o igual al mínimo.'),
                    );
                }
            }

            $raw = $this->string('blackout_dates')->toString();
            if ($raw === '') {
                return;
            }

            $decoded = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                $validator->errors()->add(
                    'blackout_dates',
                    __('Debe ser un arreglo JSON válido (p. ej. [] ) o dejarse vacío.'),
                );
            }
        });
    }
}
