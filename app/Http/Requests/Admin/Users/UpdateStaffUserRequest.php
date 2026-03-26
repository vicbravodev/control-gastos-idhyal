<?php

namespace App\Http\Requests\Admin\Users;

use App\Enums\RoleSlug;
use App\Models\State;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStaffUserRequest extends FormRequest
{
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
        /** @var User $target */
        $target = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($target->id),
            ],
            'username' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique(User::class, 'username')->ignore($target->id),
            ],
            'phone' => ['nullable', 'string', 'max:32'],
            'role_id' => ['nullable', 'integer', Rule::exists('roles', 'id')],
            'region_id' => ['nullable', 'integer', Rule::exists('regions', 'id')],
            'state_id' => ['nullable', 'integer', Rule::exists('states', 'id')],
            'hire_date' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var User $target */
            $target = $this->route('user');
            $actor = $this->user();

            $regionId = $this->input('region_id');
            $stateId = $this->input('state_id');
            $roleId = $this->input('role_id');

            if ($actor !== null && $actor->id === $target->id && $target->hasRole(RoleSlug::SuperAdmin)) {
                $newRoleId = $roleId === null || $roleId === '' ? null : (int) $roleId;
                if ($newRoleId !== $target->role_id) {
                    $validator->errors()->add(
                        'role_id',
                        __('No puede cambiar su propio rol desde este panel.'),
                    );

                    return;
                }
            }

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
