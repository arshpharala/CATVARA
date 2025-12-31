<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class ModuleStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:191'],
            'slug'      => ['nullable', 'string', 'max:191', 'unique:modules,slug'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
