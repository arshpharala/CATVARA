<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyUpdateRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // resource route: companies/{company}
        $companyId = $this->route('company') ?? $this->route('companies') ?? $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('companies', 'code')->ignore($companyId),
            ],
            'website_url' => ['nullable', 'url', 'max:255'],
            'company_status_id' => ['required', 'integer', 'exists:company_statuses,id'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],

            // company_details (nullable)
            'invoice_prefix' => ['nullable', 'string', 'max:20'],
            'invoice_postfix' => ['nullable', 'string', 'max:20'],
            'quote_prefix' => ['nullable', 'string', 'max:20'],
            'quote_postfix' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:2000'],
            'tax_number' => ['nullable', 'string', 'max:100'],

            // Base Currency (only editable if not set - handled in controller but valid here)
            'base_currency_id' => ['nullable', 'exists:currencies,id'],

            // Payment Terms
            'payment_terms' => ['nullable', 'array'],
            'payment_terms.*' => ['exists:payment_terms,id'],
            'default_payment_term_id' => ['nullable', 'exists:payment_terms,id'], // If we want to set a default on pivot
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Company code already exists.',
        ];
    }
}
