<?php

namespace App\Http\Requests\Admin\States;

use App\Models\Region;
use App\Models\State;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', State::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'region_id' => ['required', 'integer', Rule::exists(Region::class, 'id')],
            'code' => ['required', 'string', 'max:16', 'unique:states,code'],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
