<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class PermissionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'module_id' => ['required', 'integer', 'exists:modules,id'],
            'name'      => ['required', 'string', 'max:191'],
            'slug'      => ['required', 'string', 'max:191', 'unique:permissions,slug'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
