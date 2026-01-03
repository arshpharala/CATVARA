<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentTermRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'code' => 'required|string|max:50|unique:payment_terms,code',
            'name' => 'required|string|max:255',
            'due_days' => 'required|integer|min:0',
        ];
    }
}
