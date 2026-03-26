<?php

namespace App\Http\Requests\Admin\States;

use App\Models\Region;
use App\Models\State;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var State $state */
        $state = $this->route('state');

        return $this->user()->can('update', $state);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var State $state */
        $state = $this->route('state');

        return [
            'region_id' => ['required', 'integer', Rule::exists(Region::class, 'id')],
            'code' => [
                'required',
                'string',
                'max:16',
                Rule::unique('states', 'code')->ignore($state->id),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
