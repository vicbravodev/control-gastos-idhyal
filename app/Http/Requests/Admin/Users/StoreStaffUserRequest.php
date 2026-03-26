<?php

namespace App\Http\Requests\Admin\Users;

use App\Concerns\PasswordValidationRules;
use App\Models\State;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStaffUserRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('manageStaffDirectory', User::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => $this->filled('username') ? $this->string('username')->toString() : null,
            'phone' => $this->filled('phone') ? $this->string('phone')->toString() : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'username' => ['nullable', 'string', 'max:64', Rule::unique(User::class, 'username')],
            'phone' => ['nullable', 'string', 'max:32'],
            'password' => $this->passwordRules(),
            'role_id' => ['nullable', 'integer', Rule::exists('roles', 'id')],
            'region_id' => ['nullable', 'integer', Rule::exists('regions', 'id')],
            'state_id' => ['nullable', 'integer', Rule::exists('states', 'id')],
            'hire_date' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $regionId = $this->input('region_id');
            $stateId = $this->input('state_id');

            if ($stateId !== null && $stateId !== '' && ($regionId === null || $regionId === '')) {
                $validator->errors()->add(
                    'state_id',
                    __('Seleccione una región para asignar un estado.'),
                );

                return;
            }

            if ($stateId !== null && $stateId !== '' && $regionId !== null && $regionId !== '') {
                $ok = State::query()
                    ->whereKey((int) $stateId)
                    ->where('region_id', (int) $regionId)
                    ->exists();

                if (! $ok) {
                    $validator->errors()->add(
                        'state_id',
                        __('El estado no pertenece a la región indicada.'),
                    );
                }
            }
        });
    }
}
