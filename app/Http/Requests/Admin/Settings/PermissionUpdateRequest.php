<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class PermissionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('permission');

        return [
            'module_id' => ['required', 'integer', 'exists:modules,id'],
            'name'      => ['required', 'string', 'max:191'],
            'slug'      => ['required', 'string', 'max:191', 'unique:permissions,slug,' . $id],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
