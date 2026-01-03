<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $companyId = $this->route('company')?->id;
        $customerId = $this->route('customer');

        return [
            'type' => ['required', Rule::in(['INDIVIDUAL', 'COMPANY'])],
            'display_name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers', 'email')
                    ->where('company_id', $companyId)
                    ->ignore($customerId),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('customers', 'phone')
                    ->where('company_id', $companyId)
                    ->ignore($customerId),
            ],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already registered for another customer in this company.',
            'phone.unique' => 'This phone number is already registered for another customer in this company.',
        ];
    }
}
