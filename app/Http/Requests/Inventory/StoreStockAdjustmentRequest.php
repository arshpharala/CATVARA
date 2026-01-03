<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'inventory_location_id' => 'required|exists:inventory_locations,id',
            'product_variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|numeric|min:0.01',
            'type' => 'required|in:add,remove',
            'reason' => 'nullable|string',
            'redirect_to' => 'nullable|url',
        ];
    }
}
