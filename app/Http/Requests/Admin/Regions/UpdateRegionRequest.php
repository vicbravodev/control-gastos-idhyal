<?php

namespace App\Http\Requests\Admin\Regions;

use App\Models\Region;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRegionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Region $region */
        $region = $this->route('region');

        return $this->user()->can('update', $region);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Region $region */
        $region = $this->route('region');

        return [
            'code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('regions', 'code')->ignore($region->id),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
