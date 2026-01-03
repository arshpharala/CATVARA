<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCurrencyRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'code' => 'required|string|size:3|unique:currencies,code,' . $this->currency->id,
            'name' => 'required|string|max:255',
            'symbol' => 'nullable|string|max:5',
            'decimal_places' => 'required|integer|min:0|max:8',
        ];
    }
}
