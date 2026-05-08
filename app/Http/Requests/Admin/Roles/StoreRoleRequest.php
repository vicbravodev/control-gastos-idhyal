<?php

namespace App\Http\Requests\Admin\Roles;

use App\Models\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Role::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('roles', 'slug')],
            'name' => ['required', 'string', 'max:150', Rule::unique('roles', 'name')],
            'description' => ['nullable', 'string', 'max:500'],
            'permission_ids' => ['array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.alpha_dash' => 'El identificador (slug) sólo puede contener letras, números, guiones y guiones bajos.',
            'slug.unique' => 'Ya existe un rol con ese identificador.',
            'name.unique' => 'Ya existe un rol con ese nombre.',
        ];
    }
}
