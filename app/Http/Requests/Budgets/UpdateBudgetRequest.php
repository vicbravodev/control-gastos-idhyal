<?php

namespace App\Http\Requests\Budgets;

use App\Models\Budget;
use App\Models\Region;
use App\Models\Role;
use App\Models\State;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Budget $budget */
        $budget = $this->route('budget');

        return $this->user()->can('update', $budget);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'budgetable_type' => ['required', 'string', Rule::in(['user', 'role', 'state', 'region'])],
            'budgetable_id' => ['required', 'integer', 'min:1'],
            'period_starts_on' => ['required', 'date'],
            'period_ends_on' => ['required', 'date', 'after_or_equal:period_starts_on'],
            'amount_limit_cents' => ['required', 'integer', 'min:1'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var array<string, class-string> $map */
            $map = [
                'user' => User::class,
                'role' => Role::class,
                'state' => State::class,
                'region' => Region::class,
            ];

            $type = $this->string('budgetable_type')->toString();
            $id = $this->integer('budgetable_id');

            if (! isset($map[$type])) {
                return;
            }

            if (! $map[$type]::query()->whereKey($id)->exists()) {
                $validator->errors()->add(
                    'budgetable_id',
                    __('El alcance seleccionado no existe.'),
                );
            }
        });
    }
}
