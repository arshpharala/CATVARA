<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'is_active' => ['nullable', 'boolean'],
            'user_type' => ['nullable', 'string', 'max:50'],
            'email_verified_at' => ['nullable', 'date'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
