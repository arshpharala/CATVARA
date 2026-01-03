<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class QuoteStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'payment_term_id' => ['nullable', 'integer', 'exists:payment_terms,id'],
            'valid_until' => ['nullable', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'currency_id.required' => 'Please select a currency.',
            'valid_until.after' => 'Valid until date must be a future date.',
        ];
    }
}
