<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class QuoteUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
            'payment_term_id' => ['nullable', 'integer', 'exists:payment_terms,id'],
            'valid_until' => ['nullable', 'date'],
            'status_id' => ['nullable', 'integer', 'exists:quote_statuses,id'],
        ];
    }
}
