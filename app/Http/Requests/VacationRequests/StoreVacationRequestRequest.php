<?php

namespace App\Http\Requests\VacationRequests;

use App\Models\VacationRequest;
use App\Services\VacationRequests\VacationBusinessDayCounter;
use App\Services\VacationRequests\VacationEntitlementBalanceResolver;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreVacationRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', VacationRequest::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $startsOn = $this->date('starts_on');
            $endsOn = $this->date('ends_on');
            if ($startsOn === null || $endsOn === null) {
                return;
            }

            $count = app(VacationBusinessDayCounter::class)->countInclusive($startsOn, $endsOn);
            if ($count < 1) {
                $validator->errors()->add(
                    'ends_on',
                    __('El periodo debe incluir al menos un día hábil.'),
                );

                return;
            }

            $user = $this->user();
            $balance = app(VacationEntitlementBalanceResolver::class)->resolveForUser(
                $user,
                CarbonImmutable::now(),
                (int) $startsOn->format('Y'),
            );

            if (! $balance['has_hire_date']) {
                $validator->errors()->add(
                    'hire_date',
                    __('Tu perfil no incluye fecha de ingreso. Pide a un administrador que la registre.'),
                );

                return;
            }

            if ($balance['pending_first_year']) {
                $validator->errors()->add(
                    'starts_on',
                    __('Aún no cumples el primer año de antigüedad requerido para solicitar vacaciones.'),
                );

                return;
            }

            if ($count > $balance['days_remaining']) {
                $validator->errors()->add(
                    'ends_on',
                    __('No tienes suficientes días de vacaciones disponibles para este periodo.'),
                );

                return;
            }

            $maxPerRequest = $balance['rule']['max_days_per_request'] ?? null;
            if ($maxPerRequest !== null && $count > $maxPerRequest) {
                $validator->errors()->add(
                    'ends_on',
                    __('El periodo supera el máximo de días hábiles permitidos por solicitud (:max).', [
                        'max' => $maxPerRequest,
                    ]),
                );
            }
        });
    }
}
