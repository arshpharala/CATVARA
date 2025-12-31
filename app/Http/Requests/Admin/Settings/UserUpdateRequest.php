<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:6'],
            'is_active' => ['nullable', 'boolean'],
            'user_type' => ['nullable', 'string', 'max:50'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
