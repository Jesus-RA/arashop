<?php

namespace App\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalculatePricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cost_usd' => ['required', 'numeric', 'gt:0'],
            'shipping_usd' => ['sometimes', 'numeric', 'min:0'],
            'selling_price_mxn' => ['sometimes', 'nullable', 'numeric', 'gt:0'],
            'fx_rate' => ['sometimes', 'numeric', 'gt:0'],
            'margin_percent' => ['sometimes', 'numeric', 'between:0,100'],
            'platform_fee_percent' => ['sometimes', 'numeric', 'between:0,100'],
            'packaging_fee_mxn' => ['sometimes', 'numeric', 'min:0'],
            'discount_percent' => ['sometimes', 'numeric', 'between:0,100'],
        ];
    }
}
