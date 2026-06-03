<?php

namespace App\Http\Requests\Admin\Holidays;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('holiday'));
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
        $holidayId = $this->route('holiday')?->id;

        return [
            'date' => ['required', 'date', Rule::unique('holidays', 'date')->ignore($holidayId)],
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
