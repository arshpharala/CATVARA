<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UserAssignCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'is_owner' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
