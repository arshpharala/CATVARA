<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class ModuleUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('module');

        return [
            'name'      => ['required', 'string', 'max:191'],
            'slug'      => ['nullable', 'string', 'max:191', 'unique:modules,slug,' . $id],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
