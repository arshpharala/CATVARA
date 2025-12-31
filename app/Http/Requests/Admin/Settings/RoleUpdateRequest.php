<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class RoleUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:191'],
            'slug'        => ['nullable', 'string', 'max:191'],
            'is_active'   => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }
}
