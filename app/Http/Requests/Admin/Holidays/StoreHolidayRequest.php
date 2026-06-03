<?php

namespace App\Http\Requests\Admin\Holidays;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Holiday::class);
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('date')) {
            $this->merge(['date' => Carbon::parse($this->input('date'))->toDateString()]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date', Rule::unique('holidays', 'date')],
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.unique' => __('Ya existe un día festivo registrado para esa fecha.'),
        ];
    }
}
